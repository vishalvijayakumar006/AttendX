<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: events.php
// Purpose: Displays all available events for attendees to view and register.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Capture flash messages from redirects
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errors[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

try {
    // Collect search query keyword
    $search = trim($_GET['search'] ?? '');

    // 1. Get all events and aggregate registered counts, supporting filter searches
    $events_query = "
        SELECT e.*, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status != 'rejected'
    ";
    
    $params = [];
    if (!empty($search)) {
        $events_query .= " WHERE e.title LIKE :search OR e.location LIKE :search";
        $params['search'] = '%' . $search . '%';
    }
    
    $events_query .= " GROUP BY e.id ORDER BY e.date ASC, e.time ASC";
    
    $events_stmt = $db->prepare($events_query);
    $events_stmt->execute($params);
    $events = $events_stmt->fetchAll();

    // 2. Fetch the current user's registration statuses to toggle buttons
    $reg_query = "SELECT event_id, status, id AS reg_id FROM registrations WHERE user_id = :user_id";
    $reg_stmt = $db->prepare($reg_query);
    $reg_stmt->execute(['user_id' => $user_id]);
    $my_registrations = [];
    while ($row = $reg_stmt->fetch()) {
        $my_registrations[$row['event_id']] = [
            'status' => $row['status'],
            'reg_id' => $row['reg_id']
        ];
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load events: " . $e->getMessage();
}

$page_title = "Find Events | SmartEntry";
include '../includes/header.php';
?>

<style>
    .events-header {
        margin-bottom: 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .events-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .events-subtitle {
        color: var(--text-muted);
        margin-top: 4px;
    }

    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    .event-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        min-height: 300px;
    }

    .event-card:hover {
        transform: translateY(-5px);
        border-color: rgba(99, 102, 241, 0.3);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .event-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--text-main);
    }

    .event-description {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.6;
        flex-grow: 1;
        /* Limit to 3 lines with ellipsis */
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .event-meta {
        list-style: none;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .event-meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-bottom: 8px;
    }

    .event-meta-item i {
        color: var(--primary);
        width: 16px;
    }

    .card-footer {
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

    .badge-approved {
        background: rgba(16, 185, 129, 0.15);
        color: var(--accent);
    }

    .badge-pending {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .badge-rejected {
        background: rgba(239, 68, 68, 0.15);
        color: var(--danger);
    }

    .badge-full {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
    }

    .btn-register {
        padding: 10px 16px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-register:hover {
        background: var(--primary-hover);
    }

    .btn-view-ticket {
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-view-ticket:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: var(--primary);
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

    .no-events {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: var(--bg-card);
        border: 1px dashed var(--border-color);
        border-radius: 20px;
        color: var(--text-muted);
    }

    .no-events i {
        font-size: 3rem;
        margin-bottom: 16px;
        color: var(--primary);
    }
</style>

<div class="events-header">
    <div>
        <h2 class="events-title">Discover Events</h2>
        <p class="events-subtitle">Browse through available meetups, webinars, workshops, and hackathons.</p>
    </div>
    
    <form action="events.php" method="GET" style="display: flex; gap: 12px; max-width: 400px; width: 100%;">
        <input type="text" name="search" style="flex: 1; padding: 10px 16px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.9rem;" placeholder="Search by title or venue..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" style="padding: 10px 20px; background: rgba(255, 255, 255, 0.05); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">Search</button>
    </form>
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

<div class="events-grid">
    <?php if (empty($events)): ?>
        <div class="no-events">
            <i class="fa-solid fa-calendar-xmark"></i>
            <h3>No Events Found</h3>
            <p>Check back later! Organizers haven't published any events yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): 
            $event_id = $event['id'];
            $capacity = intval($event['max_capacity']);
            $registered = intval($event['registered_count']);
            $tickets_left = $capacity - $registered;
            
            // Check current user status
            $is_registered = isset($my_registrations[$event_id]);
            $reg_status = $is_registered ? $my_registrations[$event_id]['status'] : null;
            $reg_id = $is_registered ? $my_registrations[$event_id]['reg_id'] : null;
        ?>
            <div class="event-card">
                <div>
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                </div>
                
                <div>
                    <ul class="event-meta">
                        <li class="event-meta-item">
                            <i class="fa-solid fa-calendar"></i>
                            <span><?php echo date('M d, Y', strtotime($event['date'])); ?></span>
                        </li>
                        <li class="event-meta-item">
                            <i class="fa-solid fa-clock"></i>
                            <span><?php echo date('h:i A', strtotime($event['time'])); ?></span>
                        </li>
                        <li class="event-meta-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                        </li>
                        <li class="event-meta-item">
                            <i class="fa-solid fa-users"></i>
                            <span>
                                <?php 
                                    if ($tickets_left <= 0) {
                                        echo "SOLD OUT (" . $capacity . " max)";
                                    } else {
                                        echo $tickets_left . " seats left (out of " . $capacity . ")";
                                    }
                                ?>
                            </span>
                        </li>
                    </ul>
                    
                    <div class="card-footer">
                        <div>
                            <?php if ($is_registered): ?>
                                <?php if ($reg_status === 'approved'): ?>
                                    <span class="badge-status badge-approved"><i class="fa-solid fa-circle-check"></i> Approved</span>
                                <?php elseif ($reg_status === 'pending'): ?>
                                    <span class="badge-status badge-pending"><i class="fa-solid fa-spinner fa-spin"></i> Pending</span>
                                <?php else: ?>
                                    <span class="badge-status badge-rejected"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                                <?php endif; ?>
                            <?php elseif ($tickets_left <= 0): ?>
                                <span class="badge-status badge-full"><i class="fa-solid fa-ban"></i> Filled</span>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($is_registered): ?>
                                <?php if ($reg_status === 'approved'): ?>
                                    <a href="ticket.php?id=<?php echo $reg_id; ?>" class="btn-view-ticket"><i class="fa-solid fa-ticket"></i> View Pass</a>
                                <?php endif; ?>
                            <?php elseif ($tickets_left > 0): ?>
                                <!-- Link to registration action (Module 6) -->
                                <a href="register_event.php?id=<?php echo $event_id; ?>" class="btn-register"><i class="fa-solid fa-user-plus"></i> Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
