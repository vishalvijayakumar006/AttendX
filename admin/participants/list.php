<?php
// Admin Participants Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin\participants
// File Name: list.php
// Purpose: Displays all registrants for a specific event, letting admins manage approvals and attendance.

require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

// Route security guard: Ensure administrative privileges
require_admin();

$db = Database::getInstance();
$errors = [];
$success = "";

// 1. Verify event ID is provided
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$event_id = intval($_GET['event_id']);

// Fetch event details to show at the top
try {
    $event_stmt = $db->prepare("SELECT title, date, time, location FROM events WHERE id = :id");
    $event_stmt->execute(['id' => $event_id]);
    $event = $event_stmt->fetch();
    
    if (!$event) {
        $_SESSION['error_message'] = "Event not found.";
        header("Location: ../dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 2. Handle status updates (Approve / Reject) and manual attendance logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reg_id = intval($_POST['registration_id'] ?? 0);
    $admin_id = $_SESSION['user_id'];

    if ($reg_id > 0) {
        try {
            if ($action === 'approve') {
                // Update status to approved
                $stmt = $db->prepare("UPDATE registrations SET status = 'approved' WHERE id = :id");
                $stmt->execute(['id' => $reg_id]);
                $success = "Registration has been approved!";
            } elseif ($action === 'reject') {
                // Update status to rejected and remove from attendance logs if they were checked in
                $db->beginTransaction();
                
                $stmt = $db->prepare("UPDATE registrations SET status = 'rejected' WHERE id = :id");
                $stmt->execute(['id' => $reg_id]);
                
                $del_stmt = $db->prepare("DELETE FROM attendance WHERE registration_id = :reg_id");
                $del_stmt->execute(['reg_id' => $reg_id]);

                $db->commit();
                $success = "Registration has been rejected.";
            } elseif ($action === 'mark_present') {
                // Verify the registration is approved first
                $check_stmt = $db->prepare("SELECT status FROM registrations WHERE id = :id");
                $check_stmt->execute(['id' => $reg_id]);
                $reg_status = $check_stmt->fetchColumn();

                if ($reg_status !== 'approved') {
                    $errors[] = "Cannot mark attendance. Registration must be approved first.";
                } else {
                    // Check if already present
                    $att_stmt = $db->prepare("SELECT id FROM attendance WHERE registration_id = :reg_id");
                    $att_stmt->execute(['reg_id' => $reg_id]);
                    
                    if ($att_stmt->rowCount() > 0) {
                        $errors[] = "Attendee is already marked present.";
                    } else {
                        // Mark present
                        $ins_stmt = $db->prepare("INSERT INTO attendance (registration_id, marked_by) VALUES (:reg_id, :admin_id)");
                        $ins_stmt->execute([
                            'reg_id' => $reg_id,
                            'admin_id' => $admin_id
                        ]);
                        $success = "Participant checked in successfully!";
                    }
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
}

// 3. Collect participants filter searches
$search = trim($_GET['search'] ?? '');

try {
    // Select all registrants, check-in records, and user details
    $query = "
        SELECT r.id AS reg_id, r.status, r.registered_at,
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               a.marked_at, a.id AS att_id
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN attendance a ON r.id = a.registration_id
        WHERE r.event_id = :event_id
    ";

    // Append search constraints if query exists
    if (!empty($search)) {
        $query .= " AND (u.name LIKE :search OR u.email LIKE :search)";
    }
    
    $query .= " ORDER BY u.name ASC";
    
    $stmt = $db->prepare($query);
    $params = ['event_id' => $event_id];
    if (!empty($search)) {
        $params['search'] = '%' . $search . '%';
    }
    $stmt->execute($params);
    $participants = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Failed to load participants: " . $e->getMessage();
}

$page_title = "Manage Participants - " . $event['title'];
include '../../includes/header.php';
?>

<style>
    .back-btn {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        transition: color 0.2s ease;
    }

    .back-btn:hover {
        color: var(--text-main);
    }

    .header-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .header-details h2 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .header-details p {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-top: 4px;
    }

    .search-bar {
        display: flex;
        gap: 12px;
        max-width: 400px;
        width: 100%;
    }

    .search-input {
        flex: 1;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary);
    }

    .btn-search {
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-main);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-search:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: var(--primary);
    }

    /* Grid container for table */
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

    /* Badges */
    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: inline-block;
    }

    .badge-approved { background: rgba(16, 185, 129, 0.15); color: var(--accent); }
    .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-rejected { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

    .attendance-indicator {
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .indicator-present { color: var(--accent); }
    .indicator-absent { color: var(--text-muted); }

    /* Action triggers */
    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-row-action {
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-approve { background: rgba(16, 185, 129, 0.1); color: var(--accent); border: 1px solid rgba(16, 185, 129, 0.2); }
    .btn-approve:hover { background: var(--accent); color: white; }

    .btn-reject { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
    .btn-reject:hover { background: var(--danger); color: white; }

    .btn-mark { background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.2); }
    .btn-mark:hover { background: var(--primary); color: white; }

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
</style>

<a href="../dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

<div class="header-group">
    <div class="header-details">
        <h2>Participants Management</h2>
        <p><?php echo htmlspecialchars($event['title']); ?> &bull; <?php echo date('M d, Y', strtotime($event['date'])); ?></p>
    </div>
    
    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <form action="list.php" method="GET" class="search-bar" style="margin-bottom: 0;">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">Search</button>
        </form>
        <a href="export.php?event_id=<?php echo $event_id; ?>" class="btn-search" style="background: var(--primary); border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; color: white; box-shadow: 0 4px 12px var(--primary-glow); height: 42px;"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
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

<div class="table-container">
    <?php if (empty($participants)): ?>
        <div class="empty-state">
            <p>No participants registered for this event yet.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Attendee Info</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th>Manage Status</th>
                    <th>Mark Attendance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $row): 
                    $reg_id = intval($row['reg_id']);
                    $status = $row['status'];
                    $is_present = ($row['att_id'] !== null);
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['user_name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['user_email']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['user_phone']); ?></td>
                        <td>
                            <?php if ($status === 'approved'): ?>
                                <span class="badge-status badge-approved">Approved</span>
                            <?php elseif ($status === 'pending'): ?>
                                <span class="badge-status badge-pending">Pending</span>
                            <?php else: ?>
                                <span class="badge-status badge-rejected">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_present): ?>
                                <span class="attendance-indicator indicator-present">
                                    <i class="fa-solid fa-circle-check"></i> Present
                                </span>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                    <?php echo date('h:i A', strtotime($row['marked_at'])); ?>
                                </div>
                            <?php else: ?>
                                <span class="attendance-indicator indicator-absent">
                                    <i class="fa-regular fa-circle"></i> Absent
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($status !== 'approved'): ?>
                                    <form action="list.php?event_id=<?php echo $event_id; ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg_id; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-row-action btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($status !== 'rejected'): ?>
                                    <form action="list.php?event_id=<?php echo $event_id; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this registration? It will remove any check-in data.');">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg_id; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-row-action btn-reject"><i class="fa-solid fa-xmark"></i> Reject</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($status === 'approved' && !$is_present): ?>
                                <form action="list.php?event_id=<?php echo $event_id; ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="registration_id" value="<?php echo $reg_id; ?>">
                                    <input type="hidden" name="action" value="mark_present">
                                    <button type="submit" class="btn-row-action btn-mark"><i class="fa-solid fa-user-check"></i> Mark Present</button>
                                </form>
                            <?php elseif ($status !== 'approved'): ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Approve registration first</span>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--accent); font-weight: 600;">Checked In</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
