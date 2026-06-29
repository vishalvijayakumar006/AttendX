<?php
// Root Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry
// File Name: index.php
// Purpose: Public landing page of the application, showing active events and system features.

require_once 'config/database.php';

$db = Database::getInstance();
$events = [];
$errors = [];

try {
    // Select upcoming events (maximum 6) to showcase on the home screen
    $stmt = $db->query("
        SELECT e.*, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status != 'rejected'
        WHERE e.date >= CURDATE()
        GROUP BY e.id 
        ORDER BY e.date ASC, e.time ASC
        LIMIT 6
    ");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Failed to load featured events: " . $e->getMessage();
}

$page_title = "SmartEntry | Smart Event Entry & QR Ticket System";
include 'includes/header.php';
?>

<style>
    /* Hero section styling */
    .hero {
        text-align: center;
        padding: 80px 20px;
        position: relative;
        background: radial-gradient(circle at center, rgba(99, 102, 241, 0.1) 0%, rgba(0,0,0,0) 60%);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.02);
        margin-bottom: 60px;
    }

    .hero h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
        line-height: 1.2;
        letter-spacing: -0.03em;
        background: linear-gradient(135deg, #a855f7, #6366f1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero p {
        color: var(--text-muted);
        font-size: 1.2rem;
        max-width: 650px;
        margin: 0 auto 40px auto;
        line-height: 1.6;
    }

    .cta-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .btn-hero {
        padding: 16px 32px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.05rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-hero-primary {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 20px var(--primary-glow);
    }

    .btn-hero-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
    }

    .btn-hero-secondary {
        background: rgba(255, 255, 255, 0.04);
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }

    .btn-hero-secondary:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    /* Key Features layout */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 80px;
    }

    .feature-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 32px;
        text-align: center;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .feature-card:hover {
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-4px);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: rgba(99, 102, 241, 0.1);
        color: var(--primary);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.8rem;
        margin: 0 auto 24px auto;
    }

    .feature-card:nth-child(2) .feature-icon { background: rgba(168, 85, 247, 0.1); color: var(--secondary); }
    .feature-card:nth-child(3) .feature-icon { background: rgba(16, 185, 129, 0.1); color: var(--accent); }

    .feature-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--text-main);
    }

    .feature-card p {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    /* Section Headers */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 32px;
    }

    .section-title h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .section-title p {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-top: 4px;
    }

    /* Event showcase cards grid */
    .home-events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    .event-home-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 280px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .event-home-card:hover {
        transform: translateY(-5px);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--text-main);
    }

    .card-desc {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 20px;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .card-meta {
        list-style: none;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
        margin-bottom: 20px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .card-meta li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .card-meta i {
        color: var(--primary);
    }

    .btn-register-home {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border-color);
        color: var(--text-main);
        text-align: center;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        display: block;
        transition: all 0.3s ease;
    }

    .btn-register-home:hover {
        background: var(--primary);
        border-color: var(--primary);
        box-shadow: 0 4px 12px var(--primary-glow);
    }

    .no-events {
        text-align: center;
        padding: 60px 20px;
        background: var(--bg-card);
        border: 1px dashed var(--border-color);
        border-radius: 20px;
        color: var(--text-muted);
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .hero h1 { font-size: 2.5rem; }
        .hero p { font-size: 1.1rem; }
    }
</style>

<!-- Banner Hero Section -->
<section class="hero">
    <h1>Seamless Event Check-Ins</h1>
    <p>The ultimate digital entry system. Register for upcoming webinars, conferences, and workshops, download your unique encrypted QR pass, and scan to check-in instantly.</p>
    
    <div class="cta-buttons">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn-hero btn-hero-primary"><i class="fa-solid fa-user-plus"></i> Join as Attendee</a>
            <a href="login.php" class="btn-hero btn-hero-secondary"><i class="fa-solid fa-right-to-bracket"></i> Organizer Console</a>
        <?php else: ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="btn-hero btn-hero-primary"><i class="fa-solid fa-chart-line"></i> Admin Console</a>
            <?php else: ?>
                <a href="user/dashboard.php" class="btn-hero btn-hero-primary"><i class="fa-solid fa-table-columns"></i> View My Tickets</a>
                <a href="user/events.php" class="btn-hero btn-hero-secondary"><i class="fa-solid fa-compass"></i> Discover Events</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Features section -->
<section class="features-grid">
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-qrcode"></i></div>
        <h3>Dynamic QR Badges</h3>
        <p>Every attendee receives a uniquely hashed, cryptographically secure QR ticket generated on the fly. Completely prevents duplicate ticket fraud.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-camera"></i></div>
        <h3>Webcam Scan Verification</h3>
        <p>Organizers scan tickets directly using any standard smartphone camera or laptop webcam. Zero-install validation within seconds.</p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div>
        <h3>Real-time Attendance</h3>
        <p>Track show-up stats, search attendees, and export detailed check-in spreadsheets instantly. Perfect for post-event analytics reports.</p>
    </div>
</section>

<!-- Active Featured Events list -->
<section class="events-section">
    <div class="section-header">
        <div class="section-title">
            <h2>Upcoming Events</h2>
            <p>Catch the latest conferences, tech summits, and coding hackathons.</p>
        </div>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'user'): ?>
            <a href="user/events.php" class="btn-hero-secondary" style="padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-compass"></i> View All</a>
        <?php endif; ?>
    </div>

    <div class="home-events-grid">
        <?php if (empty($events)): ?>
            <div class="no-events">
                <i class="fa-solid fa-calendar-xmark" style="font-size: 3rem; color: var(--primary); margin-bottom: 16px;"></i>
                <h3>No Events Active</h3>
                <p style="margin-top: 4px;">Organizers are currently setting up next events. Please check back later!</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $row): 
                $tickets_left = intval($row['max_capacity']) - intval($row['registered_count']);
            ?>
                <div class="event-home-card">
                    <div>
                        <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="card-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                    </div>

                    <div>
                        <ul class="card-meta">
                            <li><i class="fa-regular fa-calendar"></i> <span><?php echo date('M d, Y', strtotime($row['date'])); ?></span></li>
                            <li><i class="fa-solid fa-location-dot"></i> <span><?php echo htmlspecialchars($row['location']); ?></span></li>
                            <li><i class="fa-solid fa-users"></i> <span>
                                <?php echo ($tickets_left <= 0) ? 'SOLD OUT' : $tickets_left . ' seats remaining'; ?>
                            </span></li>
                        </ul>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_role'] === 'user'): ?>
                                <a href="user/event_details.php?id=<?php echo $row['id']; ?>" class="btn-register-home">Register Now</a>
                            <?php else: ?>
                                <a href="admin/dashboard.php" class="btn-register-home">Manage Event</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn-register-home">Login to Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
