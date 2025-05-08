<?php
// includes/api/check_sources.php
// This file handles the AJAX request to check the status of data sources

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

// Check data source availability
try {
    // Initialize data sources
    $csdSource = new CSDSource();
    $brightpearlSource = new BrightpearlSource();
    $klaviyoSource = new KlaviyoSource();
    
    // Check each source
    $csdAvailable = $csdSource->isAvailable();
    $brightpearlAvailable = $brightpearlSource->isAvailable();
    $klaviyoAvailable = $klaviyoSource->isAvailable();
    
    jsonResponse([
        'success' => true,
        'csd' => $csdAvailable,
        'brightpearl' => $brightpearlAvailable,
        'klaviyo' => $klaviyoAvailable
    ]);
} catch (Exception $e) {
    logMessage('Failed to check data sources: ' . $e->getMessage(), 'error');
    
    jsonResponse([
        'success' => false,
        'message' => 'Failed to check data sources: ' . $e->getMessage()
    ], 500);
}
