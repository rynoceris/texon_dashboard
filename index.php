<?php
// Initialize session
session_start();

// Load configuration
require_once 'config/config.php';

// Load required files
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->getCurrentUser();

// Handle not logged in users
if (!$currentUser) {
    redirect(APP_URL . '/login.php');
}

// Load the dashboard view
loadView('dashboard', ['user' => $currentUser]);
?>
