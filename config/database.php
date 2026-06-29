<?php
// Config Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\config
// File Name: database.php
// Purpose: Establishes a secure connection to the MySQL database using PDO.

// Define Database credentials as constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default MySQL password is empty
define('DB_NAME', 'smart_event_db');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $conn;

    // The constructor is private to implement the Singleton Pattern (creates only one connection instance)
    private function __construct() {
        // Data Source Name defines connection details
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Configuration options for safe database transactions
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on query errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return database records as associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation to use actual SQL prepared statements (prevents SQL injection)
        ];

        try {
            // Instantiate the PDO connection
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error and hide raw connection details from users
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    // Static method to get the single connection instance
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
?>
