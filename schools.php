<?php
// Complete updated schools.php file
// This file displays the schools management page

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

// Set page title
$pageTitle = 'Schools Management';

// Get flash message
$flashMessage = getFlashMessage();

// Get all schools
$db = Database::getInstance();
$schools = $db->select("SELECT * FROM " . DB_PREFIX . "school_data ORDER BY school_name ASC");

// Include header
include 'views/partials/header.php';
?>

<div class="container-fluid">
	<div class="row">
		<?php include 'views/partials/sidebar.php'; ?>
		
		<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
			<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
				<h1 class="h2">Schools Management</h1>
				<div class="btn-toolbar mb-2 mb-md-0">
					<button type="button" class="btn btn-sm btn-primary" id="addSchoolBtn" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
						<i class="fas fa-plus"></i> Add School
					</button>
				</div>
			</div>
			
			<?php if ($flashMessage): ?>
				<div class="alert alert-<?php echo $flashMessage['type']; ?>"><?php echo $flashMessage['message']; ?></div>
			<?php endif; ?>
			
			<!-- School Search and Filter -->
			<div class="card mb-4">
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-6">
							<div class="input-group">
								<input type="text" class="form-control" id="schoolSearch" placeholder="Search schools...">
								<button class="btn btn-outline-secondary" type="button" id="searchButton">
									<i class="fas fa-search"></i> Search
								</button>
							</div>
						</div>
						<div class="col-md-3">
							<select class="form-select" id="dataSourceFilter">
								<option value="">All Data Sources</option>
								<option value="csd">College Sports Directory</option>
								<option value="brightpearl">Brightpearl</option>
								<option value="klaviyo">Klaviyo</option>
							</select>
						</div>
						<div class="col-md-3">
							<button class="btn btn-outline-primary w-100" id="refreshAllButton">
								<i class="fas fa-sync-alt"></i> Refresh All Data
							</button>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Schools Table -->
			<div class="card">
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th>School Name</th>
									<th>Domain</th>
									<th>Staff Count</th>
									<th>Orders</th>
									<th>Order Total</th>
									<th>Emails</th>
									<th>Last Updated</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody id="schoolsTableBody">
								<?php if (empty($schools)): ?>
									<tr>
										<td colspan="8" class="text-center">No schools found. <a href="#" class="add-school-link" data-bs-toggle="modal" data-bs-target="#addSchoolModal">Add a school</a>.</td>
									</tr>
								<?php else: ?>
									<?php foreach ($schools as $school): ?>
										<tr class="school-item" data-domain="<?php echo htmlspecialchars($school['domain']); ?>" data-name="<?php echo htmlspecialchars($school['school_name']); ?>">
											<td><?php echo htmlspecialchars($school['school_name']); ?></td>
											<td><?php echo htmlspecialchars($school['domain']); ?></td>
											<td><?php echo $school['staff_count']; ?></td>
											<td><?php echo $school['order_count']; ?></td>
											<td><?php echo formatCurrency($school['order_total']); ?></td>
											<td>
												<?php echo $school['email_count']; ?>
												<?php if ($school['email_count'] > 0): ?>
													<span class="text-muted small">(<?php echo formatPercentage($school['open_rate']); ?> open)</span>
												<?php endif; ?>
											</td>
											<td><?php echo formatDate($school['last_updated']); ?></td>
											<td>
												<div class="btn-group btn-group-sm">
													<a href="school_details.php?domain=<?php echo urlencode($school['domain']); ?>" class="btn btn-sm btn-primary">
														<i class="fas fa-eye"></i> View
													</a>
													<button type="button" class="btn btn-sm btn-success refresh-school-btn" data-domain="<?php echo htmlspecialchars($school['domain']); ?>">
														<i class="fas fa-sync-alt"></i> Refresh
													</button>
													<button type="button" class="btn btn-sm btn-danger delete-school-btn" data-domain="<?php echo htmlspecialchars($school['domain']); ?>" data-name="<?php echo htmlspecialchars($school['school_name']); ?>">
														<i class="fas fa-trash"></i> Delete
													</button>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</main>
	</div>
</div>

<!-- Add School Modal -->
<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addSchoolModalLabel">Add New School</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="add-school-form">
					<div class="mb-3">
						<label for="schoolDomain" class="form-label">School Email Domain</label>
						<input type="text" class="form-control" id="schoolDomain" name="domain" placeholder="albion.edu">
						<div class="form-text">Enter the email domain without the '@' symbol.</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="addSchoolButton">Add School</button>
			</div>
		</div>
	</div>
</div>

