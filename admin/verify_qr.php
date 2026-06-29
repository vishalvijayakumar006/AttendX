<?php
// File: admin/verify_qr.php
// Purpose: Allows admins/organizers to scan or manually enter a QR token,
//          then validates it and marks attendance in real time.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

require_login();
require_role('admin');

$db = Database::getInstance();
$result = null; // holds scan result data

// ── Handle manual token validation (POST) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_token'])) {
    $token = trim($_POST['qr_token']);

    if (empty($token)) {
        $result = ['status' => 'error', 'message' => 'Please enter a QR token.'];
    } else {
        try {
            // Look up the QR code token
            $stmt = $db->prepare("
                SELECT
                    qr.id          AS qr_id,
                    qr.qr_token,
                    qr.is_used,
                    qr.used_at,
                    r.id           AS reg_id,
                    r.status       AS reg_status,
                    u.name         AS attendee_name,
                    u.email        AS attendee_email,
                    u.phone        AS attendee_phone,
                    e.title        AS event_title,
                    e.date         AS event_date,
                    e.time         AS event_time,
                    e.location     AS event_location
                FROM qr_codes qr
                JOIN registrations r ON qr.registration_id = r.id
                JOIN users         u ON r.user_id  = u.id
                JOIN events        e ON r.event_id = e.id
                WHERE qr.qr_token = :token
            ");
            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch();

            if (!$row) {
                // Token does not exist at all
                $result = [
                    'status'  => 'invalid',
                    'message' => 'INVALID QR',
                    'detail'  => 'This token does not match any registered pass in the system.',
                ];
            } elseif ($row['reg_status'] !== 'approved') {
                // Registration was not approved
                $result = [
                    'status'  => 'rejected',
                    'message' => 'ACCESS DENIED',
                    'detail'  => 'This registration was not approved (status: ' . strtoupper($row['reg_status']) . ').',
                    'attendee' => $row,
                ];
            } elseif ($row['is_used']) {
                // Already scanned before → duplicate entry attempt
                $result = [
                    'status'    => 'duplicate',
                    'message'   => 'ALREADY CHECKED IN',
                    'detail'    => 'This ticket was already used on ' . date('M d, Y \a\t h:i A', strtotime($row['used_at'])),
                    'attendee'  => $row,
                ];
            } else {
                // ✅ Valid — mark attendance
                $db->beginTransaction();

                // Update qr_codes: mark used
                $upd = $db->prepare("UPDATE qr_codes SET is_used = 1, used_at = NOW() WHERE id = :id");
                $upd->execute(['id' => $row['qr_id']]);

                // Insert into attendance table
                $ins = $db->prepare("
                    INSERT INTO attendance (registration_id, user_id, event_id, checked_in_at)
                    SELECT r.id, r.user_id, r.event_id, NOW()
                    FROM registrations r
                    WHERE r.id = :reg_id
                    ON DUPLICATE KEY UPDATE checked_in_at = NOW()
                ");
                $ins->execute(['reg_id' => $row['reg_id']]);

                $db->commit();

                $result = [
                    'status'   => 'valid',
                    'message'  => 'VALID ENTRY ✓',
                    'detail'   => 'Check-in recorded successfully at ' . date('h:i A'),
                    'attendee' => $row,
                ];
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $result = ['status' => 'error', 'message' => 'Database Error', 'detail' => $e->getMessage()];
        }
    }
}

// ── AJAX endpoint: webcam scan sends token via fetch() ────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === '1' && isset($_POST['qr_token'])) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

$page_title = "Verify Entry | SmartEntry Admin";
include '../includes/header.php';
?>

<style>
/* ── Layout ───────────────────────────────────────────────────── */
.verify-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    align-items: start;
}
@media (max-width: 900px) {
    .verify-grid { grid-template-columns: 1fr; }
}

.verify-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 28px;
}
.panel-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.panel-title i { color: var(--primary); }

/* ── Camera scanner ───────────────────────────────────────────── */
#reader {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    min-height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
#reader video {
    width: 100%;
    height: auto;
    border-radius: 12px;
}
.scan-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.scan-frame {
    width: 180px; height: 180px;
    border: 3px solid var(--primary);
    border-radius: 16px;
    box-shadow: 0 0 0 2000px rgba(0,0,0,0.4);
}
.scan-line {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 160px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
    animation: scanAnim 2s ease-in-out infinite;
}
@keyframes scanAnim {
    0%   { top: calc(50% - 80px); }
    50%  { top: calc(50% + 80px); }
    100% { top: calc(50% - 80px); }
}

#camera-status {
    text-align: center;
    color: var(--text-muted);
    font-size: 0.88rem;
    margin-top: 12px;
}

