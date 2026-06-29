<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: dashboard.php
// Purpose: Main dashboard landing page for logged-in attendees.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$db = Database::getInstance();
$errors = [];
$success = "";

// Capture flash messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errors[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

try {
    // Fetch events registered by this user, joining attendance check-in status (Module 9 integration)
    $stmt = $db->prepare("
        SELECT r.id AS reg_id, r.status, r.registered_at,
               e.id AS event_id, e.title, e.date, e.time, e.location,
               a.id AS att_id, a.marked_at
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        LEFT JOIN attendance a ON r.id = a.registration_id
        WHERE r.user_id = :user_id
        ORDER BY e.date ASC, e.time ASC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $my_events = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Failed to load registered events: " . $e->getMessage();
}

$page_title = "Dashboard | SmartEntry";
include '../includes/header.php';
?>

<style>
    .welcome-banner {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 40px;
        margin-bottom: 32px;
        backdrop-filter: blur(10px);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .welcome-text h2 {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #a855f7, #6366f1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .welcome-text p {
        color: var(--text-muted);
        font-size: 1.05rem;
    }

    .btn-discover {
        padding: 12px 24px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px var(--primary-glow);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-discover:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 24px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tickets-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
    }

    .ticket-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Ticket edge cutout aesthetics for premium styling */
    .ticket-card::before, .ticket-card::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        background-color: var(--bg-color);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
    }
    .ticket-card::before { left: -8px; border-right: 1px solid var(--border-color); }
    .ticket-card::after { right: -8px; border-left: 1px solid var(--border-color); }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px dashed var(--border-color);
    }

    .ticket-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        padding-right: 16px;
    }

    .ticket-body {
        margin-bottom: 20px;
    }

    .ticket-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 6px;
    }

    .ticket-info-item i {
        color: var(--primary);
        width: 14px;
    }

    .ticket-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-approved { background: rgba(16, 185, 129, 0.15); color: var(--accent); }
    .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-rejected { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

    .btn-ticket-pass {
        padding: 8px 16px;
        background: rgba(99, 102, 241, 0.1);
        color: var(--text-main);
        border: 1px solid var(--primary);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-ticket-pass:hover {
        background: var(--primary);
        color: white;
    }

    .no-tickets {
        text-align: center;
        padding: 50px 20px;
        background: var(--bg-card);
        border: 1px dashed var(--border-color);
        border-radius: 20px;
        grid-column: 1 / -1;
    }

    .no-tickets i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 12px;
    }

    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        color: #f87171;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 24px;
        font-size: 0.9rem;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--accent);
        color: #34d399;
    }
</style>

<div class="welcome-banner">
    <div class="welcome-text">
        <h2>Hello, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>Welcome to your event portal. View your entry passes and find upcoming hackathons or workshops.</p>
    </div>
    <a href="events.php" class="btn-discover"><i class="fa-solid fa-compass"></i> Discover Events</a>
</div>

<!-- Alerts -->
<?php if (!empty($errors)): ?>
    <div class="alert">
        <?php foreach ($errors as $error): ?>
            <div><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<h3 class="section-title"><i class="fa-solid fa-ticket-simple"></i> My Registered Events</h3>

<div class="tickets-container">
    <?php if (empty($my_events)): ?>
        <div class="no-tickets">
            <i class="fa-solid fa-receipt"></i>
            <h4>No Registrations Found</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">You haven't registered for any events yet. Click "Discover Events" to get started!</p>
        </div>
    <?php else: ?>
        <?php foreach ($my_events as $reg): 
            $status = $reg['status'];
            $reg_id = $reg['reg_id'];
        ?>
            <div class="ticket-card">
                <div>
                    <div class="ticket-header">
                        <div class="ticket-title"><?php echo htmlspecialchars($reg['title']); ?></div>
                    </div>
                    
                    <div class="ticket-body">
                        <div class="ticket-info-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span><?php echo date('M d, Y', strtotime($reg['date'])); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <i class="fa-solid fa-clock"></i>
                            <span><?php echo date('h:i A', strtotime($reg['time'])); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($reg['location']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="ticket-footer">
                    <div>
                        <?php if ($reg['att_id'] !== null): ?>
                            <span class="badge-status badge-approved" style="background: rgba(16, 185, 129, 0.25);"><i class="fa-solid fa-circle-check"></i> Present</span>
                        <?php elseif ($status === 'approved'): ?>
                            <span class="badge-status badge-approved">Approved</span>
                        <?php elseif ($status === 'pending'): ?>
                            <span class="badge-status badge-pending">Pending</span>
                        <?php else: ?>
                            <span class="badge-status badge-rejected">Rejected</span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if ($reg['att_id'] !== null): ?>
                            <span style="font-size: 0.85rem; color: var(--accent); font-weight: 600;"><i class="fa-solid fa-check-double"></i> Checked In</span>
                        <?php elseif ($status === 'approved'): ?>
                            <a href="ticket.php?id=<?php echo $reg_id; ?>" class="btn-ticket-pass"><i class="fa-solid fa-qrcode"></i> View Pass</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
