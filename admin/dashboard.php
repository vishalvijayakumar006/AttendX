<?php
// Admin Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin
// File Name: dashboard.php
// Purpose: Primary landing page for system administrators. Contains metrics, data charts, and event management tools.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure administrative privileges
require_admin();

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

// 1. Fetch system statistics
$stats = [
    'users' => 0,
    'events' => 0,
    'registrations' => 0,
    'attendance' => 0
];

$checked_in_count = 0;
$approved_absent_count = 0;

try {
    // Total users count
    $stmt = $db->query("SELECT COUNT(id) AS total FROM users WHERE role = 'user'");
    $stats['users'] = $stmt->fetch()['total'];

    // Total events count
    $stmt = $db->query("SELECT COUNT(id) AS total FROM events");
    $stats['events'] = $stmt->fetch()['total'];

    // Total registrations count
    $stmt = $db->query("SELECT COUNT(id) AS total FROM registrations");
    $stats['registrations'] = $stmt->fetch()['total'];

    // Total attendance check-ins count
    $stmt = $db->query("SELECT COUNT(id) AS total FROM attendance");
    $stats['attendance'] = $stmt->fetch()['total'];

    // 2. Fetch all events for management grid with their registration counts
    $events_stmt = $db->query("
        SELECT e.*, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status != 'rejected'
        GROUP BY e.id 
        ORDER BY e.date ASC, e.time ASC
    ");
    $events = $events_stmt->fetchAll();

    // 3. Fetch attendance ratio metrics for Doughnut Chart
    $ratio_stmt = $db->query("
        SELECT 
            SUM(CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END) AS checked_in,
            SUM(CASE WHEN a.id IS NULL AND r.status = 'approved' THEN 1 ELSE 0 END) AS approved_absent
        FROM registrations r
        LEFT JOIN attendance a ON r.id = a.registration_id
    ");
    $ratio = $ratio_stmt->fetch();
    $checked_in_count = intval($ratio['checked_in'] ?? 0);
    $approved_absent_count = intval($ratio['approved_absent'] ?? 0);

} catch (PDOException $e) {
    $errors[] = "Failed to fetch dashboard data: " . $e->getMessage();
}

$page_title = "Admin Dashboard | SmartEntry";
include '../includes/header.php';
?>

<!-- Include Chart.js library from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .admin-title-group h2 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .admin-title-group p {
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    .btn-create {
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

    .btn-create:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    /* Stats Grid layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        backdrop-filter: blur(10px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.5rem;
    }

    .stat-card:nth-child(2) .stat-icon { background: rgba(168, 85, 247, 0.1); color: var(--secondary); }
    .stat-card:nth-child(3) .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--accent); }
    .stat-card:nth-child(4) .stat-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

    .stat-details h3 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1.2;
    }

    .stat-details p {
        color: var(--text-muted);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Charts Grid layout */
    .charts-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }

    .chart-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        backdrop-filter: blur(10px);
    }

    .chart-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Events Management Table */
    .table-container {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        overflow-x: auto;
        backdrop-filter: blur(10px);
    }

    .table-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
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
        color: rgba(248, 250, 252, 0.9);
    }

    tr:last-child td {
        border-bottom: none;
    }

    .actions-cell {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-edit { background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.2); }
    .btn-edit:hover { background: var(--primary); color: white; }

    .btn-delete { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
    .btn-delete:hover { background: var(--danger); color: white; }

    .btn-view-participants {
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 0.8rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-view-participants:hover {
        background: rgba(255, 255, 255, 0.08);
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

    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }

    @media (max-width: 900px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-header">
    <div class="admin-title-group">
        <h2>Organizer Console</h2>
        <p>Overview event registration stats, publish updates, and log entries.</p>
    </div>
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="verify.php" class="btn-create" style="background: var(--accent); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);"><i class="fa-solid fa-camera"></i> Verify Entry (QR)</a>
        <a href="events/create.php" class="btn-create"><i class="fa-solid fa-calendar-plus"></i> Create Event</a>
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

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- System Statistics Summary -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['users']; ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['events']; ?></h3>
            <p>Total Events</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['registrations']; ?></h3>
            <p>Registrations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-user"></i></div>
        <div class="stat-details">
            <h3><?php echo $stats['attendance']; ?></h3>
            <p>Check-ins</p>
        </div>
    </div>
</div>

<!-- Interactive Analytics Charts Grid -->
<div class="charts-grid">
    <div class="chart-card">
        <h3 class="chart-title"><i class="fa-solid fa-chart-column"></i> Registration Density</h3>
        <div style="position: relative; height: 280px; width: 100%;">
            <canvas id="registrationsChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3 class="chart-title"><i class="fa-solid fa-chart-pie"></i> Show-up Conversion</h3>
        <div style="position: relative; height: 280px; width: 100%; display: flex; justify-content: center;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</div>

<!-- Events Table -->
<div class="table-container">
    <h3 class="table-title"><i class="fa-solid fa-list-check"></i> Manage Published Events</h3>
    
    <?php if (empty($events)): ?>
        <div class="empty-state">
            <p>No events published yet. Click "Create Event" to publish your first one!</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Event Details</th>
                    <th>Date &amp; Time</th>
                    <th>Location</th>
                    <th>Bookings</th>
                    <th>Participants</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $row): 
                    $capacity = intval($row['max_capacity']);
                    $registered = intval($row['registered_count']);
                ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td>
                            <div><?php echo date('M d, Y', strtotime($row['date'])); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($row['time'])); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td>
                            <div><?php echo $registered; ?> / <?php echo $capacity; ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo ($capacity - $registered) <= 0 ? 'SOLD OUT' : ($capacity - $registered) . ' spots left'; ?>
                            </div>
                        </td>
                        <td>
                            <!-- Shortcut to view participant list (Module 11) -->
                            <a href="participants/list.php?event_id=<?php echo $row['id']; ?>" class="btn-view-participants">
                                <i class="fa-solid fa-users-viewfinder"></i> View List
                            </a>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <a href="events/edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit" title="Edit Event">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="events/delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this event? This will delete all registrations &amp; tickets for this event.');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Delete Event">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    // 1. Data arrays passed securely from PHP backend to frontend JavaScript
    const eventTitles = <?php echo json_encode(array_column($events, 'title')); ?>;
    const regCounts = <?php echo json_encode(array_map('intval', array_column($events, 'registered_count'))); ?>;
    
    const checkedInCount = <?php echo $checked_in_count; ?>;
    const approvedAbsentCount = <?php echo $approved_absent_count; ?>;

    // 2. Render Registrations Density (Bar Chart)
    const ctxReg = document.getElementById('registrationsChart').getContext('2d');
    new Chart(ctxReg, {
        type: 'bar',
        data: {
            labels: eventTitles,
            datasets: [{
                label: 'Registered Attendees',
                data: regCounts,
                backgroundColor: 'rgba(99, 102, 241, 0.4)',
                borderColor: '#6366f1',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(99, 102, 241, 0.7)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8', stepSize: 1, beginAtZero: true }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });

    // 3. Render Show-up Conversion (Doughnut Chart)
    const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctxAtt, {
        type: 'doughnut',
        data: {
            labels: ['Checked In (Present)', 'Absent'],
            datasets: [{
                data: [checkedInCount, approvedAbsentCount],
                backgroundColor: ['rgba(16, 185, 129, 0.6)', 'rgba(239, 68, 68, 0.4)'],
                borderColor: ['#10b981', '#ef4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#94a3b8', padding: 15, font: { family: 'Outfit' } }
                }
            },
            cutout: '75%'
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
