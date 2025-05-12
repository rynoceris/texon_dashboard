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
    // Ensure no output has been sent yet
    if (headers_sent($file, $line)) {
        // Log error if headers already sent
        error_log("Headers already sent in $file on line $line - Cannot send JSON response");
        
        // Try to output JSON anyway, but it might not work properly
        echo json_encode($data);
        exit;
    }
    
    // Set HTTP status code
    http_response_code($statusCode);
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Clear any previous output buffering
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Encode and output the JSON data
    echo json_encode($data);
    
    // End script execution
    exit;
}

/**
 * Parse a full name into its components (title, first name, middle name, last name, suffix)
 * 
 * @param string $fullName The full name to parse
 * @return array Associative array containing name components
 */
function parseFullName($fullName) {
    // Default result structure
    $result = [
        'title' => '',
        'first_name' => '',
        'middle_name' => '',
        'last_name' => '',
        'suffix' => ''
    ];
    
    // Return default structure if full name is empty
    if (empty($fullName)) {
        return $result;
    }
    
    // Common titles
    $titles = [
        'Dr.', 'Dr', 
        'Professor', 'Prof.', 'Prof', 
        'Mr.', 'Mr', 
        'Mrs.', 'Mrs', 
        'Ms.', 'Ms', 
        'Miss', 
        'Rev.', 'Rev', 
        'Hon.', 'Hon',
        'Sir', 
        'Lady', 
        'Capt.', 'Capt', 'Captain',
        'Lt.', 'Lt', 'Lieutenant',
        'Sgt.', 'Sgt', 'Sergeant',
        'Col.', 'Col', 'Colonel',
        'Dean', 'Coach', 'Director'
    ];
    
    // Common suffixes
    $suffixes = [
        'Jr.', 'Jr', 
        'Sr.', 'Sr', 
        'I', 'II', 'III', 'IV', 'V', 
        'Ph.D.', 'PhD', 'Ph.D', 
        'M.D.', 'MD', 'M.D', 
        'J.D.', 'JD', 'J.D', 
        'DDS', 'D.D.S.', 'D.D.S',
        'MBA', 'M.B.A.', 'M.B.A',
        'CPA', 'C.P.A.', 'C.P.A',
        'Esq.', 'Esq',
        'R.N.', 'RN',
        'B.A.', 'BA',
        'B.S.', 'BS',
        'M.A.', 'MA',
        'M.S.', 'MS'
    ];
    
    // Trim and normalize the full name
    $fullName = trim($fullName);
    
    // Check for title
    foreach ($titles as $title) {
        $titleWithSpace = $title . ' ';
        if (strpos($fullName, $titleWithSpace) === 0) {
            $result['title'] = $title;
            $fullName = trim(substr($fullName, strlen($titleWithSpace)));
            break;
        }
    }
    
    // Check for suffix
    foreach ($suffixes as $suffix) {
        // Check for suffix at the end or with a comma
        $suffixAtEnd = ' ' . $suffix;
        $suffixWithComma = ', ' . $suffix;
        
        if (substr($fullName, -strlen($suffixAtEnd)) === $suffixAtEnd) {
            $result['suffix'] = $suffix;
            $fullName = trim(substr($fullName, 0, -strlen($suffixAtEnd)));
            break;
        } else if (strpos($fullName, $suffixWithComma) !== false) {
            $result['suffix'] = $suffix;
            $fullName = trim(str_replace($suffixWithComma, '', $fullName));
            break;
        }
    }
    
    // Split remaining name into parts
    $nameParts = explode(' ', $fullName);
    $namePartsCount = count($nameParts);
    
    // Process based on number of parts
    if ($namePartsCount == 1) {
        // Only one part, treat as last name
        $result['last_name'] = $nameParts[0];
    } else if ($namePartsCount == 2) {
        // Two parts, classic first and last name
        $result['first_name'] = $nameParts[0];
        $result['last_name'] = $nameParts[1];
    } else {
        // Three or more parts, handle middle names/initials
        $result['first_name'] = $nameParts[0];
        $result['last_name'] = $nameParts[$namePartsCount - 1];
        
        // Everything in between is the middle name/initial
        $middleParts = array_slice($nameParts, 1, $namePartsCount - 2);
        $result['middle_name'] = implode(' ', $middleParts);
    }
    
    // Special handling for middle initials: recognize patterns like "William H. Gates" or "John A Smith"
    if ($namePartsCount >= 3) {
        // Check if the middle part looks like an initial (single letter, possibly followed by period)
        $middlePart = $result['middle_name'];
        
        // If the middle part is very short and the last name isn't, it might be just an initial
        // This helps avoid splitting compound last names incorrectly
        if ((strlen($middlePart) <= 2 && substr($middlePart, -1) === '.') || strlen($middlePart) === 1) {
            // Do nothing, this is correctly identified as a middle initial
        } 
        // Look for compound last names with spaces
        else if (strpos($result['middle_name'], ' ') !== false) {
            // Multiple middle names or parts - keep as is
        }
        // For complex cases, if we have 3+ parts, the middle name might be a part of a compound last name
        else if ($namePartsCount >= 3) {
            // Check for common compound name patterns
            $potentialCompound = $result['middle_name'] . ' ' . $result['last_name'];
            
            // Common compound last name prefixes
            $compoundPrefixes = ['van', 'von', 'de', 'del', 'della', 'der', 'den', 'da', 'las', 'los', 'la', 'le', 'el', 'st', 'o', 'mc', 'mac'];
            
            $isCompound = false;
            foreach ($compoundPrefixes as $prefix) {
                if (strtolower($result['middle_name']) === strtolower($prefix)) {
                    $isCompound = true;
                    break;
                }
            }
            
            if ($isCompound) {
                $result['last_name'] = $potentialCompound;
                $result['middle_name'] = '';
            }
        }
    }
    
    return $result;
}

