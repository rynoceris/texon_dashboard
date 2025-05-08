<?php
// sources/brightpearl_source.php
// Brightpearl API data source implementation

// Make sure we have the DataSource base class
require_once dirname(__FILE__) . '/data_source.php';

/**
 * Brightpearl Data Source
 * This class handles the integration with the Brightpearl API
 */
class BrightpearlSource extends DataSource {
    private $apiUrl;
    private $accountCode;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->apiUrl = BRIGHTPEARL_API_URL;
        $this->accountCode = BRIGHTPEARL_ACCOUNT_CODE;
    }
    
    /**
     * Get the name of this data source
     * 
     * @return string Source name
     */
    public function getName() {
        return 'brightpearl';
    }
    
    /**
     * Check if the data source is available
     * 
     * @return bool True if available, false otherwise
     */
    public function isAvailable() {
        $credentials = $this->getCredentials();
        
        if (!$credentials || empty($credentials['api_key']) || empty($credentials['access_token'])) {
            return false;
        }
        
        // Check if token is expired
        if (!empty($credentials['expires_at']) && strtotime($credentials['expires_at']) < time()) {
            // Try to refresh token
            $refreshResult = $this->refreshToken($credentials['refresh_token']);
            
            if (!$refreshResult['success']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get school data from Brightpearl
     * 
     * @param string $domain School email domain
     * @return array School data
     */
    public function getSchoolData($domain) {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Brightpearl API is not configured',
                'data' => null
            ];
        }
        
        $credentials = $this->getCredentials();
        
        // Search for customers with the school's email domain
        $customers = $this->searchCustomersByEmailDomain($domain);
        
        if (!$customers['success']) {
            return $customers;
        }
        
        $customerIds = array_column($customers['data'], 'contactId');
        
        // If no customers found, return empty data
        if (empty($customerIds)) {
            return [
                'success' => true,
                'message' => 'No customers found with this email domain',
                'data' => [
                    'customers' => [],
                    'orders' => [],
                    'total_orders' => 0,
                    'total_value' => 0.00
                ]
            ];
        }
        
        // Get orders for these customers
        $orders = $this->getOrdersForCustomers($customerIds);
        
        // Calculate total order value
        $totalOrderValue = 0.00;
        foreach ($orders['data'] as $order) {
            $totalOrderValue += floatval($order['totalValue'] ?? 0);
        }
        
        return [
            'success' => true,
            'message' => 'School data retrieved successfully',
            'data' => [
                'customers' => $customers['data'],
                'orders' => $orders['data'],
                'total_orders' => count($orders['data']),
                'total_value' => $totalOrderValue
            ]
        ];
    }
    
    /**
     * Search for customers by email domain
     * 
     * @param string $domain Email domain to search for
     * @return array Search results
     */
    private function searchCustomersByEmailDomain($domain) {
        $credentials = $this->getCredentials();
        
        // Endpoint for customer search
        $endpoint = "{$this->apiUrl}/{$this->accountCode}/contact-service/contact-search";
        
        // Search parameters
        $params = [
            'email' => "*@{$domain}"
        ];
        
        // Make API request
        $response = $this->makeApiRequest('GET', $endpoint, $params);
        
        return $response;
    }
    
    /**
     * Get orders for a list of customers
     * 
     * @param array $customerIds Customer IDs to get orders for
     * @return array Order data
     */
    private function getOrdersForCustomers($customerIds) {
        $credentials = $this->getCredentials();
        
        // Endpoint for order search
        $endpoint = "{$this->apiUrl}/{$this->accountCode}/order-service/order-search";
        
        // Search parameters
        $params = [
            'contactId' => implode(',', $customerIds)
        ];
        
        // Make API request
        $response = $this->makeApiRequest('GET', $endpoint, $params);
        
        return $response;
    }
    
    /**
     * Make an API request to Brightpearl
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array $data Request body (for POST/PUT)
     * @return array API response
     */
    private function makeApiRequest($method, $endpoint, $params = [], $data = null) {
        $credentials = $this->getCredentials();
        
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'brightpearl-auth: ' . $credentials['access_token']
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
        
        // Add request body for POST/PUT requests
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
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
    
    /**
     * Refresh an expired access token
     * 
     * @param string $refreshToken Refresh token
     * @return array Refresh result
     */
    private function refreshToken($refreshToken) {
        // Implementation of token refresh logic
        // This would depend on the specific Brightpearl API requirements
        
        // For now, return a failure
        return [
            'success' => false,
            'message' => 'Token refresh not implemented',
            'data' => null
        ];
    }
}
?>
