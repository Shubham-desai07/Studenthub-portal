<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 8: Database Connection Module (PDO)
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Course        : Web Development Lab (Practical 8)
 * Subject       : MySQL Schema Design, ER Model, PDO Connectivity & Prepared Statements
 * ============================================================================
 * Key Practical Objectives & Security Standards:
 * 1. Secure PHP Data Objects (PDO) connection interface.
 * 2. UTF-8 (utf8mb4) character encoding for multilingual support.
 * 3. PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION for robust error handling.
 * 4. PDO::ATTR_EMULATE_PREPARES => false for genuine server-side prepared statements.
 * 5. Secure exception handling without leaking database credentials to frontend.
 * ============================================================================
 */

// Database Configuration Constants (Configured for standard XAMPP / MySQL)
if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');
if (!defined('DB_PORT'))    define('DB_PORT', 3306);
if (!defined('DB_NAME'))    define('DB_NAME', 'studenthub_db');
if (!defined('DB_USER'))    define('DB_USER', 'root');
if (!defined('DB_PASS'))    define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Creates and returns a secure PDO database connection instance.
 *
 * @param bool $selectDatabase Whether to connect to specific database or root MySQL server
 * @return PDO|null Returns PDO connection instance on success, or null on failure
 */
function getPDOConnection($selectDatabase = true) {
    static $cachedPDO = null;

    // Return cached connection if already open for active database
    if ($selectDatabase && $cachedPDO !== null) {
        return $cachedPDO;
    }

    try {
        if ($selectDatabase) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        } else {
            // Used for database creation / setup before database exists
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Fetch associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Disable emulation to use native prepared statements
            PDO::ATTR_TIMEOUT            => 5,                        // 5 seconds connection timeout
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET // Enforce charset
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        if ($selectDatabase) {
            $cachedPDO = $pdo;
        }

        return $pdo;

    } catch (PDOException $e) {
        // Log detailed error for system administrator / developer inspection
        error_log("[Practical 8 PDO Connection Error] " . $e->getMessage());
        return null;
    }
}

/**
 * Diagnostic helper function to test database connectivity and gather metadata
 *
 * @return array Diagnostic connection results
 */
function testDBConnection() {
    $startTime = microtime(true);
    $result = [
        'connected'      => false,
        'message'        => '',
        'server_version' => 'N/A',
        'driver'         => 'PDO MySQL',
        'host'           => DB_HOST . ':' . DB_PORT,
        'database'       => DB_NAME,
        'latency_ms'     => 0,
        'tables'         => [],
        'error_detail'   => ''
    ];

    try {
        $pdo = getPDOConnection(true);

        // If specific DB failed, try connecting to MySQL host to see if MySQL is running
        if (!$pdo) {
            $pdoRoot = getPDOConnection(false);
            if ($pdoRoot) {
                $result['connected'] = false;
                $result['message'] = "MySQL server is online, but database '" . DB_NAME . "' has not been created yet. Click 'Auto-Initialize Database' to create it.";
                $result['server_version'] = $pdoRoot->getAttribute(PDO::ATTR_SERVER_VERSION);
                $result['latency_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                return $result;
            } else {
                $result['connected'] = false;
                $result['message'] = "Cannot connect to MySQL server at " . DB_HOST . ":" . DB_PORT . ". Please ensure Apache & MySQL are running in XAMPP Control Panel.";
                $result['latency_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                return $result;
            }
        }

        $latency = round((microtime(true) - $startTime) * 1000, 2);
        $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        // Fetch table information and row counts
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        $tableRows = $stmt->fetchAll(PDO::FETCH_NUM);

        foreach ($tableRows as $row) {
            $tableName = $row[0];
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `" . str_replace("`", "``", $tableName) . "`");
            $rowCount = (int)$countStmt->fetchColumn();
            $tables[$tableName] = $rowCount;
        }

        $result['connected']      = true;
        $result['message']        = "Successfully connected to MySQL database '" . DB_NAME . "' via PDO!";
        $result['server_version'] = $serverVersion;
        $result['latency_ms']     = $latency;
        $result['tables']         = $tables;

        return $result;

    } catch (PDOException $e) {
        $result['connected']    = false;
        $result['message']      = "Database Connection Failed: " . $e->getMessage();
        $result['error_detail'] = $e->getMessage();
        $result['latency_ms']   = round((microtime(true) - $startTime) * 1000, 2);
        return $result;
    }
}

/**
 * Helper to auto-create schema and seed tables if not yet created
 *
 * @return array Success status and execution message
 */
function autoInitializeDatabase() {
    try {
        // Connect to MySQL server without selecting database first
        $pdoRoot = getPDOConnection(false);
        if (!$pdoRoot) {
            return [
                'success' => false,
                'message' => 'Cannot reach MySQL server. Please start MySQL service in XAMPP Control Panel.'
            ];
        }

        // Find SQL dump file path
        $sqlPaths = [
            __DIR__ . '/studenthub_db.sql',
            __DIR__ . '/../studenthub_db.sql',
            __DIR__ . '/../data/studenthub_db.sql',
            __DIR__ . '/data/studenthub_db.sql'
        ];

        $sqlContent = false;
        foreach ($sqlPaths as $path) {
            if (file_exists($path)) {
                $sqlContent = file_get_contents($path);
                break;
            }
        }

        if ($sqlContent === false) {
            return [
                'success' => false,
                'message' => 'Schema file studenthub_db.sql not found in project directory.'
            ];
        }

        // Execute SQL script
        $pdoRoot->exec($sqlContent);

        return [
            'success' => true,
            'message' => 'Database studenthub_db created, normalized tables initialized, and seed data imported successfully!'
        ];

    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database Initialization Failed: ' . $e->getMessage()
        ];
    }
}