<!-- Delete School Modal -->
<div class="modal fade" id="deleteSchoolModal" tabindex="-1" aria-labelledby="deleteSchoolModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteSchoolModalLabel">Confirm Deletion</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>Are you sure you want to delete <strong id="deleteSchoolName"></strong>?</p>
				<p>This action cannot be undone.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete School</button>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	// Make sure Bootstrap Modal is available
	console.log("Bootstrap available:", typeof bootstrap !== 'undefined');
	console.log("Bootstrap Modal available:", typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');
	
	// Ensure Add School button works with manual event listener as backup
	$('#addSchoolBtn').on('click', function() {
		console.log('Add School button clicked');
		if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
			var addSchoolModal = new bootstrap.Modal(document.getElementById('addSchoolModal'));
			addSchoolModal.show();
		}
	});
	
	// Add school form submission
	$('#addSchoolButton').on('click', function() {
		var domain = $('#schoolDomain').val().trim();
		
		if (!domain) {
			showNotification('Please enter a school domain.', 'error');
			return;
		}
		
		// Show loading state
		$(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
		
		// AJAX request to add school
		$.ajax({
			url: 'includes/api/add_school.php',
			type: 'POST',
			data: {
				domain: domain
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showNotification(response.message, 'success');
					// Reload page on success
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showNotification(response.message, 'error');
					// Reset button state
					$('#addSchoolButton').prop('disabled', false).text('Add School');
				}
			},
			error: function(xhr) {
				var message = 'An error occurred. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				showNotification(message, 'error');
				// Reset button state
				$('#addSchoolButton').prop('disabled', false).text('Add School');
			}
		});
	});
	
	// School search
	$('#schoolSearch').on('keyup', function() {
		var searchText = $(this).val().toLowerCase();
		var sourceFilter = $('#dataSourceFilter').val();
		
		filterSchools(searchText, sourceFilter);
	});
	
	// Data source filter
	$('#dataSourceFilter').on('change', function() {
		var searchText = $('#schoolSearch').val().toLowerCase();
		var sourceFilter = $(this).val();
		
		filterSchools(searchText, sourceFilter);
	});
	
	// Function to filter schools
	function filterSchools(searchText, sourceFilter) {
		$('.school-item').each(function() {
			var $row = $(this);
			var schoolName = $row.data('name').toLowerCase();
			var domain = $row.data('domain').toLowerCase();
			var staffCount = parseInt($row.find('td:eq(2)').text());
			var orderCount = parseInt($row.find('td:eq(3)').text());
			var emailCount = parseInt($row.find('td:eq(5)').text());
			
			// Check if matches search text
			var matchesSearch = schoolName.includes(searchText) || domain.includes(searchText);
			
			// Check if matches source filter
			var matchesFilter = true;
			if (sourceFilter === 'csd') {
				matchesFilter = staffCount > 0;
			} else if (sourceFilter === 'brightpearl') {
				matchesFilter = orderCount > 0;
			} else if (sourceFilter === 'klaviyo') {
				matchesFilter = emailCount > 0;
			}
			
			// Show/hide row
			if (matchesSearch && matchesFilter) {
				$row.show();
			} else {
				$row.hide();
			}
		});
	}
	
	// Initialize modals with Bootstrap 5
	var modals = document.querySelectorAll('.modal');
	modals.forEach(function(modalEl) {
		new bootstrap.Modal(modalEl, {
			backdrop: true,
			keyboard: true,
			focus: true
		});
	});
	
	// Also ensure "Add School" link in empty table works
	$('.add-school-link').on('click', function(e) {
		e.preventDefault();
		var addSchoolModal = new bootstrap.Modal(document.getElementById('addSchoolModal'));
		addSchoolModal.show();
	});
	
	// Refresh school button
	$('.refresh-school-btn').on('click', function() {
		var domain = $(this).data('domain');
		refreshSchool(domain, $(this));
	});
	
	// Refresh all schools button
	$('#refreshAllButton').on('click', function() {
		// Show loading state
		$(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Refreshing All...');
		
		// Get all visible domains
		var domains = [];
		$('.school-item:visible').each(function() {
			domains.push($(this).data('domain'));
		});
		
		if (domains.length === 0) {
			showNotification('No schools to refresh.', 'warning');
			$(this).prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Data');
			return;
		}
		
		// Show confirmation if more than 5 schools
		if (domains.length > 5 && !confirm('Are you sure you want to refresh data for ' + domains.length + ' schools? This may take some time.')) {
			$(this).prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Data');
			return;
		}
		
		// Start refreshing schools one by one
		var refreshCount = 0;
		var errorCount = 0;
		
		function refreshNext(index) {
			if (index >= domains.length) {
				// All done
				$('#refreshAllButton').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh All Data');
				
				if (errorCount > 0) {
					showNotification('Refreshed ' + refreshCount + ' schools with ' + errorCount + ' errors.', 'warning');
				} else {
					showNotification('Successfully refreshed ' + refreshCount + ' schools.', 'success');
				}
				
				// Reload page after a delay
				setTimeout(function() {
					location.reload();
				}, 2000);
				
				return;
			}
			
			// Refresh the current school
			$.ajax({
				url: 'includes/api/refresh_school.php',
				type: 'POST',
				data: {
					domain: domains[index]
				},
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						refreshCount++;
					} else {
						errorCount++;
					}
					
					// Update progress
					var progress = Math.round(((index + 1) / domains.length) * 100);
					$('#refreshAllButton').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + progress + '%');
					
					// Refresh next school
					refreshNext(index + 1);
				},
				error: function() {
					errorCount++;
					refreshNext(index + 1);
				}
			});
		}
		
		// Start refreshing
		refreshNext(0);
	});
	
	// Function to refresh a school
	function refreshSchool(domain, $button) {
		// Show loading state
		$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
		
		// AJAX request to refresh school
		$.ajax({
			url: 'includes/api/refresh_school.php',
			type: 'POST',
			data: {
				domain: domain
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showNotification(response.message, 'success');
					// Reload page after a delay
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showNotification(response.message, 'error');
					// Reset button state
					$button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
				}
			},
			error: function(xhr) {
				var message = 'An error occurred. Please try again.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				showNotification(message, 'error');
				// Reset button state
				$button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
			}
		});
	}
	
	// Delete school button
	$('.delete-school-btn').on('click', function() {
		var domain = $(this).data('domain');
		var name = $(this).data('name');
		
		// Set values in the modal
		$('#deleteSchoolName').text(name);
		$('#confirmDeleteButton').data('domain', domain);
		
		// Show the modal - use Bootstrap 5 syntax
		var deleteModal = new bootstrap.Modal(document.getElementById('deleteSchoolModal'));
		deleteModal.show();
	});
	
	// Confirm delete button
	$('#confirmDeleteButton').on('click', function() {
		var domain = $(this).data('domain');
		
		// Show loading state
		$(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
		
		// AJAX request to delete school
		$.ajax({
			url: 'includes/api/delete_school.php',
			type: 'POST',
			data: {
				domain: domain
			},
			dataType: 'json',
			success: function(response) {
				// Reset button state
				$('#confirmDeleteButton').prop('disabled', false).text('Delete School');
				
				if (response.success) {
					showNotification(response.message, 'success');
					// Reload page on success
					setTimeout(function() {
						location.reload();
					}, 1500);
				} else {
					showNotification(response.message, 'error');
				}
			},
			error: function(xhr, status, error) {
				// Reset button state
				$('#confirmDeleteButton').prop('disabled', false).text('Delete School');
				
				// Log detailed error information
				console.error("AJAX Error:", xhr.status, error);
				console.error("Response Text:", xhr.responseText);
				
				var errorMessage = 'Error deleting school';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMessage += ': ' + xhr.responseJSON.message;
				} else if (xhr.responseText) {
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
	});
	
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

// Fix for modal accessibility and backdrop issues
document.addEventListener('DOMContentLoaded', function() {
	// Get references to the modals
	const addSchoolModal = document.getElementById('addSchoolModal');
	const deleteSchoolModal = document.getElementById('deleteSchoolModal');
	
	// Helper function to properly clean up modal elements
	function cleanupModal(modalEl) {
		// Remove modal-backdrop if it exists
		const backdrop = document.querySelector('.modal-backdrop');
		if (backdrop) {
			backdrop.remove();
		}
		
		// Reset body classes and styles
		document.body.classList.remove('modal-open');
		document.body.style.overflow = '';
		document.body.style.paddingRight = '';
	}
	
	// Add specific event listeners to each modal
	if (addSchoolModal) {
		addSchoolModal.addEventListener('hidden.bs.modal', function(event) {
			// Clean up any remaining backdrop
			cleanupModal(this);
			
			// Focus the add school button
			setTimeout(function() {
				const addSchoolBtn = document.getElementById('addSchoolBtn');
				if (addSchoolBtn) {
					addSchoolBtn.focus();
				}
			}, 10);
		});
	}
	
	if (deleteSchoolModal) {
		deleteSchoolModal.addEventListener('hidden.bs.modal', function(event) {
			// Clean up any remaining backdrop
			cleanupModal(this);
			
			// Return focus to the page
			setTimeout(function() {
				const mainContent = document.querySelector('main');
				if (mainContent) {
					mainContent.setAttribute('tabindex', '-1');
					mainContent.focus();
					mainContent.removeAttribute('tabindex');
				}
			}, 10);
		});
	}
	
	// Ensure modal buttons work properly
	document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
		button.addEventListener('click', function(e) {
			const targetSelector = this.getAttribute('data-bs-target');
			if (targetSelector) {
				// Clean up any existing backdrops before showing new modal
				cleanupModal();
				
				// Use Bootstrap's Modal API to show the modal
				const targetModal = document.querySelector(targetSelector);
				if (targetModal) {
					const bsModal = new bootstrap.Modal(targetModal);
					bsModal.show();
				}
			}
		});
	});
	
	// Handle ESC key globally to ensure modal backdrops are properly removed
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && document.querySelector('.modal.show')) {
			cleanupModal();
		}
	});
});
</script>

<?php include 'views/partials/footer.php'; ?>