/**
 * Format a full name based on its components with optional display preferences
 * 
 * @param array|string $nameData Either a parsed name array or a raw full name string
 * @param array $options Display options (show_title, show_middle, show_suffix, first_name_first)
 * @return string Formatted name
 */
function formatNameForDisplay($nameData, $options = []) {
    // Default options
    $defaultOptions = [
        'show_title' => true,
        'show_middle' => true,
        'show_suffix' => true,
        'first_name_first' => true,
        'last_name_uppercase' => false,
        'middle_initial_only' => false
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    // Parse the name if a string was provided
    if (is_string($nameData)) {
        $nameData = parseFullName($nameData);
    }
    
    // Process middle name for display
    $middleName = '';
    if ($options['show_middle'] && !empty($nameData['middle_name'])) {
        if ($options['middle_initial_only'] && strlen($nameData['middle_name']) > 1) {
            // Get first letter only
            $middleInitial = substr($nameData['middle_name'], 0, 1);
            $middleName = $middleInitial . '.';
        } else {
            $middleName = $nameData['middle_name'];
        }
    }
    
    // Process last name
    $lastName = $nameData['last_name'];
    if ($options['last_name_uppercase']) {
        $lastName = strtoupper($lastName);
    }
    
    // Build display name
    $displayName = '';
    
    if ($options['show_title'] && !empty($nameData['title'])) {
        $displayName .= $nameData['title'] . ' ';
    }
    
    if ($options['first_name_first']) {
        // First name first format (e.g., "John Doe")
        if (!empty($nameData['first_name'])) {
            $displayName .= $nameData['first_name'];
            
            if (!empty($middleName)) {
                $displayName .= ' ' . $middleName;
            }
            
            if (!empty($lastName)) {
                $displayName .= ' ' . $lastName;
            }
        } else if (!empty($lastName)) {
            // No first name, just use last name
            $displayName .= $lastName;
        }
    } else {
        // Last name first format (e.g., "Doe, John")
        if (!empty($lastName)) {
            $displayName .= $lastName;
            
            if (!empty($nameData['first_name']) || !empty($middleName)) {
                $displayName .= ',';
                
                if (!empty($nameData['first_name'])) {
                    $displayName .= ' ' . $nameData['first_name'];
                }
                
                if (!empty($middleName)) {
                    $displayName .= ' ' . $middleName;
                }
            }
        } else if (!empty($nameData['first_name'])) {
            // No last name, just use first name
            $displayName .= $nameData['first_name'];
            
            if (!empty($middleName)) {
                $displayName .= ' ' . $middleName;
            }
        }
    }
    
    if ($options['show_suffix'] && !empty($nameData['suffix'])) {
        $displayName .= ', ' . $nameData['suffix'];
    }
    
    return trim($displayName);
}

/**
 * Example for accessing individual components in different places:
 */
/*
if (isset($staff['full_name'])) {
    $parsedName = parseFullName($staff['full_name']);
    
    // Access individual components
    $title = $parsedName['title'];       // Prof.
    $firstName = $parsedName['first_name']; // William
    $middleName = $parsedName['middle_name']; // H.
    $lastName = $parsedName['last_name'];   // Gates
    $suffix = $parsedName['suffix'];     // III
    
    // Display differently depending on context
    $formalName = formatNameForDisplay($parsedName); // "Prof. William H. Gates, III"
    $directoryName = formatNameForDisplay($parsedName, ['first_name_first' => false]); // "Gates, William H."
    $casualName = formatNameForDisplay($parsedName, ['show_title' => false, 'show_suffix' => false]); // "William H. Gates"
    $lastNameWithInitial = formatNameForDisplay($parsedName, [
        'show_title' => false,
        'middle_initial_only' => true,
        'show_suffix' => false
    ]); // "William H. Gates"
}
*/
?>
