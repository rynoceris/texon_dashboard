<?php
// brightpearl.php
// This file displays the Brightpearl API configuration page

// Initialize session
session_start();

// Load configuration
require_once 'config/config.php';

// Load required files
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'sources/data_source.php';
require_once 'sources/brightpearl_source.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->checkAuthenticated();

// Set page title
$pageTitle = 'Brightpearl API Configuration';

// Get saved credentials
$db = Database::getInstance();
$brightpearlCredentials = $db->selectOne(
	"SELECT * FROM " . DB_PREFIX . "api_credentials WHERE service = ?",
	['brightpearl']
);
$bpAdditionalData = [];
if ($brightpearlCredentials && !empty($brightpearlCredentials['additional_data'])) {
	$bpAdditionalData = json_decode($brightpearlCredentials['additional_data'], true);
}

// Check if Brightpearl is available
$brightpearlSource = new BrightpearlSource();
$brightpearlAvailable = $brightpearlSource->isAvailable();

// Get flash message
$flashMessage = getFlashMessage();

// Include header
include 'views/partials/header.php';
?>

<div class="container-fluid">
	<div class="row">
		<?php include 'views/partials/sidebar.php'; ?>
		
		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
			<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
				<h1 class="h2">Brightpearl API Configuration</h1>
				<div class="btn-toolbar mb-2 mb-md-0">
					<div class="btn-group me-2">
						<a href="settings.php" class="btn btn-sm btn-outline-secondary">Back to Settings</a>
					</div>
				</div>
			</div>
			
			<?php if ($flashMessage): ?>
				<div class="alert alert-<?php echo $flashMessage['type']; ?>"><?php echo $flashMessage['message']; ?></div>
			<?php endif; ?>
			
			<div class="row">
				<div class="col-md-6">
					<div class="card mb-4">
						<div class="card-header">
							<h5 class="mb-0">Connection Status</h5>
						</div>
						<div class="card-body">
							<p>
								<strong>Status:</strong>
								<?php if ($brightpearlAvailable): ?>
									<span class="badge bg-success">Connected</span>
								<?php else: ?>
									<span class="badge bg-warning">Not Connected</span>
								<?php endif; ?>
							</p>
							
							<?php if ($brightpearlAvailable): ?>
								<p>The dashboard is successfully connected to the Brightpearl API.</p>
								<button type="button" class="btn btn-outline-primary" id="test-brightpearl-connection">Test Connection</button>
							<?php else: ?>
								<p>The dashboard is not connected to the Brightpearl API. Please configure the connection below.</p>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">Brightpearl API Configuration</h5>
						</div>
						<div class="card-body">
							<form id="brightpearl-config-form" action="includes/api/save_credentials.php" method="post">
								<input type="hidden" name="service" value="brightpearl">
								
								<div class="form-group mb-3">
									<label for="brightpearl-account-code">Account Code</label>
									<input type="text" class="form-control" id="brightpearl-account-code" name="account_code" 
										   value="<?php echo htmlspecialchars($bpAdditionalData['account_code'] ?? ''); ?>"
										   placeholder="texon">
									<small class="form-text text-muted">Your Brightpearl account code (e.g., "texon").</small>
								</div>
								
								<div class="form-group mb-3">
									<label for="brightpearl-app-ref">App Reference</label>
									<input type="text" class="form-control" id="brightpearl-app-ref" name="app_ref" 
										   value="<?php echo htmlspecialchars($bpAdditionalData['app_ref'] ?? 'texon_dashboard'); ?>"
										   placeholder="texon_dashboard">
									<small class="form-text text-muted">The identifier you chose when creating your Brightpearl private app.</small>
								</div>
								
								<div class="form-group mb-3">
									<label for="brightpearl-app-token">Staff Token</label>
									<input type="text" class="form-control" id="brightpearl-app-token" name="app_token" 
										   value="<?php echo htmlspecialchars($brightpearlCredentials['api_key'] ?? ''); ?>"
										   placeholder="Enter your Staff Token">
									<small class="form-text text-muted">The token provided when you created a Staff App in Brightpearl.</small>
								</div>
								
								<button type="submit" class="btn btn-primary">Save Configuration</button>
								<button type="button" class="btn btn-secondary" id="test-brightpearl-connection">Test Connection</button>
							</form>
						</div>
					</div>
				</div>
				
				<div class="col-md-6">
					<div class="card mb-4">
						<div class="card-header">
							<h5 class="mb-0">About Brightpearl Integration</h5>
						</div>
						<div class="card-body">
							<p>
								Brightpearl is an Omnichannel retail management system that provides complete visibility
								and control over your inventory, orders, customers, and more.
							</p>
							
							<p>
								This integration allows the dashboard to:
							</p>
							
							<ul>
								<li>Search for customers with specific school email domains</li>
								<li>Retrieve order information for those customers</li>
								<li>Calculate total order counts and values</li>
							</ul>
							
							<h6 class="mt-3">Setting Up a Brightpearl Private App</h6>
							
							<ol>
								<li>Log into your Brightpearl account</li>
								<li>Go to <strong>App Store > Private apps</strong></li>
								<li>Click <strong>"Add private app"</strong></li>
								<li>Select <strong>"Staff app"</strong> (System apps are being deprecated)</li>
								<li>Enter a name (e.g., "Texon Dashboard") and identifier (e.g., "texon_dashboard")</li>
								<li>Save and copy the provided token</li>
							</ol>
							
							<div class="alert alert-info">
								<i class="fas fa-info-circle"></i> If you encounter connection issues, please contact your Brightpearl
								administrator to ensure the API is accessible and that your account has the necessary permissions.
							</div>
						</div>
					</div>
					
					<?php if ($brightpearlAvailable): ?>
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">API Information</h5>
						</div>
						<div class="card-body">
							<p><strong>API URL:</strong> <?php echo BRIGHTPEARL_API_URL; ?></p>
							<p><strong>Account Code:</strong> <?php echo htmlspecialchars($bpAdditionalData['account_code'] ?? ''); ?></p>
							<p><strong>App Reference:</strong> <?php echo htmlspecialchars($bpAdditionalData['app_ref'] ?? ''); ?></p>
							
							<div class="alert alert-info mt-3">
								<strong>Note:</strong> To protect your security, the full API token is not displayed.
							</div>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</main>
	</div>
