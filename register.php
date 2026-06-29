<?php
// Root Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry
// File Name: register.php
// Purpose: Allows new users and test admins to create accounts.

require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Redirect if already logged in
redirect_if_logged_in();

$errors = [];
$success = "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and collect inputs (strips unnecessary whitespace)
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'user' or 'admin'

    // 2. Validate inputs
    if (empty($name)) {
        $errors[] = "Full name is required.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if ($role !== 'user' && $role !== 'admin') {
        $errors[] = "Invalid account role selected.";
    }

    // Secret Key validation to protect admin registration from public exploits
    if ($role === 'admin') {
        $admin_secret = $_POST['admin_secret'] ?? '';
        if ($admin_secret !== 'AdminEntryPass2026') {
            $errors[] = "Invalid Admin Secret Access Key. You cannot register as an Organizer without the key.";
        }
    }

    // 3. Database operation (if no validation errors)
    if (empty($errors)) {
        $db = Database::getInstance();

        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email is already registered. Try logging in.";
            } else {
                // Hash the password securely using Bcrypt (default algorithm in PHP)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user into database
                $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (:name, :email, :password, :phone, :role)");
                $insert_stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'phone' => $phone,
                    'role' => $role
                ]);

                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Register | SmartEntry";
include 'includes/header.php';
?>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px 0;
        min-height: calc(100vh - 180px);
    }

    .auth-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 500px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .auth-title {
        font-size: 1.8rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 24px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
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

    .role-selector {
        display: flex;
        gap: 16px;
        margin-top: 4px;
    }

    .role-option {
        flex: 1;
        position: relative;
    }

    .role-option input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .role-label {
        display: block;
        text-align: center;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        padding: 12px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        color: var(--text-muted);
    }

    .role-option input:checked ~ .role-label {
        border-color: var(--primary);
        background: rgba(99, 102, 241, 0.1);
        color: var(--text-main);
    }

    .btn-auth {
        width: 100%;
        padding: 14px;
        background: var(--primary);
        border: none;
        border-radius: 10px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
        box-shadow: 0 4px 15px var(--primary-glow);
    }

    .btn-auth:hover {
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

    .auth-footer {
        text-align: center;
        margin-top: 24px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .auth-footer a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">Create an Account</h2>

        <!-- Error Alert Panel -->
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="9876543210" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Register As</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" id="role_user" name="role" value="user" checked>
                        <label for="role_user" class="role-label"><i class="fa-solid fa-user"></i> Attendee</label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="role_admin" name="role" value="admin">
                        <label for="role_admin" class="role-label"><i class="fa-solid fa-user-shield"></i> Organizer</label>
                    </div>
                </div>
            </div>

            <div class="form-group" id="admin_secret_group" style="display: none;">
                <label class="form-label" for="admin_secret">Admin Secret Access Key</label>
                <input type="password" id="admin_secret" name="admin_secret" class="form-control" placeholder="Enter secret code to register as Organizer">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth">Sign Up</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<script>
    // Toggle Admin Secret Key input visibility depending on chosen role
    const roleUser = document.getElementById('role_user');
    const roleAdmin = document.getElementById('role_admin');
    const adminSecretGroup = document.getElementById('admin_secret_group');
    const adminSecretInput = document.getElementById('admin_secret');

    function toggleAdminSecret() {
        if (roleAdmin.checked) {
            adminSecretGroup.style.display = 'block';
            adminSecretInput.setAttribute('required', 'required');
        } else {
            adminSecretGroup.style.display = 'none';
            adminSecretInput.removeAttribute('required');
            adminSecretInput.value = '';
        }
    }

    roleUser.addEventListener('change', toggleAdminSecret);
    roleAdmin.addEventListener('change', toggleAdminSecret);
</script>

<?php include 'includes/footer.php'; ?>
