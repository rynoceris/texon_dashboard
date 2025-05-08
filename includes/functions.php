<?php
// includes/functions.php

/**
 * Format a date to a human-readable string
 * 
 * @param string $date Date string
 * @param string $format Format string (default: 'M j, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format a number to a currency value
 * 
 * @param float $value Value to format
 * @param string $currency Currency code (default: 'USD')
 * @return string Formatted currency value
 */
function formatCurrency($value, $currency = 'USD') {
    $currencySymbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    
    $symbol = $currencySymbols[$currency] ?? '$';
    return $symbol . number_format($value, 2);
}

/**
 * Format a percentage value
 * 
 * @param float $value Value to format as percentage
 * @param int $decimals Number of decimal places
 * @return string Formatted percentage
 */
function formatPercentage($value, $decimals = 2) {
    return number_format($value, $decimals) . '%';
}

/**
 * Clean and validate an email address
 * 
 * @param string $email Email address to clean
 * @return string|false Cleaned email or false if invalid
 */
function cleanEmail($email) {
    $email = trim(strtolower($email));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

/**
 * Extract domain from an email address
 * 
 * @param string $email Email address
 * @return string Domain name
 */
function extractEmailDomain($email) {
    $parts = explode('@', $email);
    return end($parts);
}

/**
 * Create a slug from a string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function createSlug($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/\s+/', '-', $string);
    return $string;
}

/**
 * Get school name from domain
 * 
 * @param string $domain Email domain
 * @return string School name
 */
function getSchoolNameFromDomain($domain) {
    // Extract domain without TLD for basic name
    $parts = explode('.', $domain);
    if (count($parts) >= 2) {
        $name = $parts[count($parts) - 2];
        // Capitalize and replace hyphens with spaces
        $name = str_replace('-', ' ', $name);
        return ucwords($name);
    }
    return ucfirst($domain);
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message in session
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message text
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'time' => time()
    ];
}

/**
 * Get and clear flash message from session
 * 
 * @return array|null Flash message array or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        // Only return if the message is less than 5 minutes old
        if (time() - $message['time'] < 300) {
            return $message;
        }
    }
    return null;
}

/**
 * Check if a string contains a specific value
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if needle is found, false otherwise
 */
function stringContains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}

/**
 * Load a view file with data
 * 
 * @param string $view View file name without extension
 * @param array $data Data to pass to the view
 * @return void
 */
function loadView($view, $data = []) {
    // Extract data to individual variables
    extract($data);
    
    // Include view file
    $viewPath = APP_PATH . '/views/' . $view . '.php';
    
    if (file_exists($viewPath)) {
        require $viewPath;
    } else {
        echo "View not found: $view";
    }
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $level Log level (info, error, warning, debug)
 * @return void
 */
function logMessage($message, $level = 'info') {
    $logDir = APP_PATH . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Check if the current request is AJAX
 * 
 * @return bool True if AJAX request, false otherwise
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 * 
 * @param array $data Data to convert to JSON
 * @param int $statusCode HTTP status code
 * @return void
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
