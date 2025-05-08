<?php
// settings.php
// This file displays the settings page for the application

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
$currentUser = $auth->checkAuthenticated();

// Check if user is admin
if ($currentUser['role'] !== 'admin') {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect(APP_URL . '/index.php');
}

// Get flash message
$flashMessage = getFlashMessage();

// Set page title
$pageTitle = 'Settings';

// Include header
include 'views/partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'views/partials/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Settings</h1>
            </div>
            
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>"><?php echo $flashMessage['message']; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- API Configuration Tabs -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="apiConfigTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="csd-tab" data-bs-toggle="tab" href="#csd" role="tab" aria-controls="csd" aria-selected="true">College Sports Directory</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="brightpearl-tab" data-bs-toggle="tab" href="#brightpearl" role="tab" aria-controls="brightpearl" aria-selected="false">Brightpearl</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="klaviyo-tab" data-bs-toggle="tab" href="#klaviyo" role="tab" aria-controls="klaviyo" aria-selected="false">Klaviyo</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="apiConfigTabsContent">
                                <!-- College Sports Directory -->
                                <div class="tab-pane fade show active" id="csd" role="tabpanel" aria-labelledby="csd-tab">
                                    <form id="csd-config-form" action="includes/api/save_credentials.php" method="post">
                                        <input type="hidden" name="service" value="csd">
                                        
                                        <div class="form-group mb-3">
                                            <label for="csd-db-host">Database Host</label>
                                            <input type="text" class="form-control" id="csd-db-host" name="db_host" placeholder="localhost">
                                            <small class="form-text text-muted">The hostname of the College Sports Directory database.</small>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="csd-db-name">Database Name</label>
                                            <input type="text" class="form-control" id="csd-db-name" name="db_name" placeholder="college_sports_directory">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="csd-db-user">Database User</label>
                                            <input type="text" class="form-control" id="csd-db-user" name="db_user" placeholder="username">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="csd-db-pass">Database Password</label>
                                            <input type="password" class="form-control" id="csd-db-pass" name="db_pass" placeholder="password">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="csd-db-prefix">Table Prefix</label>
                                            <input type="text" class="form-control" id="csd-db-prefix" name="db_prefix" placeholder="csd_">
                                            <small class="form-text text-muted">The prefix used for tables in the database.</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                                        <button type="button" class="btn btn-secondary" id="test-csd-connection">Test Connection</button>
                                    </form>
                                </div>
                                
                                <!-- Brightpearl -->
                                <div class="tab-pane fade" id="brightpearl" role="tabpanel" aria-labelledby="brightpearl-tab">
                                    <form id="brightpearl-config-form" action="includes/api/save_credentials.php" method="post">
                                        <input type="hidden" name="service" value="brightpearl">
                                        
                                        <div class="form-group mb-3">
                                            <label for="brightpearl-account-code">Account Code</label>
                                            <input type="text" class="form-control" id="brightpearl-account-code" name="account_code" placeholder="texon">
                                            <small class="form-text text-muted">Your Brightpearl account code.</small>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="brightpearl-api-key">API Key</label>
                                            <input type="text" class="form-control" id="brightpearl-api-key" name="api_key" placeholder="Enter API key">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="brightpearl-access-token">Access Token</label>
                                            <input type="text" class="form-control" id="brightpearl-access-token" name="access_token" placeholder="Enter access token">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="brightpearl-refresh-token">Refresh Token</label>
                                            <input type="text" class="form-control" id="brightpearl-refresh-token" name="refresh_token" placeholder="Enter refresh token">
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="brightpearl-token-expires">Token Expiration</label>
                                            <input type="datetime-local" class="form-control" id="brightpearl-token-expires" name="token_expires">
                                            <small class="form-text text-muted">When the access token expires.</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                                        <button type="button" class="btn btn-secondary" id="test-brightpearl-connection">Test Connection</button>
                                    </form>
                                </div>
                                
                                <!-- Klaviyo -->
                                <div class="tab-pane fade" id="klaviyo" role="tabpanel" aria-labelledby="klaviyo-tab">
                                    <form id="klaviyo-config-form" action="includes/api/save_credentials.php" method="post">
                                        <input type="hidden" name="service" value="klaviyo">
                                        
                                        <div class="form-group mb-3">
                                            <label for="klaviyo-api-key">API Key</label>
                                            <input type="text" class="form-control" id="klaviyo-api-key" name="api_key" placeholder="Enter API key">
                                            <small class="form-text text-muted">Your Klaviyo API key.</small>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label for="klaviyo-api-version">API Version</label>
                                            <input type="text" class="form-control" id="klaviyo-api-version" name="api_version" placeholder="2023-09-15" value="2023-09-15">
                                            <small class="form-text text-muted">Klaviyo API version to use.</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                                        <button type="button" class="btn btn-secondary" id="test-klaviyo-connection">Test Connection</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- General Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">General Settings</h5>
                        </div>
                        <div class="card-body">
                            <form id="general-settings-form" action="includes/api/save_settings.php" method="post">
                                <div class="form-group mb-3">
                                    <label for="app-name">Application Name</label>
                                    <input type="text" class="form-control" id="app-name" name="app_name" value="<?php echo APP_NAME; ?>">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="app-url">Application URL</label>
                                    <input type="text" class="form-control" id="app-url" name="app_url" value="<?php echo APP_URL; ?>">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="company-domain">Company Domain</label>
                                    <input type="text" class="form-control" id="company-domain" name="company_domain" value="<?php echo COMPANY_DOMAIN; ?>">
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="debug-mode" name="debug_mode" <?php echo DEBUG_MODE ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="debug-mode">Debug Mode</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Connection Status -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Connection Status</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    College Sports Directory
                                    <span class="badge bg-primary rounded-pill" id="csd-status-indicator">Checking...</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Brightpearl API
                                    <span class="badge bg-primary rounded-pill" id="brightpearl-status-indicator">Checking...</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Klaviyo API
                                    <span class="badge bg-primary rounded-pill" id="klaviyo-status-indicator">Checking...</span>
                                </li>
                            </ul>
                            
                            <button class="btn btn-outline-primary btn-sm mt-3 w-100" id="refresh-status-btn">
                                Refresh Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    // Check data source status on page load
    checkDataSourceStatus();
    
    // Refresh status button
    $('#refresh-status-btn').on('click', function() {
        checkDataSourceStatus();
    });
    
    // Test connection buttons
    $('#test-csd-connection').on('click', function() {
        testConnection('csd');
    });
    
    $('#test-brightpearl-connection').on('click', function() {
        testConnection('brightpearl');
    });
    
    $('#test-klaviyo-connection').on('click', function() {
        testConnection('klaviyo');
    });
    
    // Form submissions
    $('#csd-config-form, #brightpearl-config-form, #klaviyo-config-form, #general-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        
        // Disable submit button
        form.find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        
        // Send AJAX request
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Re-enable submit button
                form.find('button[type="submit"]').prop('disabled', false).text('Save Configuration');
                
                if (response.success) {
                    showNotification(response.message, 'success');
                    
                    // Refresh status after saving
                    setTimeout(function() {
                        checkDataSourceStatus();
                    }, 1000);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                // Re-enable submit button
                form.find('button[type="submit"]').prop('disabled', false).text('Save Configuration');
                
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Function to check data source status
    function checkDataSourceStatus() {
        // Update status indicators
        $('#csd-status-indicator, #brightpearl-status-indicator, #klaviyo-status-indicator').removeClass('bg-success bg-warning bg-danger').addClass('bg-primary').text('Checking...');
        
        // Send AJAX request
        $.ajax({
            url: 'includes/api/check_sources.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update status indicators
                    if (response.csd) {
                        $('#csd-status-indicator').removeClass('bg-primary').addClass('bg-success').text('Connected');
                    } else {
                        $('#csd-status-indicator').removeClass('bg-primary').addClass('bg-warning').text('Not Connected');
                    }
                    
                    if (response.brightpearl) {
                        $('#brightpearl-status-indicator').removeClass('bg-primary').addClass('bg-success').text('Connected');
                    } else {
                        $('#brightpearl-status-indicator').removeClass('bg-primary').addClass('bg-warning').text('Not Connected');
                    }
                    
                    if (response.klaviyo) {
                        $('#klaviyo-status-indicator').removeClass('bg-primary').addClass('bg-success').text('Connected');
                    } else {
                        $('#klaviyo-status-indicator').removeClass('bg-primary').addClass('bg-warning').text('Not Connected');
                    }
                } else {
                    // Update status indicators to error
                    $('#csd-status-indicator, #brightpearl-status-indicator, #klaviyo-status-indicator').removeClass('bg-primary').addClass('bg-danger').text('Error');
                    
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                // Update status indicators to error
                $('#csd-status-indicator, #brightpearl-status-indicator, #klaviyo-status-indicator').removeClass('bg-primary').addClass('bg-danger').text('Error');
                
                showNotification('An error occurred while checking data source status.', 'error');
            }
        });
    }
    
    // Function to test connection
    function testConnection(service) {
        var form;
        var button;
        
        switch (service) {
            case 'csd':
                form = $('#csd-config-form');
                button = $('#test-csd-connection');
                break;
            case 'brightpearl':
                form = $('#brightpearl-config-form');
                button = $('#test-brightpearl-connection');
                break;
            case 'klaviyo':
                form = $('#klaviyo-config-form');
                button = $('#test-klaviyo-connection');
                break;
            default:
                return;
        }
        
        // Disable button
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
        
        // Send AJAX request
        $.ajax({
            url: 'includes/api/test_connection.php',
            type: 'POST',
            data: form.serialize() + '&test=1',
            dataType: 'json',
            success: function(response) {
                // Re-enable button
                button.prop('disabled', false).text('Test Connection');
                
                if (response.success) {
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                // Re-enable button
                button.prop('disabled', false).text('Test Connection');
                
                showNotification('An error occurred while testing the connection.', 'error');
            }
        });
    }
});
</script>

<?php include 'views/partials/footer.php'; ?>
