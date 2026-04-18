<?php
session_start();
require_once 'db_connect.php';

// Get user info if logged in (but don't redirect if not)
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$role = $_SESSION['role'] ?? 'user';

// Pagination settings
$programs_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $programs_per_page;

// Check if a state filter is applied
$state_filter = isset($_GET['state']) && !empty($_GET['state']) ? $_GET['state'] : null;

if ($state_filter) {
    // Count total programs for this state
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM pathways_opportunities_master_dnd WHERE State = ?");
    $countStmt->bind_param("s", $state_filter);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total_programs = (int)$countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get programs with pagination
    $sql = "SELECT * FROM pathways_opportunities_master_dnd WHERE State = ? ORDER BY Program_Name ASC LIMIT {$programs_per_page} OFFSET {$offset}";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $state_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $pageTitle = htmlspecialchars($state_filter) . " Programs";
} else {
    // Count all programs
    $countResult = $conn->query("SELECT COUNT(*) as total FROM pathways_opportunities_master_dnd");
    $total_programs = (int)$countResult->fetch_assoc()['total'];
    
    // Get all programs with pagination
    $sql = "SELECT * FROM pathways_opportunities_master_dnd ORDER BY State ASC, Program_Name ASC LIMIT {$programs_per_page} OFFSET {$offset}";
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    
    $pageTitle = "All Student Programs";
}

// Calculate total pages
$total_pages = ceil($total_programs / $programs_per_page);

