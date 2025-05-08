<?php
// sources/data_source.php
// Abstract base class for all data sources

// Make sure we have the database class
require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Abstract DataSource class for common functionality across all data sources
 */
abstract class DataSource {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if the data source is configured and available
     * 
     * @return bool True if available, false otherwise
     */
    abstract public function isAvailable();
    
    /**
     * Get the source name
     * 
     * @return string Source name
     */
    abstract public function getName();
    
    /**
     * Get data for a specific school domain
     * 
     * @param string $domain School email domain
     * @return array Data for the school
     */
    abstract public function getSchoolData($domain);
    
    /**
     * Get API credentials from database
     * 
     * @return array|null Credentials or null if not available
     */
    protected function getCredentials() {
        $sourceName = $this->getName();
        
        $credentials = $this->db->selectOne(
            "SELECT * FROM " . DB_PREFIX . "api_credentials WHERE service = ?",
            [$sourceName]
        );
        
        return $credentials;
    }
    
    /**
     * Save API credentials to database
     * 
     * @param array $credentials Credentials to save
     * @return bool True on success, false on failure
     */
    public function saveCredentials($credentials) {
        $sourceName = $this->getName();
        
        // Check if credentials already exist
        $existingCredentials = $this->getCredentials();
        
        if ($existingCredentials) {
            // Update existing credentials
            return $this->db->update(
                DB_PREFIX . "api_credentials",
                $credentials,
                "service = ?",
                [$sourceName]
            );
        } else {
            // Insert new credentials
            $credentials['service'] = $sourceName;
            
            $insertId = $this->db->insert(DB_PREFIX . "api_credentials", $credentials);
            return $insertId !== false;
        }
    }
    
    /**
     * Log an API request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param array $response API response
     * @param bool $success Whether the request was successful
     * @return void
     */
    protected function logApiRequest($endpoint, $params, $response, $success) {
        $sourceName = $this->getName();
        
        $message = sprintf(
            "[%s API] Request to %s %s. Params: %s, Response: %s",
            $sourceName,
            $endpoint,
            $success ? 'succeeded' : 'failed',
            json_encode($params),
            is_array($response) ? json_encode($response) : $response
        );
        
        logMessage($message, $success ? 'info' : 'error');
    }
}
?>
