<?php
// sources/klaviyo_source.php
// Klaviyo API data source implementation

// Make sure we have the DataSource base class
require_once dirname(__FILE__) . '/data_source.php';

/**
 * Klaviyo Data Source
 * This class handles the integration with the Klaviyo API
 */
class KlaviyoSource extends DataSource {
    private $apiUrl;
    private $apiVersion;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->apiUrl = KLAVIYO_API_URL;
        $this->apiVersion = KLAVIYO_API_VERSION;
    }
    
    /**
     * Get the name of this data source
     * 
     * @return string Source name
     */
    public function getName() {
        return 'klaviyo';
    }
    
    /**
     * Check if the data source is available
     * 
     * @return bool True if available, false otherwise
     */
    public function isAvailable() {
        $credentials = $this->getCredentials();
        
        return $credentials && !empty($credentials['api_key']);
    }
    
    /**
     * Get school data from Klaviyo
     * 
     * @param string $domain School email domain
     * @return array School data
     */
    public function getSchoolData($domain) {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Klaviyo API is not configured',
                'data' => null
            ];
        }
        
        // Get profiles with the school's email domain
        $profiles = $this->searchProfilesByEmailDomain($domain);
        
        if (!$profiles['success']) {
            return $profiles;
        }
        
        // Get campaign metrics for these profiles
        $metrics = $this->getMetricsForProfiles($domain);
        
        if (!$metrics['success']) {
            return $metrics;
        }
        
        return [
            'success' => true,
            'message' => 'School data retrieved successfully',
            'data' => [
                'profiles' => $profiles['data'],
                'total_profiles' => $profiles['data']['total'] ?? 0,
                'metrics' => $metrics['data'],
                'email_count' => $metrics['data']['sent'] ?? 0,
                'open_rate' => $metrics['data']['open_rate'] ?? 0,
                'click_rate' => $metrics['data']['click_rate'] ?? 0,
                'order_rate' => $metrics['data']['conversion_rate'] ?? 0
            ]
        ];
    }
    
    /**
     * Search for profiles by email domain
     * 
     * @param string $domain Email domain to search for
     * @return array Search results
     */
    private function searchProfilesByEmailDomain($domain) {
        $credentials = $this->getCredentials();
        
        // Endpoint for profile search
        $endpoint = "{$this->apiUrl}/profiles";
        
        // Search parameters
        $params = [
            'filter' => "equals(email,\"*@{$domain}\")"
        ];
        
        // Make API request
        $response = $this->makeApiRequest('GET', $endpoint, $params);
        
        return $response;
    }
    
    /**
     * Get metrics for profiles with a specific domain
     * 
     * @param string $domain Email domain
     * @return array Metrics data
     */
    private function getMetricsForProfiles($domain) {
        $credentials = $this->getCredentials();
        
        // Endpoint for metrics
        $endpoint = "{$this->apiUrl}/metrics/email";
        
        // Search parameters
        $params = [
            'filter' => "contains(profile.email,\"@{$domain}\")"
        ];
        
        // Make API request
        $response = $this->makeApiRequest('GET', $endpoint, $params);
        
        return $response;
    }
    
    /**
     * Make an API request to Klaviyo
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array API response
     */
    private function makeApiRequest($method, $endpoint, $params = []) {
        $credentials = $this->getCredentials();
        
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Revision: ' . $this->apiVersion,
            'Authorization: Klaviyo-API-Key ' . $credentials['api_key']
        ];
        
        // Add query parameters to URL
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        $success = $statusCode >= 200 && $statusCode < 300;
        
        // Log the API request
        $this->logApiRequest($endpoint, $params, $response, $success);
        
        if (!$success) {
            return [
                'success' => false,
                'message' => "API request failed: {$error}",
                'status_code' => $statusCode,
                'data' => null
            ];
        }
        
        $responseData = json_decode($response, true);
        
        return [
            'success' => true,
            'message' => 'API request successful',
            'data' => $responseData
        ];
    }
}
?>
