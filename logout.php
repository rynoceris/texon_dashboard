<?php
// logout.php
// This file handles user logout

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

// Perform logout
$auth->logout();

// Destroy session
session_destroy();

// Redirect to login page
redirect(APP_URL . '/login.php');
