<?php
// Admin Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin
// File Name: verify.php
// Purpose: Enables admins/volunteers to scan tickets, validate entries, and check in attendees.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure administrative privileges
require_admin();

$db = Database::getInstance();
$result_status = ""; // "VALID", "ALREADY_PRESENT", "INVALID", or empty
$attendee_info = null;
$error_message = "";

// Capture manual token submits or scanned AJAX/POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_token'])) {
    $token = trim($_POST['qr_token']);
    $admin_id = $_SESSION['user_id'];

    if (!empty($token)) {
        try {
            // 1. Join QR, Registrations, Users, and Events to check details
            $stmt = $db->prepare("
                SELECT q.registration_id, r.status,
                       u.name AS attendee_name, u.email AS attendee_email,
                       e.title AS event_title, e.id AS event_id
                FROM qr_codes q
                JOIN registrations r ON q.registration_id = r.id
                JOIN users u ON r.user_id = u.id
                JOIN events e ON r.event_id = e.id
                WHERE q.qr_token = :token
            ");
            $stmt->execute(['token' => $token]);
            $reg = $stmt->fetch();

            if ($reg) {
                $reg_id = intval($reg['registration_id']);

                if ($reg['status'] !== 'approved') {
                    $result_status = "INVALID";
                    $error_message = "Registration status is '" . strtoupper($reg['status']) . "' (Not Approved).";
                } else {
                    // 2. Check if already present in attendance log
                    $check_stmt = $db->prepare("SELECT marked_at FROM attendance WHERE registration_id = :reg_id");
                    $check_stmt->execute(['reg_id' => $reg_id]);
                    $attendance = $check_stmt->fetch();

                    if ($attendance) {
                        $result_status = "ALREADY_PRESENT";
                        $attendee_info = [
                            'name' => $reg['attendee_name'],
                            'email' => $reg['attendee_email'],
                            'event' => $reg['event_title'],
                            'time' => date('M d, Y h:i A', strtotime($attendance['marked_at']))
                        ];
                    } else {
                        // 3. Mark check-in entry inside database
                        $insert_stmt = $db->prepare("INSERT INTO attendance (registration_id, marked_by) VALUES (:reg_id, :admin_id)");
                        $insert_stmt->execute([
                            'reg_id' => $reg_id,
                            'admin_id' => $admin_id
                        ]);

                        $result_status = "VALID";
                        $attendee_info = [
                            'name' => $reg['attendee_name'],
                            'email' => $reg['attendee_email'],
                            'event' => $reg['event_title'],
                            'time' => date('M d, Y h:i A')
                        ];
                    }
                }
            } else {
                $result_status = "INVALID";
                $error_message = "This QR code token does not match any registration record in our system.";
            }
        } catch (PDOException $e) {
            $result_status = "INVALID";
            $error_message = "Database verification transaction failed: " . $e->getMessage();
        }
    } else {
        $result_status = "INVALID";
        $error_message = "Submitted scan token is empty.";
    }
}

$page_title = "Verify Entry Ticket | Admin Portal";
include '../includes/header.php';
?>

