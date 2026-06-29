<?php
// Admin Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin
// File Name: reports.php
// Purpose: Provides comprehensive attendance check-in logs, filters, and reports for event managers.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure administrative privileges
require_admin();

$db = Database::getInstance();
$errors = [];
$success = "";

// 1. Fetch all events for the dropdown filter
try {
    $events_stmt = $db->query("SELECT id, title FROM events ORDER BY title ASC");
    $all_events = $events_stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Failed to load events list: " . $e->getMessage();
}

// 2. Collect filter criteria
$event_filter = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? intval($_GET['event_id']) : null;
$search = trim($_GET['search'] ?? '');

try {
    // 3. Build query to aggregate check-ins
    $query = "
        SELECT a.id AS att_id, a.marked_at,
               u.name AS attendee_name, u.email AS attendee_email, u.phone AS attendee_phone,
               e.title AS event_title,
               m.name AS marker_name
        FROM attendance a
        JOIN registrations r ON a.registration_id = r.id
        JOIN users u ON r.user_id = u.id
        JOIN events e ON r.event_id = e.id
        JOIN users m ON a.marked_by = m.id
        WHERE 1=1
    ";

    $params = [];

    // Filter by specific event
    if ($event_filter !== null) {
        $query .= " AND e.id = :event_id";
        $params['event_id'] = $event_filter;
    }

    // Filter by name/email search query
    if (!empty($search)) {
        $query .= " AND (u.name LIKE :search OR u.email LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY a.marked_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $checkins = $stmt->fetchAll();

    // 4. Calculate stats for the selected event if filtered
    $stats = null;
    if ($event_filter !== null) {
        // Fetch total registered vs present for stats card
        $stats_stmt = $db->prepare("
            SELECT 
                COUNT(r.id) AS total_registered,
                SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) AS total_approved,
                COUNT(a.id) AS total_present
            FROM registrations r
            LEFT JOIN attendance a ON r.id = a.registration_id
            WHERE r.event_id = :event_id
        ");
        $stats_stmt->execute(['event_id' => $event_filter]);
        $stats = $stats_stmt->fetch();
    }
} catch (PDOException $e) {
    $errors[] = "Failed to fetch attendance logs: " . $e->getMessage();
}

$page_title = "Attendance Reports | Admin Portal";
include '../includes/header.php';
?>

<style>
    .reports-header {
        margin-bottom: 30px;
    }

    .reports-title h2 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .reports-title p {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-top: 4px;
    }

    /* Filters Bar styling */
    .filter-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
    }

    .filter-form {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .filter-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .filter-control {
        padding: 10px 14px;
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .filter-control option {
        background: var(--bg-color);
        color: var(--text-main);
    }

    .btn-filter-submit {
        padding: 10px 20px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
        height: 42px;
    }

    .btn-filter-submit:hover {
        background: var(--primary-hover);
    }

    .btn-reset {
        padding: 10px 20px;
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        height: 42px;
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }

    .btn-reset:hover {
        border-color: var(--primary);
        color: var(--text-main);
    }

    /* Event Stats Cards */
    .event-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }

    .stat-card-mini {
        background: rgba(255,255,255,0.01);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }

    .stat-num {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .stat-lbl {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-top: 4px;
        letter-spacing: 0.05em;
    }

    /* Logs Table Grid */
    .table-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        overflow-x: auto;
        backdrop-filter: blur(10px);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    th {
        color: var(--text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }

    td {
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        font-size: 0.95rem;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-muted);
    }

    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        color: #f87171;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 24px;
    }
</style>

<div class="reports-header">
    <div class="reports-title">
        <h2>Attendance Logs &amp; Reports</h2>
        <p>Review check-in activity times, audit verify routes, and track capacity conversion rates.</p>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($errors)): ?>
    <div class="alert">
        <?php foreach ($errors as $error): ?>
            <div><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Filters Form Panel -->
<div class="filter-card">
    <form action="reports.php" method="GET" class="filter-form">
        <div class="filter-group" style="flex: 1.5;">
            <label class="filter-label" for="event_id">Filter by Event</label>
            <select name="event_id" id="event_id" class="filter-control">
                <option value="">-- All Events --</option>
                <?php foreach ($all_events as $ev): ?>
                    <option value="<?php echo $ev['id']; ?>" <?php echo $event_filter === intval($ev['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ev['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label" for="search">Attendee Search</label>
            <input type="text" name="search" id="search" class="filter-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-filter-submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
            <a href="reports.php" class="btn-reset">Reset</a>
        </div>
    </form>
</div>

<!-- Dynamic Event Stats Section -->
<?php if ($stats !== null): 
    $total_reg = intval($stats['total_registered']);
    $total_app = intval($stats['total_approved']);
    $total_pres = intval($stats['total_present']);
    $present_rate = $total_app > 0 ? round(($total_pres / $total_app) * 100) : 0;
?>
    <div class="event-stats">
        <div class="stat-card-mini">
            <div class="stat-num"><?php echo $total_reg; ?></div>
            <div class="stat-lbl">Total Registered</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-num"><?php echo $total_app; ?></div>
            <div class="stat-lbl">Approved Passes</div>
        </div>
        <div class="stat-card-mini" style="border-color: rgba(16, 185, 129, 0.3);">
            <div class="stat-num" style="color: var(--accent);"><?php echo $total_pres; ?></div>
            <div class="stat-lbl">Present (Checked In)</div>
        </div>
        <div class="stat-card-mini" style="border-color: rgba(99, 102, 241, 0.3);">
            <div class="stat-num" style="color: #818cf8;"><?php echo $present_rate; ?>%</div>
            <div class="stat-lbl">Show-up Rate</div>
        </div>
    </div>
<?php endif; ?>

<!-- Check-in Logs Table Grid -->
<div class="table-container">
    <h3 style="font-size: 1.15rem; margin-bottom: 20px; color: var(--text-main);"><i class="fa-solid fa-clock-rotate-left"></i> Check-in Log Registry</h3>
    
    <?php if (empty($checkins)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.5;"></i>
            <p>No check-in logs match your criteria.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Attendee</th>
                    <th>Email / Contact</th>
                    <th>Target Event</th>
                    <th>Check-in Date &amp; Time</th>
                    <th>Verified By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checkins as $log): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($log['attendee_name']); ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($log['attendee_email']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($log['attendee_phone']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($log['event_title']); ?></td>
                        <td style="font-weight: 500; color: var(--accent);">
                            <i class="fa-regular fa-calendar-check"></i> <?php echo date('M d, Y', strtotime($log['marked_at'])); ?> 
                            <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 6px; font-weight: 400;"><?php echo date('h:i A', strtotime($log['marked_at'])); ?></span>
                        </td>
                        <td style="font-size: 0.9rem; color: var(--text-muted);"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($log['marker_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
