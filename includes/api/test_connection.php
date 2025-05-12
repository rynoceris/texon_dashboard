<?php
// includes/api/test_connection.php
// This file handles the AJAX request to test API connections

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

// Process test connection based on service
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
		
		// Test connection
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
		
		// Test connection
		try {
			// Construct the API URL
			$apiUrl = BRIGHTPEARL_API_URL;
			
			// Try a basic endpoint that should be available to all apps
			$endpoint = "{$apiUrl}/{$accountCode}/product-service/product";
			
			// Setup cURL
			$curl = curl_init();
			
			$headers = [
				'Content-Type: application/json',
				'Accept: application/json',
				'brightpearl-app-ref: ' . $appRef,
				'brightpearl-staff-token: ' . $appToken
			];
			
			curl_setopt_array($curl, [
				CURLOPT_URL => $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_VERBOSE => DEBUG_MODE,
			]);
			
			$response = curl_exec($curl);
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$error = curl_error($curl);
			
			curl_close($curl);
			
			// Check if request was successful
			if ($statusCode >= 200 && $statusCode < 300) {
				jsonResponse([
					'success' => true,
					'message' => 'Connection to Brightpearl API successful'
				]);
			} else {
				$errorMessage = $error;
				
				if (empty($errorMessage)) {
					$responseData = json_decode($response, true);
					if (isset($responseData['response']) && isset($responseData['response']['errors'])) {
						$errorMessage = $responseData['response']['errors'][0]['message'] ?? 'Unknown error';
					} else {
						$errorMessage = $response; // Just show the raw response if we can't parse it
					}
				}
				
				jsonResponse([
					'success' => false,
					'message' => 'API request failed: ' . $errorMessage,
					'status_code' => $statusCode
				], 400);
			}
		} catch (Exception $e) {
			jsonResponse([
				'success' => false,
				'message' => 'Exception: ' . $e->getMessage()
			], 500);
		}
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
		
		// Test connection
		try {
			// Construct the API URL
			$apiUrl = KLAVIYO_API_URL;
			$endpoint = "{$apiUrl}/metrics";
			
			// Setup cURL
			$curl = curl_init();
			
			$headers = [
				'Accept: application/json',
				'Revision: ' . $apiVersion,
				'Authorization: Klaviyo-API-Key ' . $apiKey
			];
			
			curl_setopt_array($curl, [
				CURLOPT_URL => $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => $headers,
			]);
			
			$response = curl_exec($curl);
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$error = curl_error($curl);
			
			curl_close($curl);
			
			// Check if request was successful
			if ($statusCode >= 200 && $statusCode < 300) {
				jsonResponse([
					'success' => true,
					'message' => 'Connection to Klaviyo API successful'
				]);
			} else {
				$errorMessage = $error;
				
				if (empty($errorMessage)) {
					$responseData = json_decode($response, true);
					$errorMessage = isset($responseData['errors'][0]['detail']) 
						? $responseData['errors'][0]['detail'] 
						: 'Unknown error';
				}
				
				jsonResponse([
					'success' => false,
					'message' => 'API request failed: ' . $errorMessage,
					'status_code' => $statusCode
				], 400);
			}
		} catch (Exception $e) {
			jsonResponse([
				'success' => false,
				'message' => 'Exception: ' . $e->getMessage()
			], 500);
		}
		break;
		
	default:
		jsonResponse([
			'success' => false,
			'message' => 'Invalid service specified'
		], 400);
}