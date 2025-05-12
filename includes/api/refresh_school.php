<?php
// includes/api/refresh_school.php
// This file handles the AJAX request to refresh school data

// Initialize session
session_start();

// Load configuration
require_once '../../config/config.php';

// Load required files
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Load data source files
require_once '../../sources/csd_source.php';
require_once '../../sources/brightpearl_source.php';
require_once '../../sources/klaviyo_source.php';

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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid request method'
    ], 405);
}

// Get domain from POST data
$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';

// Validate domain
if (empty($domain)) {
    jsonResponse([
        'success' => false,
        'message' => 'School domain is required'
    ], 400);
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
}

// Refresh school data
try {
    // Initialize data sources
    $csdSource = new CSDSource();
    $brightpearlSource = new BrightpearlSource();
    $klaviyoSource = new KlaviyoSource();
    
    $data = [];
    
    // Get data from College Sports Directory
    if ($csdSource->isAvailable()) {
        $csdData = $csdSource->getSchoolData($domain);
        $data['csd'] = $csdData;
        
        // Update school data with CSD information
        if ($csdData['success']) {
            $staffCount = $csdData['data']['total_staff'] ?? 0;
            $csdSchoolId = $csdData['data']['school']['school_id'] ?? null;
            
            $db->update(DB_PREFIX . "school_data", 
                [
                    'staff_count' => $staffCount,
                    'csd_school_id' => $csdSchoolId
                ],
                "domain = ?",
                [$domain]
            );
        }
    }
    
    // Get data from Brightpearl
    if ($brightpearlSource->isAvailable()) {
        $bpData = $brightpearlSource->getSchoolData($domain);
        $data['brightpearl'] = $bpData;
        
        // Update school data with Brightpearl information
        if ($bpData['success']) {
            $orderCount = $bpData['data']['total_orders'] ?? 0;
            $orderTotal = $bpData['data']['total_value'] ?? 0;
            
            $db->update(DB_PREFIX . "school_data", 
                [
                    'order_count' => $orderCount,
                    'order_total' => $orderTotal
                ],
                "domain = ?",
                [$domain]
            );
        }
    }
    
    // Get data from Klaviyo
    if ($klaviyoSource->isAvailable()) {
        $klaviyoData = $klaviyoSource->getSchoolData($domain);
        $data['klaviyo'] = $klaviyoData;
        
        // Update school data with Klaviyo information
        if ($klaviyoData['success']) {
            $emailCount = $klaviyoData['data']['email_count'] ?? 0;
            $openRate = $klaviyoData['data']['open_rate'] ?? 0;
            $clickRate = $klaviyoData['data']['click_rate'] ?? 0;
            $orderRate = $klaviyoData['data']['order_rate'] ?? 0;
            
            $db->update(DB_PREFIX . "school_data", 
                [
                    'email_count' => $emailCount,
                    'open_rate' => $openRate,
                    'click_rate' => $clickRate,
                    'order_rate' => $orderRate
                ],
                "domain = ?",
                [$domain]
            );
        }
    }
    
    // Store complete JSON data and update timestamp
    $db->update(DB_PREFIX . "school_data", 
        [
            'data_json' => json_encode($data),
            'last_updated' => date('Y-m-d H:i:s')
        ],
        "domain = ?",
        [$domain]
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'School data refreshed successfully',
        'data' => $data
    ]);
} catch (Exception $e) {
    logMessage('Failed to refresh school data: ' . $e->getMessage(), 'error');
    
    jsonResponse([
        'success' => false,
        'message' => 'Failed to refresh school data: ' . $e->getMessage()
    ], 500);
}