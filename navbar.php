<?php
// navbar.php - Shared navigation component
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<header>
    <h1>Student Programs Portal<?php echo ($current_page === 'admin.php') ? ' - Admin Dashboard' : ''; ?></h1>
    <nav>
        <a href="index.php" <?php echo ($current_page === 'index.php') ? 'style="color: #ffd700;"' : ''; ?>>Home</a>
        <a href="programs.php" <?php echo ($current_page === 'programs.php') ? 'style="color: #ffd700;"' : ''; ?>>All Programs</a>
        <a href="index.php#services">Services</a>
        <a href="index.php#contact">Contact</a>
        <a href="index.php#sitemap">Site Map</a>
        
        <?php if ($is_admin): ?>
            <a href="admin.php" <?php echo ($current_page === 'admin.php') ? 'style="color: #ffd700;"' : ''; ?>>Admin Dashboard</a>
            <a href="reports.php" <?php echo ($current_page === 'reports.php') ? 'style="color: #ffd700;"' : ''; ?>>📊 Reports</a>
        <?php endif; ?>
        
        <a href="logout.php">Logout</a>
    </nav>
</header>