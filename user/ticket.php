<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: ticket.php
// Purpose: Renders the printable event ticket and shows the unique QR code.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

// 1. Verify ticket registration ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid ticket request.";
    header("Location: dashboard.php");
    exit();
}

$reg_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$db = Database::getInstance();

try {
    // 2. Fetch registration details with SQL JOINs (User, Event, QR Code)
    $stmt = $db->prepare("
        SELECT r.id AS reg_id, r.user_id, r.status, r.registered_at,
               u.name AS attendee_name, u.email AS attendee_email, u.phone AS attendee_phone,
               e.title AS event_title, e.date AS event_date, e.time AS event_time, e.location AS event_location,
               q.qr_token, q.qr_image_path
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN events e ON r.event_id = e.id
        LEFT JOIN qr_codes q ON r.id = q.registration_id
        WHERE r.id = :reg_id
    ");
    $stmt->execute(['reg_id' => $reg_id]);
    $ticket = $stmt->fetch();

    // 3. Perform security validation: Ensure only owner OR admin can view
    if (!$ticket) {
        $_SESSION['error_message'] = "Ticket not found.";
        header("Location: dashboard.php");
        exit();
    }

    if ($ticket['user_id'] !== $user_id && $user_role !== 'admin') {
        $_SESSION['error_message'] = "Access denied. You do not own this ticket.";
        header("Location: dashboard.php");
        exit();
    }

    if ($ticket['status'] !== 'approved') {
        $_SESSION['error_message'] = "This ticket registration status is not approved.";
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database transaction failed: " . $e->getMessage());
}

$page_title = "Ticket Pass - " . $ticket['event_title'];
include '../includes/header.php';
?>

<style>
    .ticket-view-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px 0;
    }

    .back-nav {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        transition: color 0.2s ease;
    }

    .back-nav:hover {
        color: var(--text-main);
    }

    /* Print and download action buttons */
    .button-group {
        display: flex;
        gap: 16px;
        margin-bottom: 30px;
    }

    .btn-action-ticket {
        flex: 1;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-print {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .btn-print:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: var(--primary);
    }

    .btn-download {
        background: var(--primary);
        color: white;
        border: none;
        box-shadow: 0 4px 15px var(--primary-glow);
    }

    .btn-download:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    /* Outer Ticket Design */
    .physical-ticket {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        overflow: hidden;
        backdrop-filter: blur(15px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        position: relative;
    }

    /* Ticket notch cutouts */
    .physical-ticket::before, .physical-ticket::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        background-color: var(--bg-color);
        border-radius: 50%;
        bottom: 160px; /* Positioned right at the divider line */
        z-index: 5;
    }
    .physical-ticket::before { left: -12px; border-right: 1px solid var(--border-color); }
    .physical-ticket::after { right: -12px; border-left: 1px solid var(--border-color); }

    /* Upper part: Event Details */
    .ticket-main {
        padding: 40px;
    }

    .ticket-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        background: rgba(16, 185, 129, 0.15);
        color: var(--accent);
        border: 1px solid var(--accent);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 20px;
    }

    .ticket-event-title {
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 20px;
        line-height: 1.3;
        color: var(--text-main);
    }

    .ticket-grid-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .ticket-meta-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
        font-weight: 600;
    }

    .ticket-meta-val {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-main);
    }

    /* Lower part: User & QR Code */
    .ticket-stub {
        padding: 30px 40px;
        border-top: 2px dashed var(--border-color);
        background: rgba(255, 255, 255, 0.01);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .attendee-info h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .attendee-info p {
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .qr-holder {
        text-align: center;
        flex-shrink: 0;
    }

    .qr-holder img {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        background: white;
        padding: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    .qr-caption {
        font-size: 0.7rem;
        color: var(--text-muted);
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    @media (max-width: 550px) {
        .ticket-stub {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }
        .ticket-main {
            padding: 30px 20px;
        }
        .physical-ticket::before, .physical-ticket::after {
            bottom: 234px;
        }
    }

    /* Styles specifically when printing (Ctrl+P) */
    @media print {
        header, footer, .back-nav, .button-group {
            display: none !important;
        }
        body {
            background-color: white !important;
            color: black !important;
        }
        .ticket-view-container {
            margin: 0;
            padding: 0;
            max-width: 100%;
        }
        .physical-ticket {
            background: white !important;
            border: 2px solid #ccc !important;
            box-shadow: none !important;
            color: black !important;
            width: 100%;
        }
        .ticket-event-title, .ticket-meta-val, .attendee-info h4 {
            color: black !important;
        }
        .ticket-badge {
            background: none !important;
            border: 1px solid black !important;
            color: black !important;
        }
        .ticket-stub {
            border-top: 2px dashed #ccc !important;
            background: none !important;
        }
        .qr-holder img {
            border: 1px solid #ccc !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="ticket-view-container">
    <a href="dashboard.php" class="back-nav"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

    <!-- Action Shortcuts -->
    <div class="button-group">
        <button onclick="window.print();" class="btn-action-ticket btn-print">
            <i class="fa-solid fa-print"></i> Print Pass
        </button>
        <!-- Allows saving the QR image directly -->
        <a href="../<?php echo $ticket['qr_image_path']; ?>" download="Pass_<?php echo str_replace(' ', '_', $ticket['event_title']); ?>.png" class="btn-action-ticket btn-download">
            <i class="fa-solid fa-download"></i> Save QR Image
        </a>
    </div>

    <!-- Ticket Pass card -->
    <div class="physical-ticket">
        <div class="ticket-main">
            <span class="ticket-badge"><i class="fa-solid fa-circle-check"></i> Entry Ticket Approved</span>
            <h3 class="ticket-event-title"><?php echo htmlspecialchars($ticket['event_title']); ?></h3>

            <div class="ticket-grid-meta">
                <div>
                    <div class="ticket-meta-label">Date</div>
                    <div class="ticket-meta-val"><?php echo date('l, F d, Y', strtotime($ticket['event_date'])); ?></div>
                </div>
                <div>
                    <div class="ticket-meta-label">Time</div>
                    <div class="ticket-meta-val"><?php echo date('h:i A', strtotime($ticket['event_time'])); ?></div>
                </div>
                <div>
                    <div class="ticket-meta-label">Venue</div>
                    <div class="ticket-meta-val"><?php echo htmlspecialchars($ticket['event_location']); ?></div>
                </div>
                <div>
                    <div class="ticket-meta-label">Pass ID</div>
                    <div class="ticket-meta-val">#SE-<?php echo sprintf('%05d', $ticket['reg_id']); ?></div>
                </div>
            </div>
        </div>

        <div class="ticket-stub">
            <div class="attendee-info">
                <div class="ticket-meta-label">Attendee Name</div>
                <h4><?php echo htmlspecialchars($ticket['attendee_name']); ?></h4>
                <p><?php echo htmlspecialchars($ticket['attendee_email']); ?></p>
                <p style="margin-top: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($ticket['attendee_phone']); ?></p>
            </div>

            <div class="qr-holder">
                <!-- Displays the generated QR Code PNG file -->
                <img src="../<?php echo $ticket['qr_image_path']; ?>" alt="Ticket Entry QR Code">
                <div class="qr-caption">Scan to Verify</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
