<?php
// klaviyo.php
// This file displays the Klaviyo API configuration page

// Initialize session
session_start();

// Load configuration
require_once 'config/config.php';

// Load required files
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'sources/data_source.php';
require_once 'sources/klaviyo_source.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->checkAuthenticated();

// Set page title
$pageTitle = 'Klaviyo API Configuration';

// Get saved credentials
$db = Database::getInstance();
$klaviyoCredentials = $db->selectOne(
	"SELECT * FROM " . DB_PREFIX . "api_credentials WHERE service = ?",
	['klaviyo']
);
$klaviyoAdditionalData = [];
if ($klaviyoCredentials && !empty($klaviyoCredentials['additional_data'])) {
	$klaviyoAdditionalData = json_decode($klaviyoCredentials['additional_data'], true);
}

// Check if Klaviyo is available
$klaviyoSource = new KlaviyoSource();
$klaviyoAvailable = $klaviyoSource->isAvailable();

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
				<h1 class="h2">Klaviyo API Configuration</h1>
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
								<?php if ($klaviyoAvailable): ?>
									<span class="badge bg-success">Connected</span>
								<?php else: ?>
									<span class="badge bg-warning">Not Connected</span>
								<?php endif; ?>
							</p>
							
							<?php if ($klaviyoAvailable): ?>
								<p>The dashboard is successfully connected to the Klaviyo API.</p>
								<button type="button" class="btn btn-outline-primary" id="test-klaviyo-connection">Test Connection</button>
							<?php else: ?>
								<p>The dashboard is not connected to the Klaviyo API. Please configure the connection below.</p>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">Klaviyo API Configuration</h5>
						</div>
						<div class="card-body">
							<form id="klaviyo-config-form" action="includes/api/save_credentials.php" method="post">
								<input type="hidden" name="service" value="klaviyo">
								
								<div class="form-group mb-3">
									<label for="klaviyo-api-key">API Key</label>
									<input type="text" class="form-control" id="klaviyo-api-key" name="api_key" 
										   value="<?php echo htmlspecialchars($klaviyoCredentials['api_key'] ?? ''); ?>"
										   placeholder="Enter API key">
									<small class="form-text text-muted">Your Klaviyo API key.</small>
								</div>
								
								<div class="form-group mb-3">
									<label for="klaviyo-api-version">API Version</label>
									<input type="text" class="form-control" id="klaviyo-api-version" name="api_version" 
										   value="<?php echo htmlspecialchars($klaviyoAdditionalData['api_version'] ?? '2023-09-15'); ?>"
										   placeholder="2023-09-15">
									<small class="form-text text-muted">Klaviyo API version to use.</small>
								</div>
								
								<button type="submit" class="btn btn-primary">Save Configuration</button>
								<button type="button" class="btn btn-secondary" id="test-klaviyo-connection">Test Connection</button>
							</form>
						</div>
					</div>
				</div>
				
				<div class="col-md-6">
					<div class="card mb-4">
						<div class="card-header">
							<h5 class="mb-0">About Klaviyo Integration</h5>
						</div>
						<div class="card-body">
							<p>
								Klaviyo is a leading marketing automation platform that helps businesses
								create personalized customer experiences through email, SMS, and other channels.
							</p>
							
							<p>
								This integration allows the dashboard to:
							</p>
							
							<ul>
								<li>Search for email profiles with specific school domains</li>
								<li>Retrieve email campaign metrics for school-specific audiences</li>
								<li>Calculate open rates, click rates, and conversion rates</li>
							</ul>
							
							<h6 class="mt-3">Getting Your Klaviyo API Key</h6>
							
							<ol>
								<li>Log into your Klaviyo account</li>
								<li>Go to <strong>Account > Settings > API Keys</strong></li>
								<li>Create a new <strong>Private API Key</strong> with appropriate permissions</li>
								<li>Copy the generated key and paste it in the configuration form</li>
							</ol>
							
							<div class="alert alert-info">
								<i class="fas fa-info-circle"></i> For this integration to work properly, your API key
								should have at least read access to Profiles, Metrics, and Campaigns.
							</div>
						</div>
					</div>
					
					<?php if ($klaviyoAvailable): ?>
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">API Information</h5>
						</div>
						<div class="card-body">
							<p><strong>API URL:</strong> <?php echo KLAVIYO_API_URL; ?></p>
							<p><strong>API Version:</strong> <?php echo htmlspecialchars($klaviyoAdditionalData['api_version'] ?? '2023-09-15'); ?></p>
							
							<div class="alert alert-info mt-3">
								<strong>Note:</strong> To protect your security, the full API key is not displayed.
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
	$('#test-klaviyo-connection').on('click', function() {
		testConnection('klaviyo');
	});
	
	// Function to test connection
	function testConnection(service) {
		var formData = {
			service: 'klaviyo',
			api_key: $('#klaviyo-api-key').val(),
			api_version: $('#klaviyo-api-version').val(),
			test: 1
		};
		
		// Disable button
		$('#test-klaviyo-connection').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
		
		// Send AJAX request
		$.ajax({
			url: 'includes/api/test_connection.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				// Re-enable button
				$('#test-klaviyo-connection').prop('disabled', false).text('Test Connection');
				
				if (response.success) {
					showNotification(response.message, 'success');
				} else {
					showNotification(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				// Re-enable button
				$('#test-klaviyo-connection').prop('disabled', false).text('Test Connection');
				
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