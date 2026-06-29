<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: event_details.php
// Purpose: Displays the full description and detail variables of a single selected event.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

try {
    // 1. Fetch event information and aggregate registrations count
    $stmt = $db->prepare("
        SELECT e.*, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status != 'rejected'
        WHERE e.id = :id
        GROUP BY e.id
    ");
    $stmt->execute(['id' => $event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        $_SESSION['error_message'] = "Event not found.";
        header("Location: events.php");
        exit();
    }

    // 2. Fetch registration status for the active user
    $reg_stmt = $db->prepare("SELECT status, id AS reg_id FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
    $reg_stmt->execute([
        'user_id' => $user_id,
        'event_id' => $event_id
    ]);
    $registration = $reg_stmt->fetch();
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

$page_title = $event['title'] . " | SmartEntry";
include '../includes/header.php';

$capacity = intval($event['max_capacity']);
$registered = intval($event['registered_count']);
$tickets_left = $capacity - $registered;
?>

<style>
    .details-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px 0;
    }

    .back-link {
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        transition: color 0.3s ease;
    }

    .back-link:hover {
        color: var(--text-main);
    }

    .details-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 40px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .details-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 16px;
        color: var(--text-main);
        line-height: 1.3;
    }

    .info-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .info-bar-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .info-bar-item i {
        color: var(--primary);
        font-size: 1.1rem;
    }

    .description-box {
        color: rgba(248, 250, 252, 0.85);
        font-size: 1.05rem;
        line-height: 1.7;
        margin-bottom: 40px;
        white-space: pre-line; /* Preserves paragraphs and newlines from textarea */
    }

    .action-panel {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .action-status {
        font-size: 0.95rem;
    }

    .action-status span {
        font-weight: 700;
    }

    .btn-register {
        padding: 12px 28px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px var(--primary-glow);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-register:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .btn-ticket {
        padding: 12px 28px;
        background: rgba(16, 185, 129, 0.15);
        color: var(--accent);
        border: 1px solid var(--accent);
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-ticket:hover {
        background: rgba(16, 185, 129, 0.25);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-badge.approved {
        background: rgba(16, 185, 129, 0.1);
        color: var(--accent);
        border: 1px solid var(--accent);
    }

    .status-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
        border: 1px solid #f59e0b;
    }

    .status-badge.rejected {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid var(--danger);
    }
</style>

<div class="details-container">
    <a href="events.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>

    <div class="details-card">
        <h2 class="details-title"><?php echo htmlspecialchars($event['title']); ?></h2>

        <div class="info-bar">
            <div class="info-bar-item">
                <i class="fa-solid fa-calendar-days"></i>
                <span><?php echo date('l, M d, Y', strtotime($event['date'])); ?></span>
            </div>
            <div class="info-bar-item">
                <i class="fa-solid fa-clock"></i>
                <span><?php echo date('h:i A', strtotime($event['time'])); ?></span>
            </div>
            <div class="info-bar-item">
                <i class="fa-solid fa-location-dot"></i>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
        </div>

        <div class="description-box">
            <?php echo htmlspecialchars($event['description']); ?>
        </div>

        <div class="action-panel">
            <div class="action-status">
                <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Ticket Availability</p>
                <?php if ($tickets_left <= 0): ?>
                    <span style="color: var(--danger); font-weight: 600;"><i class="fa-solid fa-triangle-exclamation"></i> SOLD OUT</span>
                <?php else: ?>
                    <span style="color: var(--accent); font-weight: 600;"><?php echo $tickets_left; ?> spots remaining</span> (out of <?php echo $capacity; ?>)
                <?php endif; ?>
            </div>

            <div>
                <?php if ($registration): ?>
                    <!-- User is already registered -->
                    <?php if ($registration['status'] === 'approved'): ?>
                        <a href="ticket.php?id=<?php echo $registration['reg_id']; ?>" class="btn-ticket"><i class="fa-solid fa-ticket"></i> View Ticket Pass</a>
                    <?php elseif ($registration['status'] === 'pending'): ?>
                        <span class="status-badge pending"><i class="fa-solid fa-spinner fa-spin"></i> Registration Pending</span>
                    <?php else: ?>
                        <span class="status-badge rejected"><i class="fa-solid fa-circle-xmark"></i> Registration Rejected</span>
                    <?php endif; ?>
                <?php elseif ($tickets_left <= 0): ?>
                    <!-- Event is full and user is not registered -->
                    <span class="status-badge" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--border-color);"><i class="fa-solid fa-ban"></i> Event Capacity Reached</span>
                <?php else: ?>
                    <!-- Available to register -->
                    <a href="register_event.php?id=<?php echo $event_id; ?>" class="btn-register"><i class="fa-solid fa-user-plus"></i> Register for Event</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
