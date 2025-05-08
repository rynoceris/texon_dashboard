<?php
// includes/auth.php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $password) {
        // Check if email is from company domain
        if (!$this->validateEmailDomain($email)) {
            return [
                'success' => false,
                'message' => 'Only @' . COMPANY_DOMAIN . ' email addresses are allowed to login.'
            ];
        }
        
        // Find user
        $user = $this->findUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Check if user is active
        if ($user['active'] != 1) {
            return [
                'success' => false,
                'message' => 'Your account is inactive. Please contact an administrator.'
            ];
        }
        
        // Create session
        $sessionToken = $this->createSession($user['id']);
        
        // Update last login timestamp
        $this->db->update(DB_PREFIX . 'users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role']
            ],
            'token' => $sessionToken
        ];
    }
    
    public function register($email, $password, $firstName, $lastName, $role = 'user') {
        // Check if email is from company domain
        if (!$this->validateEmailDomain($email)) {
            return [
                'success' => false,
                'message' => 'Only @' . COMPANY_DOMAIN . ' email addresses are allowed to register.'
            ];
        }
        
        // Check if user already exists
        $existingUser = $this->findUserByEmail($email);
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Email already registered.'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $this->db->insert(DB_PREFIX . 'users', [
            'email' => $email,
            'password' => $hashedPassword,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Failed to create user. Please try again.'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'User registered successfully.',
            'user_id' => $userId
        ];
    }
    
    public function logout() {
        if (isset($_COOKIE['texon_session'])) {
            $token = $_COOKIE['texon_session'];
            
            // Delete session from database
            $this->db->delete(DB_PREFIX . 'sessions', 'session_token = ?', [$token]);
            
            // Clear session cookie
            setcookie('texon_session', '', time() - 3600, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, COOKIE_HTTP);
            
            return true;
        }
        
        return false;
    }
    
    public function getCurrentUser() {
        if (!isset($_COOKIE['texon_session'])) {
            return null;
        }
        
        $token = $_COOKIE['texon_session'];
        
        // Find valid session
        $session = $this->db->selectOne(
            "SELECT * FROM " . DB_PREFIX . "sessions WHERE session_token = ? AND expires_at > NOW()",
            [$token]
        );
        
        if (!$session) {
            // Clear invalid session cookie
            setcookie('texon_session', '', time() - 3600, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, COOKIE_HTTP);
            return null;
        }
        
        // Get user
        $user = $this->db->selectOne(
            "SELECT id, email, first_name, last_name, role FROM " . DB_PREFIX . "users WHERE id = ? AND active = 1",
            [$session['user_id']]
        );
        
        if (!$user) {
            // Clear session for invalid user
            $this->db->delete(DB_PREFIX . 'sessions', 'session_token = ?', [$token]);
            setcookie('texon_session', '', time() - 3600, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, COOKIE_HTTP);
            return null;
        }
        
        return $user;
    }
    
    public function checkAuthenticated() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
        
        return $user;
    }
    
    private function validateEmailDomain($email) {
        $domain = substr(strrchr($email, "@"), 1);
        return $domain === COMPANY_DOMAIN;
    }
    
    private function findUserByEmail($email) {
        return $this->db->selectOne(
            "SELECT * FROM " . DB_PREFIX . "users WHERE email = ?",
            [$email]
        );
    }
    
    private function createSession($userId) {
        // Generate random token
        $token = bin2hex(random_bytes(32));
        
        // Calculate expiration
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        // Get client information
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Insert session
        $this->db->insert(DB_PREFIX . 'sessions', [
            'user_id' => $userId,
            'session_token' => $token,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);
        
        // Set cookie
        setcookie('texon_session', $token, time() + SESSION_LIFETIME, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, COOKIE_HTTP);
        
        return $token;
    }
}
?>
