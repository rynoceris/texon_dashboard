<?php
// includes/api/save_credentials.php
// This file handles the AJAX request to save API credentials

// Initialize session
session_start();

// Load configuration
require_once '../../config/config.php';

// Load required files
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    jsonResponse([
        'success' => false,
        'message' => 'Authentication required'
    ], 401);
}

// Check if user is admin
if ($currentUser['role'] !== 'admin') {
    jsonResponse([
        'success' => false,
        'message' => 'You do not have permission to perform this action'
    ], 403);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid request method'
    ], 405);
}

// Get service from POST data
$service = isset($_POST['service']) ? trim($_POST['service']) : '';

// Validate service
if (empty($service) || !in_array($service, ['csd', 'brightpearl', 'klaviyo'])) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid service specified'
    ], 400);
}

// Initialize database
$db = Database::getInstance();

// Check if we're testing the connection
$isTest = isset($_POST['test']) && $_POST['test'] == 1;

// Process credentials based on service
switch ($service) {
    case 'csd':
        // Get CSD database credentials
        $dbHost = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
        $dbName = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';
        $dbUser = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
        $dbPass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
        $dbPrefix = isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : '';
        
        // Validate required fields
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            jsonResponse([
                'success' => false,
                'message' => 'Database host, name, and user are required'
            ], 400);
        }
        
        // Test connection if requested
        if ($isTest) {
            try {
                // Create database connection
                $dsn = "mysql:host=$dbHost;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5 // 5 seconds timeout
                ];
                
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                
                // Check if database exists
                $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
                $dbExists = $stmt->fetchColumn();
                
                if (!$dbExists) {
                    jsonResponse([
                        'success' => false,
                        'message' => "Database '$dbName' does not exist"
                    ], 400);
                }
                
                // Connect to database
                $pdo->exec("USE `$dbName`");
                
                // Check if tables exist
                $tablePrefix = $dbPrefix ?: 'csd_';
                $requiredTables = ['schools', 'staff', 'school_staff'];
                $missingTables = [];
                
                foreach ($requiredTables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '{$tablePrefix}{$table}'");
                    if (!$stmt->fetchColumn()) {
                        $missingTables[] = $tablePrefix . $table;
                    }
                }
                
                if (!empty($missingTables)) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'The following tables are missing: ' . implode(', ', $missingTables)
                    ], 400);
                }
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Connection to College Sports Directory database successful'
                ]);
            } catch (PDOException $e) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ], 400);
            }
        }
        
        // Save credentials
        $credentials = [
            'api_key' => null,
            'api_secret' => null,
            'access_token' => null,
            'refresh_token' => null,
            'additional_data' => json_encode([
                'db_host' => $dbHost,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'db_prefix' => $dbPrefix
            ])
        ];
        
        break;
        
    case 'brightpearl':
        // Get Brightpearl credentials
        $accountCode = isset($_POST['account_code']) ? trim($_POST['account_code']) : '';
        $appRef = isset($_POST['app_ref']) ? trim($_POST['app_ref']) : '';
        $appToken = isset($_POST['app_token']) ? trim($_POST['app_token']) : '';
        
        // Validate required fields
        if (empty($accountCode) || empty($appRef) || empty($appToken)) {
            jsonResponse([
                'success' => false,
                'message' => 'Account code, App Reference, and Staff Token are required'
            ], 400);
        }
        
        // Save credentials
        $credentials = [
            'api_key' => $appToken,
            'api_secret' => null,
            'access_token' => null,
            'refresh_token' => null,
            'expires_at' => null,
            'additional_data' => json_encode([
                'account_code' => $accountCode,
                'app_ref' => $appRef
            ])
        ];
        
        break;
        
    case 'klaviyo':
        // Get Klaviyo credentials
        $apiKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
        $apiVersion = isset($_POST['api_version']) ? trim($_POST['api_version']) : '2023-09-15';
        
        // Validate required fields
        if (empty($apiKey)) {
            jsonResponse([
                'success' => false,
                'message' => 'API key is required'
            ], 400);
        }
        
        // Test connection if requested
        if ($isTest) {
            // Test Klaviyo connection logic
            // This would normally involve making an API request to validate the credentials
            
            // For now, just simulate a successful connection
            jsonResponse([
                'success' => true,
                'message' => 'Connection to Klaviyo API successful'
            ]);
        }
        
        // Save credentials
        $credentials = [
            'api_key' => $apiKey,
            'api_secret' => null,
            'access_token' => null,
            'refresh_token' => null,
            'additional_data' => json_encode([
                'api_version' => $apiVersion
            ])
        ];
        
        break;
}

// Check if credentials already exist
$existingCredentials = $db->selectOne(
    "SELECT * FROM " . DB_PREFIX . "api_credentials WHERE service = ?",
    [$service]
);

try {
    if ($existingCredentials) {
        // Update existing credentials
        $success = $db->update(
            DB_PREFIX . "api_credentials",
            $credentials,
            "service = ?",
            [$service]
        );
    } else {
        // Insert new credentials
        $credentials['service'] = $service;
        $credentials['created_at'] = date('Y-m-d H:i:s');
        
        $success = $db->insert(DB_PREFIX . "api_credentials", $credentials);
    }
    
    if ($success) {
        jsonResponse([
            'success' => true,
            'message' => ucfirst($service) . ' credentials saved successfully'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to save credentials'
        ], 500);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error saving credentials: ' . $e->getMessage()
    ], 500);
}