</div>

<script>
$(document).ready(function() {
	// Test connection button
	$('#test-brightpearl-connection').on('click', function() {
		testConnection('brightpearl');
	});
	
	// Function to test connection
	function testConnection(service) {
		var formData = {
			service: 'brightpearl',
			account_code: $('#brightpearl-account-code').val(),
			app_ref: $('#brightpearl-app-ref').val(),
			app_token: $('#brightpearl-app-token').val(),
			test: 1
		};
		
		// Disable button
		$('#test-brightpearl-connection').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
		
		// Send AJAX request
		$.ajax({
			url: 'includes/api/test_connection.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				// Re-enable button
				$('#test-brightpearl-connection').prop('disabled', false).text('Test Connection');
				
				if (response.success) {
					showNotification(response.message, 'success');
				} else {
					showNotification(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				// Re-enable button
				$('#test-brightpearl-connection').prop('disabled', false).text('Test Connection');
				
				// Log detailed error information
				console.error("AJAX Error:", xhr.status, xhr.responseText);
				
				var errorMessage = 'Error testing connection';
				
				if (xhr.responseText) {
					try {
						var response = JSON.parse(xhr.responseText);
						errorMessage += ': ' + (response.message || error);
					} catch (e) {
						errorMessage += ': ' + xhr.responseText;
					}
				} else {
					errorMessage += ': ' + error;
				}
				
				showNotification(errorMessage, 'error');
			}
		});
	}
	
	// Function to show notification
	function showNotification(message, type = 'info', duration = 5000) {
		// Create notification element if it doesn't exist
		if ($('#notification-container').length === 0) {
			$('body').append('<div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
		}
		
		// Set notification class based on type
		let notificationClass = 'bg-info';
		switch (type) {
			case 'success':
				notificationClass = 'bg-success';
				break;
			case 'error':
				notificationClass = 'bg-danger';
				break;
			case 'warning':
				notificationClass = 'bg-warning';
				break;
		}
		
		// Create a unique ID for this notification
		const notificationId = 'notification-' + Date.now();
		
		// Create notification HTML
		const notificationHtml = `
			<div id="${notificationId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${duration}">
				<div class="toast-header ${notificationClass} text-white">
					<strong class="me-auto">Notification</strong>
					<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
				</div>
				<div class="toast-body">
					${message}
				</div>
			</div>
		`;
		
		// Append notification to container
		$('#notification-container').append(notificationHtml);
		
		// Initialize and show the toast
		var toast = new bootstrap.Toast(document.getElementById(notificationId));
		toast.show();
		
		// Remove notification after it's hidden
		$(`#${notificationId}`).on('hidden.bs.toast', function() {
			$(this).remove();
		});
	}
});
</script>

<?php include 'views/partials/footer.php'; ?>