.btn-cam {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-start { background: var(--primary); color: white; }
.btn-start:hover { background: var(--primary-hover); }
.btn-stop  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.btn-stop:hover { background: rgba(239,68,68,0.25); }

/* ── Manual form ──────────────────────────────────────────────── */
.form-group { margin-bottom: 16px; }
.form-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.form-control {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    color: var(--text-main);
    font-family: monospace;
    font-size: 0.95rem;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.form-control:focus { outline: none; border-color: var(--primary); }
.btn-validate {
    width: 100%;
    padding: 13px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 15px var(--primary-glow);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-sizing: border-box;
}
.btn-validate:hover { background: var(--primary-hover); transform: translateY(-1px); }

/* ── Result panel ─────────────────────────────────────────────── */
.result-panel {
    border-radius: 16px;
    padding: 24px;
    margin-top: 0;
    border: 2px solid;
    transition: all 0.4s;
    animation: fadeInUp 0.4s ease;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.result-valid    { background: rgba(16,185,129,0.08); border-color: #10b981; }
.result-duplicate{ background: rgba(234,179,8,0.08);  border-color: #f59e0b; }
.result-invalid  { background: rgba(239,68,68,0.08);  border-color: #ef4444; }
.result-rejected { background: rgba(239,68,68,0.08);  border-color: #ef4444; }
.result-error    { background: rgba(107,114,128,0.08); border-color: #6b7280; }

.result-status {
    font-size: 1.6rem;
    font-weight: 800;
    margin-bottom: 6px;
}
.color-valid     { color: #10b981; }
.color-duplicate { color: #f59e0b; }
.color-invalid   { color: #ef4444; }
.color-rejected  { color: #ef4444; }
.color-error     { color: #9ca3af; }

.result-detail {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 16px;
}
.attendee-info {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 16px;
}
.info-row {
    display: flex;
    gap: 10px;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: 0.88rem;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--text-muted); width: 110px; flex-shrink: 0; }
.info-value { color: var(--text-main); font-weight: 500; }

/* ── Recent check-ins table ───────────────────────────────────── */
.recent-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.recent-table th {
    text-align: left;
    padding: 10px 14px;
    color: var(--text-muted);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border-color);
}
.recent-table td {
    padding: 12px 14px;
    color: var(--text-main);
    border-bottom: 1px solid rgba(255,255,255,0.04);
    vertical-align: middle;
}
.recent-table tr:last-child td { border-bottom: none; }
.badge-in {
    background: rgba(16,185,129,0.15);
    color: #34d399;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
}

/* ── Mobile table scroll ─────────────────────────────────────── */
.table-scroll { overflow-x: auto; }
</style>

<div class="page-header" style="margin-bottom:28px;">
    <h1 style="font-size:2rem;font-weight:800;color:var(--text-main);">
        <i class="fa-solid fa-qrcode" style="color:var(--primary);"></i> Verify Entry
    </h1>
    <p style="color:var(--text-muted);margin-top:4px;">
        Scan a QR pass using your camera or enter the token code manually.
    </p>
</div>

<div class="verify-grid">

    <!-- LEFT: Camera Scanner + Manual Input -->
    <div style="display:flex;flex-direction:column;gap:24px;">

        <!-- Camera Scanner Panel -->
        <div class="verify-panel">
            <div class="panel-title"><i class="fa-solid fa-camera"></i> Webcam / Mobile Scanner</div>

            <div id="reader">
                <p style="color:#555;font-size:0.9rem;">Camera feed will appear here</p>
                <div class="scan-overlay">
                    <div class="scan-frame"></div>
                    <div class="scan-line" id="scan-line" style="display:none;"></div>
                </div>
            </div>
            <div id="camera-status">Camera is off. Press Start to begin scanning.</div>

            <button class="btn-cam btn-start" id="btn-start-cam" onclick="startCamera()">
                <i class="fa-solid fa-video"></i> Start Camera
            </button>
            <button class="btn-cam btn-stop" id="btn-stop-cam" onclick="stopCamera()" style="display:none;">
                <i class="fa-solid fa-stop"></i> Stop Camera
            </button>
        </div>

        <!-- Manual Token Input Panel -->
        <div class="verify-panel">
            <div class="panel-title"><i class="fa-solid fa-keyboard"></i> Manual Token Entry</div>

            <form method="POST" action="verify_qr.php">
                <div class="form-group">
                    <label class="form-label" for="qr_token">QR Pass Token</label>
                    <input type="text" id="qr_token" name="qr_token" class="form-control"
                           placeholder="Paste token here (e.g. pass_5f3c9e2b...)"
                           value="<?php echo isset($_POST['qr_token']) ? htmlspecialchars($_POST['qr_token']) : ''; ?>"
                           autocomplete="off" required>
                </div>
                <button type="submit" class="btn-validate">
                    <i class="fa-solid fa-shield-halved"></i> Validate Token
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT: Scan Result + Recent Check-ins -->
    <div style="display:flex;flex-direction:column;gap:24px;">

        <!-- Result Panel -->
        <div class="verify-panel" id="result-area">
            <div class="panel-title"><i class="fa-solid fa-circle-info"></i> Validation Result</div>

            <?php if ($result): ?>
                <?php
                    $css_class  = 'result-' . $result['status'];
                    $color_class = 'color-' . $result['status'];
                    $icon_map   = [
                        'valid'     => 'fa-circle-check',
                        'duplicate' => 'fa-triangle-exclamation',
                        'invalid'   => 'fa-circle-xmark',
                        'rejected'  => 'fa-ban',
                        'error'     => 'fa-gear',
                    ];
                    $icon = $icon_map[$result['status']] ?? 'fa-question';
                ?>
                <div class="result-panel <?php echo $css_class; ?>">
                    <div class="result-status <?php echo $color_class; ?>">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                        <?php echo htmlspecialchars($result['message']); ?>
                    </div>
                    <p class="result-detail"><?php echo htmlspecialchars($result['detail'] ?? ''); ?></p>

                    <?php if (!empty($result['attendee'])): $a = $result['attendee']; ?>
                        <div class="attendee-info">
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-user"></i> Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($a['attendee_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($a['attendee_email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-phone"></i> Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($a['attendee_phone']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-calendar"></i> Event</span>
                                <span class="info-value"><?php echo htmlspecialchars($a['event_title']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-clock"></i> Date</span>
                                <span class="info-value">
                                    <?php echo date('M d, Y', strtotime($a['event_date'])); ?>
                                    at <?php echo date('g:i A', strtotime($a['event_time'])); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i class="fa-solid fa-location-dot"></i> Venue</span>
                                <span class="info-value"><?php echo htmlspecialchars($a['event_location']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center;padding:40px 20px;color:var(--text-muted);">
                    <i class="fa-solid fa-qrcode" style="font-size:3rem;color:var(--border-color);margin-bottom:16px;display:block;"></i>
                    <p>Scan a QR code or enter a token above.</p>
                    <p style="font-size:0.8rem;margin-top:8px;">Results will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Check-ins Panel -->
        <div class="verify-panel">
            <div class="panel-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Check-ins (Today)</div>
            <div class="table-scroll">
            <?php
            try {
                $recent = $db->prepare("
                    SELECT u.name, u.email, e.title AS event_title, a.checked_in_at
                    FROM attendance a
                    JOIN registrations r ON a.registration_id = r.id
                    JOIN users         u ON r.user_id = u.id
                    JOIN events        e ON r.event_id = e.id
                    WHERE DATE(a.checked_in_at) = CURDATE()
                    ORDER BY a.checked_in_at DESC
                    LIMIT 10
                ");
                $recent->execute();
                $checkins = $recent->fetchAll();
            } catch (PDOException $e) {
                $checkins = [];
            }
            ?>
            <?php if (empty($checkins)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:20px 0;font-size:0.9rem;">
                    No check-ins recorded today yet.
                </p>
            <?php else: ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Attendee</th>
                            <th>Event</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checkins as $c): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['name']); ?></strong><br>
                                    <span style="color:var(--text-muted);font-size:0.78rem;"><?php echo htmlspecialchars($c['email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($c['event_title']); ?></td>
                                <td>
                                    <span class="badge-in">
                                        <?php echo date('h:i A', strtotime($c['checked_in_at'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- html5-qrcode library for webcam scanning -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode = null;

function startCamera() {
    document.getElementById('btn-start-cam').style.display = 'none';
    document.getElementById('btn-stop-cam').style.display  = 'flex';
    document.getElementById('camera-status').textContent   = 'Starting camera...';
    document.getElementById('scan-line').style.display     = 'block';

    html5QrCode = new Html5Qrcode("reader");

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || cameras.length === 0) {
            document.getElementById('camera-status').textContent = 'No camera found on this device.';
            stopCamera();
            return;
        }

        // Prefer back camera on mobile
        const cam = cameras.find(c => /back|rear|environment/i.test(c.label)) || cameras[0];

        html5QrCode.start(
            cam.id,
            { fps: 10, qrbox: { width: 220, height: 220 } },
            (decodedText) => {
                // QR successfully decoded — auto-submit for validation
                document.getElementById('camera-status').textContent = 'QR detected! Validating...';
                stopCamera();
                submitToken(decodedText);
            },
            (errorMessage) => { /* scanning frame errors — silently ignore */ }
        ).then(() => {
            document.getElementById('camera-status').textContent = 'Camera active — point at a QR code.';
        }).catch(err => {
            document.getElementById('camera-status').textContent = 'Camera error: ' + err;
        });

    }).catch(err => {
        document.getElementById('camera-status').textContent = 'Cannot access camera: ' + err;
        stopCamera();
    });
}

function stopCamera() {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
        html5QrCode = null;
    }
    document.getElementById('btn-start-cam').style.display = 'flex';
    document.getElementById('btn-stop-cam').style.display  = 'none';
    document.getElementById('scan-line').style.display     = 'none';
    document.getElementById('camera-status').textContent   = 'Camera stopped.';
}

function submitToken(token) {
    // Fill the manual field and submit the form
    document.getElementById('qr_token').value = token;

    const form = document.querySelector('form[action="verify_qr.php"]');
    form.submit();
}
</script>

<?php include '../includes/header.php'; // Note: already included at top — this is footer ?>
<?php include '../includes/footer.php'; ?>
