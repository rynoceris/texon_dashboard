<?php
// install.php
// This file handles the initial installation of the application

// Check if installation is already completed
if (file_exists('install.lock')) {
    die('Installation is already completed. If you need to reinstall, please delete the install.lock file.');
}

// Load configuration
require_once 'config/config.php';

// Function to create database tables
function createTables($host, $dbname, $user, $pass, $prefix) {
    try {
        // Create database connection
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to the specific database
        $pdo->exec("USE `$dbname`");
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create sessions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}sessions` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create school_data table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}school_data` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_name VARCHAR(255) NOT NULL,
                domain VARCHAR(255) NOT NULL UNIQUE,
                csd_school_id INT NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                staff_count INT DEFAULT 0,
                order_count INT DEFAULT 0,
                order_total DECIMAL(10,2) DEFAULT 0.00,
                email_count INT DEFAULT 0,
                open_rate DECIMAL(5,2) DEFAULT 0.00,
                click_rate DECIMAL(5,2) DEFAULT 0.00,
                order_rate DECIMAL(5,2) DEFAULT 0.00,
                data_json LONGTEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create api_credentials table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}api_credentials` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service VARCHAR(50) NOT NULL UNIQUE,
                api_key VARCHAR(255),
                api_secret VARCHAR(255),
                access_token TEXT,
                refresh_token TEXT,
                expires_at TIMESTAMP NULL,
                additional_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// Function to create admin user
function createAdminUser($host, $dbname, $user, $pass, $prefix, $email, $password, $firstName, $lastName) {
    try {
        // Create database connection
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return "User with email $email already exists.";
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin user
        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}users (email, password, first_name, last_name, role, active) 
             VALUES (?, ?, ?, ?, 'admin', 1)"
        );
        
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
        
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// Process installation
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get admin user details
    $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    $adminPassword = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    $adminFirstName = isset($_POST['admin_first_name']) ? trim($_POST['admin_first_name']) : '';
    $adminLastName = isset($_POST['admin_last_name']) ? trim($_POST['admin_last_name']) : '';
    
    // Validate admin email (must be from company domain)
    $domain = substr(strrchr($adminEmail, "@"), 1);
    if ($domain !== COMPANY_DOMAIN) {
        $error = "Admin email must be a @" . COMPANY_DOMAIN . " email address.";
    } elseif (empty($adminPassword)) {
        $error = "Admin password is required.";
    } else {
        // Create database tables
        $tablesResult = createTables(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PREFIX);
        
        if ($tablesResult !== true) {
            $error = "Failed to create database tables: " . $tablesResult;
        } else {
            // Create admin user
            $userResult = createAdminUser(
                DB_HOST, 
                DB_NAME, 
                DB_USER, 
                DB_PASS, 
                DB_PREFIX, 
                $adminEmail, 
                $adminPassword, 
                $adminFirstName, 
                $adminLastName
            );
            
            if ($userResult !== true) {
                $error = "Failed to create admin user: " . $userResult;
            } else {
                // Create installation lock file
                file_put_contents('install.lock', date('Y-m-d H:i:s'));
                
                $success = "Installation completed successfully! You can now <a href='login.php'>login</a> with your admin account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Texon Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 50px 0;
        }
        .install-container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .install-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-install {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #007bff;
            border: none;
        }
        .alert {
            margin-bottom: 20px;
        }
        .install-footer {
            margin-top: 30px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="install-header">
                <img src="assets/images/logo.png" alt="Texon Dashboard Logo">
                <h1>Texon Dashboard Installation</h1>
                <p>Welcome to the installation process for the Texon Dashboard. Follow the steps below to complete the setup.</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <h3>Database Configuration</h3>
                    <p>The following database settings are configured in config.php:</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Database Host</label>
                                <input type="text" class="form-control" value="<?php echo DB_HOST; ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Database Name</label>
                                <input type="text" class="form-control" value="<?php echo DB_NAME; ?>" disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Database User</label>
                                <input type="text" class="form-control" value="<?php echo DB_USER; ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Table Prefix</label>
                                <input type="text" class="form-control" value="<?php echo DB_PREFIX; ?>" disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        If you need to change these settings, please edit the config/config.php file.
                    </div>
                    
                    <h3>Admin Account</h3>
                    <p>Create an administrator account for the dashboard:</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_first_name">First Name</label>
                                <input type="text" class="form-control" id="admin_first_name" name="admin_first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admin_last_name">Last Name</label>
                                <input type="text" class="form-control" id="admin_last_name" name="admin_last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email Address</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required 
                               placeholder="Enter your @<?php echo COMPANY_DOMAIN; ?> email">
                        <small class="form-text text-muted">Must be a @<?php echo COMPANY_DOMAIN; ?> email address.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        <small class="form-text text-muted">Choose a strong password.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-install">Install Dashboard</button>
                </form>
            <?php endif; ?>
            
            <div class="install-footer">
                <p>Texon Dashboard &copy; <?php echo date('Y'); ?> Texon</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>