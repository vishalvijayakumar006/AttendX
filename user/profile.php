<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: profile.php
// Purpose: Allows logged-in users or admins to update their personal details and passwords.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

$errors = [];
$success = "";
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

// 1. Fetch current profile values
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("User profile not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 2. Process updates on form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    if (empty($name)) {
        $errors[] = "Name cannot be empty.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number cannot be empty.";
    }

    // Password validation (only if they are attempting to change it)
    $password_changed = false;
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Confirm password does not match new password.";
        } else {
            $password_changed = true;
        }
    }

    if (empty($errors)) {
        try {
            if ($password_changed) {
                // Hash and update everything including the password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $db->prepare("UPDATE users SET name = :name, phone = :phone, password = :password WHERE id = :id");
                $update_stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'password' => $hashed_password,
                    'id' => $user_id
                ]);
            } else {
                // Update only basic profile info
                $update_stmt = $db->prepare("UPDATE users SET name = :name, phone = :phone WHERE id = :id");
                $update_stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'id' => $user_id
                ]);
            }

            // Sync active session display name
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
            
            // Refresh local user variables to display updated values
            $user['name'] = $name;
            $user['phone'] = $phone;
        } catch (PDOException $e) {
            $errors[] = "Database update failed: " . $e->getMessage();
        }
    }
}

$page_title = "My Profile | SmartEntry";
include '../includes/header.php';
?>

<style>
    .profile-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px 0;
    }

    .profile-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 40px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .profile-title {
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

    .profile-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
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

    .form-control:disabled {
        background: rgba(255, 255, 255, 0.01);
        color: rgba(255, 255, 255, 0.3);
        cursor: not-allowed;
    }

    .btn-save {
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

    .btn-save:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
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

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--accent);
        color: #34d399;
    }

    .password-section {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .badge-role {
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 6px;
        background: rgba(99, 102, 241, 0.2);
        color: #818cf8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        display: inline-block;
        vertical-align: middle;
    }
</style>

<div class="profile-container">
    <div class="profile-card">
        <h2 class="profile-title">
            <i class="fa-solid fa-user-gear"></i> Profile Settings
            <span class="badge-role"><?php echo htmlspecialchars($user['role']); ?></span>
        </h2>
        <p class="profile-subtitle">Update your personal account information and login password.</p>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address (Cannot be changed)</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <div class="password-section">
                <h3 class="form-label" style="font-size: 0.95rem; color: var(--text-main); margin-bottom: 4px;">Change Password</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 16px;">Leave these fields blank if you do not want to change your password.</p>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