<!-- Include Html5-QRCode camera scanner library from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<style>
    .verify-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 32px;
        align-items: start;
        margin-top: 20px;
    }

    .card-verify {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 30px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .panel-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Scanner Viewfinder Box */
    #reader {
        width: 100%;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-color) !important;
        background: rgba(0,0,0,0.2);
    }

    #reader button {
        padding: 10px 20px;
        background: var(--primary);
        border: none;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        margin: 10px;
        transition: background 0.3s ease;
    }

    #reader button:hover {
        background: var(--primary-hover);
    }

    #reader select {
        padding: 8px 12px;
        background: var(--bg-color);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin: 10px;
    }

    /* Manual Input styling */
    .manual-form {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--border-color);
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-main);
        font-size: 1rem;
        margin-bottom: 12px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
    }

    .btn-verify-submit {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-verify-submit:hover {
        background: var(--primary-hover);
    }

    /* Scanned Results Panel */
    .result-box {
        text-align: center;
        padding: 40px 20px;
        border-radius: 16px;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .result-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .result-badge {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    /* Result Colors */
    .result-valid {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--accent);
        color: #34d399;
    }
    .result-valid .result-icon { color: var(--accent); }

    .result-already {
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid #f59e0b;
        color: #fbbf24;
    }
    .result-already .result-icon { color: #f59e0b; }

    .result-invalid {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        color: #f87171;
    }
    .result-invalid .result-icon { color: var(--danger); }

    .attendee-details {
        text-align: left;
        background: rgba(0,0,0,0.15);
        border-radius: 12px;
        padding: 20px;
        margin-top: 24px;
        border: 1px solid var(--border-color);
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.03);
        padding-bottom: 8px;
    }

    .detail-row:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }

    .detail-lbl {
        color: var(--text-muted);
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    .detail-val {
        font-weight: 600;
    }

    .result-empty {
        color: var(--text-muted);
        text-align: center;
        padding: 60px 20px;
        border: 1px dashed var(--border-color);
        border-radius: 16px;
    }

    .result-empty i {
        font-size: 3rem;
        margin-bottom: 16px;
        color: var(--text-muted);
        opacity: 0.5;
    }

    @media (max-width: 900px) {
        .verify-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="verify-grid">
    <!-- Left Panel: Scanner camera and manual form -->
    <div class="card-verify">
        <h3 class="panel-title"><i class="fa-solid fa-qrcode"></i> Entry QR Code Scanner</h3>
        
        <!-- Camera viewfinder container -->
        <div id="reader"></div>

        <form action="verify.php" method="POST" class="manual-form" id="verifyForm">
            <h4 class="panel-title" style="font-size: 1rem; margin-bottom: 12px;"><i class="fa-solid fa-keyboard"></i> Manual Passcode Check</h4>
            <label class="form-label" style="display: block; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">Enter token hash manually if camera is blocked or unreadable:</label>
            <input type="text" name="qr_token" id="qr_token" class="form-control" placeholder="e.g. 5f3c9e2b1..." required>
            <button type="submit" class="btn-verify-submit">Validate Passcode</button>
        </form>
    </div>

    <!-- Right Panel: Real-time scan feedback -->
    <div class="card-verify">
        <h3 class="panel-title"><i class="fa-solid fa-shield-halved"></i> Verification Feedback</h3>

        <?php if ($result_status === "VALID"): ?>
            <!-- Valid check-in pass -->
            <div class="result-box result-valid">
                <i class="fa-regular fa-circle-check result-icon"></i>
                <div class="result-badge">Valid Entry</div>
                <p>Welcome! Participant's attendance has been successfully logged.</p>
                
                <div class="attendee-details">
                    <div class="detail-row">
                        <span class="detail-lbl">Attendee</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-lbl">Email</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-lbl">Event</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['event']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-lbl">Checked In At</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['time']); ?></span>
                    </div>
                </div>
            </div>

        <?php elseif ($result_status === "ALREADY_PRESENT"): ?>
            <!-- Duplicate scan block -->
            <div class="result-box result-already">
                <i class="fa-solid fa-triangle-exclamation result-icon"></i>
                <div class="result-badge">Already Checked In</div>
                <p>Warning: This pass was already scanned for entry.</p>

                <div class="attendee-details">
                    <div class="detail-row">
                        <span class="detail-lbl">Attendee</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-lbl">Event</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['event']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-lbl">Original Scan</span>
                        <span class="detail-val"><?php echo htmlspecialchars($attendee_info['time']); ?></span>
                    </div>
                </div>
            </div>

        <?php elseif ($result_status === "INVALID"): ?>
            <!-- Fake or rejected ticket pass -->
            <div class="result-box result-invalid">
                <i class="fa-regular fa-circle-xmark result-icon"></i>
                <div class="result-badge">Invalid QR</div>
                <p>Access Denied! The scanned passcode is invalid or blocked.</p>
                <div style="font-size: 0.85rem; background: rgba(0,0,0,0.15); border-radius: 8px; padding: 12px; margin-top: 20px; border: 1px dashed rgba(239,68,68,0.3);">
                    <strong>Error Reason:</strong><br><?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Default idle state waiting for scans -->
            <div class="result-empty">
                <i class="fa-solid fa-video"></i>
                <h4>Awaiting Ticket Scan</h4>
                <p style="font-size: 0.85rem; margin-top: 4px;">Initialize camera stream to scan passes or write the passcode in manual form.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Initialize Camera Scanner stream
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning when code is found to prevent rapid duplicate submits
        html5QrcodeScanner.clear();
        
        // Populate manual form and submit automatically
        document.getElementById('qr_token').value = decodedText;
        document.getElementById('verifyForm').submit();
    }

    function onScanFailure(error) {
        // Failures happen constantly while camera searches for codes (can be ignored)
    }

    // Configure the Html5-QRCode scanner object
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { 
            fps: 10,                 // Frames parsed per second
            qrbox: {width: 250, height: 250}, // Scan square dimensions
            aspectRatio: 1.0
        },
        /* verbose= */ false
    );
    
    // Start scanning
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
</script>

<?php include '../includes/footer.php'; ?>