// Fetch distinct states for filter buttons
$statesStmt = $conn->query("SELECT DISTINCT State FROM pathways_opportunities_master_dnd WHERE State IS NOT NULL AND State != '' ORDER BY State ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - Student Programs Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f8;
            color: #333; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Header */
        header {
            background: #1a3c6b;
            color: white;
            padding: 20px 10px;
            text-align: center;
            position: relative;
        }

        header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 12px;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #ffd700;
        }

        /* Login/User section in header */
        .user-section {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-section .user-name {
            color: white;
            font-weight: 600;
        }

        .user-section .login-btn,
        .user-section .logout-btn {
            background: #ffd700;
            color: #1a3c6b;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .user-section .login-btn:hover,
        .user-section .logout-btn:hover {
            background: #ffed4e;
            transform: translateY(-2px);
        }

        .user-section .logout-btn {
            background: #dc3545;
            color: white;
        }

        .user-section .logout-btn:hover {
            background: #c82333;
        }
        
        .state-selection { 
            text-align: center; 
            margin: 20px auto; 
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            max-width: 1400px;
        }
        .state-selection a { 
            display: inline-block; 
            margin: 5px; 
            padding: 8px 15px; 
            background: #1a3c6b;
            color: white; 
            border-radius: 5px; 
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .state-selection a:hover { background: #0f2a4b; }
        .state-selection a.active { background: #003366; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        
        .programs-container { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            padding: 20px; 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .program-card { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            scroll-margin-top: 100px; /* Space from top when scrolling to anchor */
        }
        .program-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        
        /* Highlight effect for linked program */
        .program-card.highlight {
            animation: highlightFade 2s ease-in-out;
            border: 2px solid #ffd700;
        }
        
        @keyframes highlightFade {
            0% { background-color: #fff9e6; }
            50% { background-color: #fff9e6; }
            100% { background-color: white; }
        }
        
        .program-card h3 { margin-top: 0; color: #1a3c6b; font-size: 18px; margin-bottom: 12px; }
        .program-card p { margin: 8px 0; font-size: 14px; line-height: 1.6; }
        .program-card strong { color: #555; }
        .program-card a { color: #0077cc; text-decoration: none; font-weight: bold; }
        .program-card a:hover { text-decoration: underline; }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
            padding: 20px;
        }
        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            background: white;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background: #1a3c6b;
            color: white;
            border-color: #1a3c6b;
        }
        .pagination .current {
            background: #1a3c6b;
            color: white;
            border-color: #1a3c6b;
            font-weight: bold;
        }
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .pagination-info {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 14px;
        }
        
        footer {
            background: #1a3c6b;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 50px;
        }
        
        @media (max-width: 1200px) {
            .programs-container { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 900px) {
            .programs-container { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .programs-container { grid-template-columns: 1fr; }
            .state-selection a { font-size: 12px; padding: 6px 10px; }
            
            .user-section {
                position: static;
                justify-content: center;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid rgba(255,255,255,0.3);
            }

            header {
                position: relative;
                padding-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Student Programs Portal</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="programs.php">All Programs</a>
        <?php if ($isLoggedIn && $role === 'admin'): ?>
            <a href="admin.php">Admin Dashboard</a>
        <?php endif; ?>
        <a href="index.php#services">Services</a>
        <a href="index.php#contact">Contact</a>
    </nav>
    
    <!-- User Login/Logout Section -->
    <div class="user-section">
        <?php if ($isLoggedIn): ?>
            <span class="user-name">👋 <?php echo htmlspecialchars($userName); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Login / Register</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <h2 style="text-align:center; margin: 20px 0; color: #1a3c6b;"><?= $pageTitle ?></h2>

    <!-- State Filter Buttons -->
    <div class="state-selection">
        <strong>Filter by State:</strong><br><br>
        <a href="programs.php" <?= !$state_filter ? 'class="active"' : '' ?>>All States</a>
        <?php
        if ($statesStmt && $statesStmt->num_rows > 0) {
            while ($row = $statesStmt->fetch_assoc()) {
                $stateName = $row['State'];
                if (empty($stateName)) continue;
                $activeClass = ($state_filter === $stateName) ? 'class="active"' : '';
                echo '<a href="programs.php?state=' . urlencode($stateName) . '" ' . $activeClass . '>' . htmlspecialchars($stateName) . '</a>';
            }
        }
        ?>
    </div>

    <!-- Pagination Info -->
    <div class="pagination-info">
        Showing <?= min($offset + 1, $total_programs) ?> - <?= min($offset + $programs_per_page, $total_programs) ?> of <?= $total_programs ?> programs
    </div>

    <!-- Program Cards -->
    <div class="programs-container">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Skip empty rows
                if (empty($row['Program_Name'])) {
                    continue;
                }
                
                // Get program ID with fallback for different column name formats
                $programId = $row['Id'] ?? $row['id'] ?? $row['ID'] ?? 0;
                
                // Add anchor ID to each card
                echo '<div class="program-card" id="program-' . $programId . '">';
                echo '<h3>' . htmlspecialchars($row['Program_Name']) . '</h3>';
                
                if (!empty($row['State'])) {
                    echo '<p><strong>State:</strong> ' . htmlspecialchars($row['State']) . '</p>';
                }
                
                if (!empty($row['Grade_Levels'])) {
                    echo '<p><strong>Grades:</strong> ' . htmlspecialchars($row['Grade_Levels']) . '</p>';
                }
                
                if (!empty($row['Eligibility'])) {
                    $eligibility = htmlspecialchars($row['Eligibility']);
                    if (strlen($eligibility) > 150) {
                        $eligibility = substr($eligibility, 0, 150) . '...';
                    }
                    echo '<p><strong>Eligibility:</strong> ' . $eligibility . '</p>';
                }
                
                if (!empty($row['Category'])) {
                    echo '<p><strong>Category:</strong> ' . htmlspecialchars($row['Category']) . '</p>';
                }
                
                if (!empty($row['Field'])) {
                    echo '<p><strong>Field:</strong> ' . htmlspecialchars($row['Field']) . '</p>';
                }
                
                if (!empty($row['Delivery_Context'])) {
                    echo '<p><strong>Delivery:</strong> ' . htmlspecialchars($row['Delivery_Context']) . '</p>';
                }
                
                if (!empty($row['Cost_Funding'])) {
                    echo '<p><strong>Cost/Funding:</strong> ' . htmlspecialchars($row['Cost_Funding']) . '</p>';
                }
                
                if (!empty($row['Deadlines'])) {
                    echo '<p><strong>Deadlines:</strong> ' . htmlspecialchars($row['Deadlines']) . '</p>';
                }
                
                if (!empty($row['Notes'])) {
                    $notes = htmlspecialchars($row['Notes']);
                    if (strlen($notes) > 200) {
                        $notes = substr($notes, 0, 200) . '...';
                    }
                    echo '<p><strong>Notes:</strong> ' . $notes . '</p>';
                }

                if (!empty($row['Website'])) {
                    $link = trim($row['Website']);
                    if (!preg_match("/^https?:\/\//", $link)) { 
                        $link = "https://" . $link; 
                    }
                    echo '<p><a href="' . htmlspecialchars($link) . '" target="_blank">Visit Website →</a></p>';
                }

                echo '</div>';
            }
        } else {
            echo '<p style="text-align:center; grid-column: 1/-1;">No programs found for this selection.</p>';
        }
        ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        // Build base URL
        $base_url = 'programs.php?';
        if ($state_filter) {
            $base_url .= 'state=' . urlencode($state_filter) . '&';
        }
        
        // Ensure integers
        $current_page = (int)$current_page;
        $total_pages = (int)$total_pages;
        
        // Previous button
        if ($current_page > 1) {
            $prev_page = $current_page - 1;
            echo '<a href="' . $base_url . 'page=' . $prev_page . '">&laquo; Previous</a>';
        } else {
            echo '<span class="disabled">&laquo; Previous</span>';
        }
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            echo '<a href="' . $base_url . 'page=1">1</a>';
            if ($start_page > 2) {
                echo '<span>...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="' . $base_url . 'page=' . $i . '">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<span>...</span>';
            }
            echo '<a href="' . $base_url . 'page=' . $total_pages . '">' . $total_pages . '</a>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $next_page = $current_page + 1;
            echo '<a href="' . $base_url . 'page=' . $next_page . '">Next &raquo;</a>';
        } else {
            echo '<span class="disabled">Next &raquo;</span>';
        }
        ?>
    </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2025 Student Programs Portal | All Rights Reserved</p>
</footer>

<script>
// Highlight and scroll to program if linked from search
window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        const targetElement = document.getElementById(targetId);
        
        if (targetElement && targetElement.classList.contains('program-card')) {
            // Add highlight class
            targetElement.classList.add('highlight');
            
            // Smooth scroll to element
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
            
            // Remove highlight after animation
            setTimeout(() => {
                targetElement.classList.remove('highlight');
            }, 3000);
        }
    }
});
</script>

</body>
</html>