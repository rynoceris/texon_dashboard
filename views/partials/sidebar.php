<?php
// views/partials/sidebar.php
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
	<div class="position-sticky pt-3">
		<ul class="nav flex-column">
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>">
					<span data-feather="home"></span>
					Dashboard
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schools.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/schools.php">
					<span data-feather="book"></span>
					Schools
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/settings.php">
					<span data-feather="settings"></span>
					Settings
				</a>
			</li>
		</ul>
		
		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
			<span>Data Sources</span>
		</h6>
		<ul class="nav flex-column mb-2">
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'csd.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/csd.php">
					<span data-feather="database"></span>
					College Sports Directory
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'brightpearl.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/brightpearl.php">
					<span data-feather="shopping-cart"></span>
					Brightpearl
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'klaviyo.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/klaviyo.php">
					<span data-feather="mail"></span>
					Klaviyo
				</a>
			</li>
		</ul>
		
		<?php if (isset($user) && $user['role'] === 'admin'): ?>
		<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
			<span>Administration</span>
		</h6>
		<ul class="nav flex-column mb-2">
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/users.php">
					<span data-feather="users"></span>
					Users
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/logs.php">
					<span data-feather="file-text"></span>
					Logs
				</a>
			</li>
		</ul>
		<?php endif; ?>
	</div>
</nav>