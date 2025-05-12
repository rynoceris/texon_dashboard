<?php
// csd.php
// This file displays the College Sports Directory configuration page

// Initialize session
session_start();

// Load configuration
require_once 'config/config.php';

// Load required files
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'sources/data_source.php';
require_once 'sources/csd_source.php';

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->checkAuthenticated();

// Set page title
$pageTitle = 'College Sports Directory Configuration';

// Get saved credentials
$db = Database::getInstance();
$csdCredentials = $db->selectOne(
	"SELECT * FROM " . DB_PREFIX . "api_credentials WHERE service = ?",
	['csd']
);
$csdSettings = [];
if ($csdCredentials && !empty($csdCredentials['additional_data'])) {
	$csdSettings = json_decode($csdCredentials['additional_data'], true);
}

// Check if CSD is available
$csdSource = new CSDSource();
$csdAvailable = $csdSource->isAvailable();

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
				<h1 class="h2">College Sports Directory Configuration</h1>
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
								<?php if ($csdAvailable): ?>
									<span class="badge bg-success">Connected</span>
								<?php else: ?>
									<span class="badge bg-warning">Not Connected</span>
								<?php endif; ?>
							</p>
							
							<?php if ($csdAvailable): ?>
								<p>The dashboard is successfully connected to the College Sports Directory database.</p>
								<button type="button" class="btn btn-outline-primary" id="test-csd-connection">Test Connection</button>
							<?php else: ?>
								<p>The dashboard is not connected to the College Sports Directory database. Please configure the connection below.</p>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">Database Configuration</h5>
						</div>
						<div class="card-body">
							<form id="csd-config-form" action="includes/api/save_credentials.php" method="post">
								<input type="hidden" name="service" value="csd">
								
								<div class="form-group mb-3">
									<label for="csd-db-host">Database Host</label>
									<input type="text" class="form-control" id="csd-db-host" name="db_host" 
										   value="<?php echo htmlspecialchars($csdSettings['db_host'] ?? ''); ?>" 
										   placeholder="localhost">
									<small class="form-text text-muted">The hostname of the College Sports Directory database.</small>
								</div>
								
								<div class="form-group mb-3">
									<label for="csd-db-name">Database Name</label>
									<input type="text" class="form-control" id="csd-db-name" name="db_name" 
										   value="<?php echo htmlspecialchars($csdSettings['db_name'] ?? ''); ?>"
										   placeholder="college_sports_directory">
								</div>
								
								<div class="form-group mb-3">
									<label for="csd-db-user">Database User</label>
									<input type="text" class="form-control" id="csd-db-user" name="db_user" 
										   value="<?php echo htmlspecialchars($csdSettings['db_user'] ?? ''); ?>"
										   placeholder="username">
								</div>
								
								<div class="form-group mb-3">
									<label for="csd-db-pass">Database Password</label>
									<input type="password" class="form-control" id="csd-db-pass" name="db_pass" 
										   value="<?php echo htmlspecialchars($csdSettings['db_pass'] ?? ''); ?>"
										   placeholder="password">
								</div>
								
								<div class="form-group mb-3">
									<label for="csd-db-prefix">Table Prefix</label>
									<input type="text" class="form-control" id="csd-db-prefix" name="db_prefix" 
										   value="<?php echo htmlspecialchars($csdSettings['db_prefix'] ?? ''); ?>"
										   placeholder="csd_">
									<small class="form-text text-muted">The prefix used for tables in the database.</small>
								</div>
								
								<button type="submit" class="btn btn-primary">Save Configuration</button>
								<button type="button" class="btn btn-secondary" id="test-csd-connection">Test Connection</button>
							</form>
						</div>
					</div>
				</div>
				
				<div class="col-md-6">
					<div class="card mb-4">
						<div class="card-header">
							<h5 class="mb-0">About College Sports Directory</h5>
						</div>
						<div class="card-body">
							<p>
								The College Sports Directory database contains information about colleges, universities, and their athletic departments.
								This includes:
							</p>
							
							<ul>
								<li>School information (name, location, division, etc.)</li>
								<li>Athletic staff details</li>
								<li>Department structure</li>
							</ul>
							
							<p>
								This data is used by the dashboard to provide contextual information about schools
								and to enrich the data from other sources.
							</p>
							
							<h6 class="mt-3">Required Database Tables</h6>
							
							<ul>
								<li><code>csd_schools</code> - Contains school information</li>
								<li><code>csd_staff</code> - Contains staff information</li>
								<li><code>csd_school_staff</code> - Maps staff to schools</li>
							</ul>
						</div>
					</div>
					
					<?php if ($csdAvailable): ?>
					<div class="card">
						<div class="card-header">
							<h5 class="mb-0">Database Information</h5>
						</div>
						<div class="card-body">
							<?php
							try {
								$pdo = new PDO(
									'mysql:host=' . $csdSettings['db_host'] . ';dbname=' . $csdSettings['db_name'] . ';charset=utf8mb4',
									$csdSettings['db_user'],
									$csdSettings['db_pass'],
									[
										PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
										PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
										PDO::ATTR_EMULATE_PREPARES => false
									]
								);
								
								// Get table prefix
								$tablePrefix = $csdSettings['db_prefix'] ?: 'csd_';
								
								// Get school count
								$stmt = $pdo->query("SELECT COUNT(*) as count FROM {$tablePrefix}schools");
								$schoolCount = $stmt->fetch()['count'];
								
								// Get staff count
								$stmt = $pdo->query("SELECT COUNT(*) as count FROM {$tablePrefix}staff");
								$staffCount = $stmt->fetch()['count'];
								
								// Get school-staff relationships count
								$stmt = $pdo->query("SELECT COUNT(*) as count FROM {$tablePrefix}school_staff");
								$relationshipCount = $stmt->fetch()['count'];
							} catch (PDOException $e) {
								$error = $e->getMessage();
							}
							?>
							
							<?php if (isset($error)): ?>
								<div class="alert alert-danger">Error: <?php echo $error; ?></div>
							<?php else: ?>
								<div class="row">
									<div class="col-md-4 text-center mb-3">
										<div class="stat-container">
											<div class="stat-value"><?php echo number_format($schoolCount); ?></div>
											<div class="stat-label">Schools</div>
										</div>
									</div>
									<div class="col-md-4 text-center mb-3">
										<div class="stat-container">
											<div class="stat-value"><?php echo number_format($staffCount); ?></div>
											<div class="stat-label">Staff Members</div>
										</div>
									</div>
									<div class="col-md-4 text-center mb-3">
										<div class="stat-container">
											<div class="stat-value"><?php echo number_format($relationshipCount); ?></div>
											<div class="stat-label">Relationships</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
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
	$('#test-csd-connection').on('click', function() {
		testConnection('csd');
	});
	
	// Function to test connection
	function testConnection(service) {
		var formData = {
			service: 'csd',
			db_host: $('#csd-db-host').val(),
			db_name: $('#csd-db-name').val(),
			db_user: $('#csd-db-user').val(),
			db_pass: $('#csd-db-pass').val(),
			db_prefix: $('#csd-db-prefix').val(),
			test: 1
		};
		
		// Disable button
		$('#test-csd-connection').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
		
		// Send AJAX request
		$.ajax({
			url: 'includes/api/test_connection.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				// Re-enable button
				$('#test-csd-connection').prop('disabled', false).text('Test Connection');
				
				if (response.success) {
					showNotification(response.message, 'success');
				} else {
					showNotification(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				// Re-enable button
				$('#test-csd-connection').prop('disabled', false).text('Test Connection');
				
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