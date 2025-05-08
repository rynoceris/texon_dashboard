<?php
// school_details.php
// This file displays the details page for a specific school

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
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    redirect(APP_URL . '/login.php');
}

// Get domain from query string
$domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';

// Validate domain
if (empty($domain)) {
    setFlashMessage('error', 'School domain is required');
    redirect(APP_URL . '/index.php');
}

// Get school data
$db = Database::getInstance();
$school = $db->selectOne(
    "SELECT * FROM " . DB_PREFIX . "school_data WHERE domain = ?",
    [$domain]
);

// Check if school exists
if (!$school) {
    setFlashMessage('error', 'School not found');
    redirect(APP_URL . '/index.php');
}

// Parse JSON data
$schoolData = json_decode($school['data_json'] ?? '{}', true);

// Set page title
$pageTitle = $school['school_name'] . ' Dashboard';

// Include header
include 'views/partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'views/partials/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="school-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><?php echo htmlspecialchars($school['school_name']); ?></h1>
                        <p class="text-muted"><?php echo htmlspecialchars($school['domain']); ?></p>
                    </div>
                    <div>
                        <button class="btn btn-primary refresh-data-btn" data-domain="<?php echo htmlspecialchars($school['domain']); ?>">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                        <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                <p>Last updated: <?php echo formatDate($school['last_updated'], 'M j, Y g:i A'); ?></p>
            </div>
            
            <!-- Key Metrics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-container">
                        <div class="stat-value"><?php echo $school['staff_count']; ?></div>
                        <div class="stat-label">Staff Members</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-container">
                        <div class="stat-value"><?php echo $school['order_count']; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-container">
                        <div class="stat-value"><?php echo formatCurrency($school['order_total']); ?></div>
                        <div class="stat-label">Order Total</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-container">
                        <div class="stat-value"><?php echo formatPercentage($school['open_rate']); ?></div>
                        <div class="stat-label">Email Open Rate</div>
                    </div>
                </div>
            </div>
            
            <!-- Data Sources -->
            <div class="row">
                <!-- College Sports Directory -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">College Sports Directory</h5>
                            <?php if (isset($schoolData['csd']) && $schoolData['csd']['success']): ?>
                                <span class="badge bg-success">Connected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Not Available</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($schoolData['csd']) && $schoolData['csd']['success']): ?>
                                <p><strong>Staff Count:</strong> <?php echo $schoolData['csd']['data']['total_staff']; ?></p>
                                
                                <?php if (!empty($schoolData['csd']['data']['staff'])): ?>
                                    <h6>Top Staff Members</h6>
                                    <ul class="list-group">
                                        <?php 
                                        $staffMembers = $schoolData['csd']['data']['staff'];
                                        $topStaff = array_slice($staffMembers, 0, 5);
                                        foreach ($topStaff as $staff): 
                                        ?>
                                            <li class="list-group-item">
                                                <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                <?php if (!empty($staff['title'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($staff['title']); ?></small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <?php if (count($staffMembers) > 5): ?>
                                        <p class="mt-2">
                                            <a href="#" class="view-all-staff" data-toggle="modal" data-target="#staffModal">
                                                View all <?php echo count($staffMembers); ?> staff members
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>No staff members found for this school.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>College Sports Directory data is not available for this school.</p>
                                <a href="<?php echo APP_URL; ?>/csd.php" class="btn btn-sm btn-outline-primary">Configure CSD</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Brightpearl -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Brightpearl</h5>
                            <?php if (isset($schoolData['brightpearl']) && $schoolData['brightpearl']['success']): ?>
                                <span class="badge bg-success">Connected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Not Available</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($schoolData['brightpearl']) && $schoolData['brightpearl']['success']): ?>
                                <div class="mb-3">
                                    <p><strong>Customer Count:</strong> <?php echo count($schoolData['brightpearl']['data']['customers'] ?? []); ?></p>
                                    <p><strong>Order Count:</strong> <?php echo $schoolData['brightpearl']['data']['total_orders']; ?></p>
                                    <p><strong>Total Value:</strong> <?php echo formatCurrency($schoolData['brightpearl']['data']['total_value']); ?></p>
                                </div>
                                
                                <?php if (!empty($schoolData['brightpearl']['data']['orders'])): ?>
                                    <h6>Recent Orders</h6>
                                    <ul class="list-group">
                                        <?php 
                                        $orders = $schoolData['brightpearl']['data']['orders'];
                                        $recentOrders = array_slice($orders, 0, 5);
                                        foreach ($recentOrders as $order): 
                                        ?>
                                            <li class="list-group-item">
                                                <strong>Order #<?php echo htmlspecialchars($order['orderNumber'] ?? 'N/A'); ?></strong>
                                                <br>
                                                <?php if (!empty($order['placedOn'])): ?>
                                                    <small class="text-muted">Date: <?php echo formatDate($order['placedOn']); ?></small>
                                                <?php endif; ?>
                                                <br>
                                                <small>Amount: <?php echo formatCurrency($order['totalValue'] ?? 0); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <?php if (count($orders) > 5): ?>
                                        <p class="mt-2">
                                            <a href="#" class="view-all-orders" data-toggle="modal" data-target="#ordersModal">
                                                View all <?php echo count($orders); ?> orders
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>No orders found for this school.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Brightpearl data is not available for this school.</p>
                                <a href="<?php echo APP_URL; ?>/brightpearl.php" class="btn btn-sm btn-outline-primary">Configure Brightpearl</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Klaviyo -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Klaviyo</h5>
                            <?php if (isset($schoolData['klaviyo']) && $schoolData['klaviyo']['success']): ?>
                                <span class="badge bg-success">Connected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Not Available</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($schoolData['klaviyo']) && $schoolData['klaviyo']['success']): ?>
                                <div class="mb-3">
                                    <p><strong>Profiles:</strong> <?php echo $schoolData['klaviyo']['data']['total_profiles']; ?></p>
                                    <p><strong>Emails Sent:</strong> <?php echo $schoolData['klaviyo']['data']['email_count']; ?></p>
                                    <p><strong>Open Rate:</strong> <?php echo formatPercentage($schoolData['klaviyo']['data']['open_rate']); ?></p>
                                    <p><strong>Click Rate:</strong> <?php echo formatPercentage($schoolData['klaviyo']['data']['click_rate']); ?></p>
                                    <p><strong>Order Rate:</strong> <?php echo formatPercentage($schoolData['klaviyo']['data']['order_rate']); ?></p>
                                </div>
                                
                                <?php if (!empty($schoolData['klaviyo']['data']['profiles']['data'])): ?>
                                    <h6>Recent Profiles</h6>
                                    <ul class="list-group">
                                        <?php 
                                        $profiles = $schoolData['klaviyo']['data']['profiles']['data'];
                                        $recentProfiles = array_slice($profiles, 0, 5);
                                        foreach ($recentProfiles as $profile): 
                                        ?>
                                            <li class="list-group-item">
                                                <?php echo htmlspecialchars($profile['attributes']['email'] ?? 'N/A'); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <?php if (count($profiles) > 5): ?>
                                        <p class="mt-2">
                                            <a href="#" class="view-all-profiles" data-toggle="modal" data-target="#profilesModal">
                                                View all <?php echo count($profiles); ?> profiles
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>No profiles found for this school.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Klaviyo data is not available for this school.</p>
                                <a href="<?php echo APP_URL; ?>/klaviyo.php" class="btn btn-sm btn-outline-primary">Configure Klaviyo</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Staff Modal -->
<?php if (isset($schoolData['csd']) && $schoolData['csd']['success'] && !empty($schoolData['csd']['data']['staff'])): ?>
<div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffModalLabel">Staff Members - <?php echo htmlspecialchars($school['school_name']); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schoolData['csd']['data']['staff'] as $staff): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($staff['title'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['department'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($staff['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Orders Modal -->
<?php if (isset($schoolData['brightpearl']) && $schoolData['brightpearl']['success'] && !empty($schoolData['brightpearl']['data']['orders'])): ?>
<div class="modal fade" id="ordersModal" tabindex="-1" aria-labelledby="ordersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ordersModalLabel">Orders - <?php echo htmlspecialchars($school['school_name']); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schoolData['brightpearl']['data']['orders'] as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['orderNumber'] ?? 'N/A'); ?></td>
                            <td><?php echo !empty($order['placedOn']) ? formatDate($order['placedOn']) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($order['customerName'] ?? 'N/A'); ?></td>
                            <td><?php echo formatCurrency($order['totalValue'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($order['orderStatus'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Profiles Modal -->
<?php if (isset($schoolData['klaviyo']) && $schoolData['klaviyo']['success'] && !empty($schoolData['klaviyo']['data']['profiles']['data'])): ?>
<div class="modal fade" id="profilesModal" tabindex="-1" aria-labelledby="profilesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profilesModalLabel">Email Profiles - <?php echo htmlspecialchars($school['school_name']); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schoolData['klaviyo']['data']['profiles']['data'] as $profile): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($profile['attributes']['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($profile['attributes']['first_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($profile['attributes']['last_name'] ?? 'N/A'); ?></td>
                            <td><?php echo !empty($profile['attributes']['created']) ? formatDate($profile['attributes']['created']) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'views/partials/footer.php'; ?>
