<?php
// Admin Events Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin\events
// File Name: create.php
// Purpose: Allows event organizers to create a new event.

require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

// Route security guard: Ensure only Admin can access
require_admin();

$errors = [];
$success = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = trim($_POST['location']);
    $max_capacity = intval($_POST['max_capacity']);
    $admin_id = $_SESSION['user_id'];

    // Validations
    if (empty($title)) {
        $errors[] = "Event title is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Event description is required.";
    }
    
    if (empty($date)) {
        $errors[] = "Event date is required.";
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Event date cannot be in the past.";
    }

    if (empty($time)) {
        $errors[] = "Event start time is required.";
    }

    if (empty($location)) {
        $errors[] = "Event location/venue is required.";
    }

    if ($max_capacity <= 0) {
        $errors[] = "Maximum capacity must be a positive integer greater than zero.";
    }

    // Insert into database if validation passes
    if (empty($errors)) {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("INSERT INTO events (title, description, date, time, location, max_capacity, created_by) VALUES (:title, :description, :date, :time, :location, :max_capacity, :created_by)");
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'date' => $date,
                'time' => $time,
                'location' => $location,
                'max_capacity' => $max_capacity,
                'created_by' => $admin_id
            ]);

            $_SESSION['success_message'] = "Event '$title' has been created successfully!";
            header("Location: ../dashboard.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to create event: " . $e->getMessage();
        }
    }
}

$page_title = "Create Event | Admin Portal";
include '../../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 700px;
        margin: 0 auto;
        padding: 20px 0;
    }

    .card-admin {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 40px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .admin-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .admin-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-row {
        display: flex;
        gap: 20px;
    }

    .form-row .form-group {
        flex: 1;
    }

    .form-label {
        display: block;
        color: var(--text-muted);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-main);
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.15);
        background: rgba(255, 255, 255, 0.04);
    }

    .textarea-control {
        resize: vertical;
        min-height: 120px;
    }

    .btn-admin-submit {
        padding: 12px 24px;
        background: var(--primary);
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px var(--primary-glow);
    }

    .btn-admin-submit:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .btn-cancel {
        padding: 12px 24px;
        background: transparent;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-muted);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.03);
        color: var(--text-main);
    }

    .alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        color: #f87171;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .alert ul {
        list-style-type: none;
    }

    .action-group {
        display: flex;
        gap: 16px;
        margin-top: 30px;
    }

    @media (max-width: 600px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<div class="admin-container">
    <div class="card-admin">
        <h2 class="admin-title"><i class="fa-solid fa-calendar-plus"></i> Create New Event</h2>
        <p class="admin-subtitle">Publish a new event details, location capacity, and time slots for registrations.</p>

        <!-- Error Panel -->
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="title">Event Title</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. National Level Web Hackathon 2026" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Event Description</label>
                <textarea id="description" name="description" class="form-control textarea-control" placeholder="Describe what the event is about, guidelines, speakers, prizes, etc..." required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo isset($date) ? htmlspecialchars($date) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="time">Start Time</label>
                    <input type="time" id="time" name="time" class="form-control" value="<?php echo isset($time) ? htmlspecialchars($time) : ''; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="location">Venue / Location</label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Seminar Hall A, Tech Campus" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="max_capacity">Max Ticket Capacity</label>
                    <input type="number" id="max_capacity" name="max_capacity" class="form-control" placeholder="e.g. 150" value="<?php echo isset($max_capacity) ? htmlspecialchars($max_capacity) : ''; ?>" required>
                </div>
            </div>

            <div class="action-group">
                <button type="submit" class="btn-admin-submit"><i class="fa-solid fa-cloud-arrow-up"></i> Publish Event</button>
                <a href="../dashboard.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/header.php'; ?>
