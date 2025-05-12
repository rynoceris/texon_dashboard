<?php
// includes/api/delete_school.php
// This file handles the AJAX request to delete a school

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
	exit;
}

// Check if user is admin
if ($currentUser['role'] !== 'admin') {
	jsonResponse([
		'success' => false,
		'message' => 'You do not have permission to perform this action'
	], 403);
	exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	jsonResponse([
		'success' => false,
		'message' => 'Invalid request method'
	], 405);
	exit;
}

// Get domain from POST data
$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';

// Validate domain
if (empty($domain)) {
	jsonResponse([
		'success' => false,
		'message' => 'School domain is required'
	], 400);
	exit;
}

// Check if school exists
$db = Database::getInstance();
$existingSchool = $db->selectOne(
	"SELECT * FROM " . DB_PREFIX . "school_data WHERE domain = ?",
	[$domain]
);

if (!$existingSchool) {
	jsonResponse([
		'success' => false,
		'message' => 'School with this domain does not exist'
	], 404);
	exit;
}

// Delete school
try {
	$result = $db->delete(DB_PREFIX . "school_data", "domain = ?", [$domain]);
	
	if ($result) {
		logMessage("School {$existingSchool['school_name']} ({$domain}) deleted by user {$currentUser['email']}", 'info');
		
		jsonResponse([
			'success' => true,
			'message' => 'School deleted successfully',
			'school_name' => $existingSchool['school_name']
		]);
		exit;
	} else {
		logMessage("Failed to delete school {$existingSchool['school_name']} ({$domain})", 'error');
		
		jsonResponse([
			'success' => false,
			'message' => 'Failed to delete school'
		], 500);
		exit;
	}
} catch (Exception $e) {
	logMessage("Exception deleting school {$existingSchool['school_name']} ({$domain}): " . $e->getMessage(), 'error');
	
	jsonResponse([
		'success' => false,
		'message' => 'Error deleting school: ' . $e->getMessage()
	], 500);
	exit;
}

// Ensure we always send a response
jsonResponse([
	'success' => false,
	'message' => 'Unknown error occurred'
], 500);
exit;