<?php
// views/dashboard.php
// This file is included via the loadView function

// Ensure $user is available
if (!isset($user)) {
    die('User data not available.');
}

// Get school data
$db = Database::getInstance();
$schools = $db->select("SELECT * FROM " . DB_PREFIX . "school_data ORDER BY school_name ASC");
?>

<?php include 'partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <span data-feather="calendar"></span>
                        This week
                    </button>
                </div>
            </div>
            
            <h2>School Overview</h2>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>School Name</th>
                            <th>Domain</th>
                            <th>Staff Count</th>
                            <th>Orders</th>
                            <th>Order Total</th>
                            <th>Emails Sent</th>
                            <th>Open Rate</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schools)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No school data available. <a href="#" class="add-school-btn">Add a school</a>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schools as $school): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                    <td><?php echo htmlspecialchars($school['domain']); ?></td>
                                    <td><?php echo $school['staff_count']; ?></td>
                                    <td><?php echo $school['order_count']; ?></td>
                                    <td><?php echo formatCurrency($school['order_total']); ?></td>
                                    <td><?php echo $school['email_count']; ?></td>
                                    <td><?php echo formatPercentage($school['open_rate']); ?></td>
                                    <td>
                                        <a href="school_details.php?domain=<?php echo urlencode($school['domain']); ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h3>Add New School</h3>
                    <form id="add-school-form" class="mb-4">
                        <div class="mb-3">
                            <label for="schoolDomain" class="form-label">School Email Domain</label>
                            <input type="text" class="form-control" id="schoolDomain" placeholder="albion.edu">
                            <div class="form-text">Enter the email domain without the '@' symbol.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add School</button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <h3>Data Sources</h3>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            College Sports Directory
                            <span class="badge bg-primary rounded-pill" id="csd-status">Connected</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Brightpearl API
                            <span class="badge bg-warning rounded-pill" id="brightpearl-status">Config Required</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Klaviyo API
                            <span class="badge bg-warning rounded-pill" id="klaviyo-status">Config Required</span>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add school form submission
    $('#add-school-form').on('submit', function(e) {
        e.preventDefault();
        
        var domain = $('#schoolDomain').val().trim();
        
        if (!domain) {
            alert('Please enter a school domain.');
            return;
        }
        
        // Show loading state
        $(this).find('button').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        
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
                    // Reload page on success
                    location.reload();
                } else {
                    alert(response.message);
                    // Reset button state
                    $('#add-school-form').find('button').prop('disabled', false).text('Add School');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                // Reset button state
                $('#add-school-form').find('button').prop('disabled', false).text('Add School');
            }
        });
    });
    
    // Check data source status
    function checkDataSourceStatus() {
        $.ajax({
            url: 'includes/api/check_sources.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.csd) {
                    $('#csd-status').removeClass('bg-warning').addClass('bg-success').text('Connected');
                } else {
                    $('#csd-status').removeClass('bg-success').addClass('bg-warning').text('Not Connected');
                }
                
                if (response.brightpearl) {
                    $('#brightpearl-status').removeClass('bg-warning').addClass('bg-success').text('Connected');
                } else {
                    $('#brightpearl-status').removeClass('bg-success').addClass('bg-warning').text('Config Required');
                }
                
                if (response.klaviyo) {
                    $('#klaviyo-status').removeClass('bg-warning').addClass('bg-success').text('Connected');
                } else {
                    $('#klaviyo-status').removeClass('bg-success').addClass('bg-warning').text('Config Required');
                }
            }
        });
    }
    
    // Check status on page load
    checkDataSourceStatus();
});
</script>

<?php include 'partials/footer.php'; ?>
