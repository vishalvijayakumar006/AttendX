<?php
// Folder: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\includes
// File: header.php
// Purpose: Main header template containing global CSS links, navigation bar, and session starts.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper to determine active link
function is_active($page) {
    return (basename($_SERVER['PHP_SELF']) == $page) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Smart Event Entry'; ?></title>
    <!-- Modern typography from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons for modern dashboard feel -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #0b1329;
            --bg-card: rgba(255, 255, 255, 0.03);
            --border-color: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --secondary: #a855f7;
            --accent: #10b981;
            --accent-hover: #059669;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --max-width: 1200px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient background glowing circles */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            z-index: -2;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
        }
        body::before {
            top: 10%;
            left: 5%;
            background: var(--primary);
        }
        body::after {
            bottom: 10%;
            right: 5%;
            background: var(--secondary);
        }

        /* Navigation Bar */
        header {
            background: rgba(11, 19, 41, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--text-main);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 16px;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.04);
        }

        .btn-nav-primary {
            background: var(--primary) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 14px var(--primary-glow);
        }

        .btn-nav-primary:hover {
            background: var(--primary-hover) !important;
            transform: translateY(-1px);
        }

        .menu-btn {
            display: none;
            color: var(--text-main);
            font-size: 1.5rem;
            cursor: pointer;
            border: none;
            background: none;
        }

        main {
            flex: 1;
            max-width: var(--max-width);
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Responsive Layouts */
        @media (max-width: 768px) {
            .menu-btn {
                display: block;
            }

            .nav-links {
                position: fixed;
                top: 70px;
                right: -100%;
                width: 250px;
                height: calc(100vh - 70px);
                background: rgba(11, 19, 41, 0.95);
                backdrop-filter: blur(20px);
                border-left: 1px solid var(--border-color);
                flex-direction: column;
                align-items: stretch;
                padding: 24px;
                transition: right 0.3s ease;
                z-index: 999;
            }

            .nav-links.active {
                right: 0;
            }

            .nav-links a {
                padding: 12px 16px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="/smart-event-entry/index.php" class="logo">
                <i class="fa-solid fa-qrcode"></i> SmartEntry
            </a>
            
            <button class="menu-btn" id="menuToggle">
                <i class="fa-solid fa-bars"></i>
            </button>

            <ul class="nav-links" id="navLinks">
                <!-- Links accessible to everyone when logged out -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="/smart-event-entry/index.php" class="<?php echo is_active('index.php'); ?>"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="/smart-event-entry/login.php" class="<?php echo is_active('login.php'); ?>"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
                    <li><a href="/smart-event-entry/register.php" class="btn-nav-primary <?php echo is_active('register.php'); ?>"><i class="fa-solid fa-user-plus"></i> Register</a></li>
                <?php else: ?>
                    <!-- Links for logged in users (attendees/admins) -->
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <!-- Admin Navigation links -->
                        <li><a href="/smart-event-entry/admin/dashboard.php" class="<?php echo is_active('dashboard.php'); ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                        <li><a href="/smart-event-entry/admin/verify.php" class="<?php echo is_active('verify.php'); ?>"><i class="fa-solid fa-camera"></i> Verify Entry</a></li>
                        <li><a href="/smart-event-entry/admin/reports.php" class="<?php echo is_active('reports.php'); ?>"><i class="fa-solid fa-file-invoice"></i> Reports</a></li>
                    <?php else: ?>
                        <!-- Standard User Navigation links -->
                        <li><a href="/smart-event-entry/user/dashboard.php" class="<?php echo is_active('dashboard.php'); ?>"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
                        <li><a href="/smart-event-entry/user/events.php" class="<?php echo is_active('events.php'); ?>"><i class="fa-solid fa-calendar-days"></i> Find Events</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/smart-event-entry/user/profile.php" class="<?php echo is_active('profile.php'); ?>"><i class="fa-solid fa-user-gear"></i> Profile</a></li>
                    <li><a href="/smart-event-entry/logout.php" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <script>
        // Toggle mobile menu
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        const toggleIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            if (navLinks.classList.contains('active')) {
                toggleIcon.className = 'fa-solid fa-xmark';
            } else {
                toggleIcon.className = 'fa-solid fa-bars';
            }
        });
    </script>
    <main>
