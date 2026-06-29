<?php
// Root Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry
// File Name: login.php
// Purpose: Authenticates users and organizers, establishing login session variables.

require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Redirect user if they are already logged in
redirect_if_logged_in();

$errors = [];
$success_message = "";

// Capture success messages from redirection (like after successful registration)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Capture error messages from auth_check guards
if (isset($_SESSION['error_message'])) {
    $errors[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $db = Database::getInstance();

        try {
            // Find user in the database
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password using crypt-safe verification
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start the session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect to respective dashboard
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Log In | SmartEntry";
include 'includes/header.php';
?>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 180px);
        padding: 20px 0;
    }

    .auth-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 420px;
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

    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid var(--accent);
        color: #34d399;
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
        <h2 class="auth-title">Welcome Back</h2>

        <!-- Success Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_message); ?>
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

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth">Log In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Sign Up</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
