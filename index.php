<?php
session_start();
require_once 'db_connect.php';

// Get user info if logged in (but don't redirect if not)
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$role = $_SESSION['role'] ?? 'user';

// Handle search if submitted
$searchResults = null;
$searchTerm = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $searchEscaped = $conn->real_escape_string($searchTerm);
    
    // Search all fields - using only columns we know exist
    // Simplified query to avoid column name issues
    $searchQuery = "SELECT * FROM pathways_opportunities_master_dnd 
                    WHERE Program_Name LIKE '%$searchEscaped%' 
                    OR State LIKE '%$searchEscaped%' 
                    OR Category LIKE '%$searchEscaped%'
                    OR Eligibility LIKE '%$searchEscaped%'
                    OR Notes LIKE '%$searchEscaped%'
                    OR Field LIKE '%$searchEscaped%'
                    ORDER BY 
                        CASE 
                            WHEN Program_Name LIKE '%$searchEscaped%' THEN 1
                            WHEN Category LIKE '%$searchEscaped%' THEN 2
                            WHEN State LIKE '%$searchEscaped%' THEN 3
                            ELSE 4
                        END,
                        Program_Name ASC
                    LIMIT 100";
    
    $searchResults = $conn->query($searchQuery);
    
    // Debug: Check if query failed
    if (!$searchResults) {
        die("Search Error: " . $conn->error . "<br>Query: " . $searchQuery);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Programs Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Reset and body */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f8;
            color: #333;
        }

        /* Header */
        header {
            background: #1a3c6b;
            color: white;
            padding: 20px 10px;
            text-align: center;
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

        /* Search Bar */
        .search-bar {
            text-align: center;
            margin: 30px 0;
        }

        .search-bar form {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-bar input[type="text"] {
            padding: 12px 15px;
            width: 450px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .search-bar input[type="submit"] {
            padding: 12px 25px;
            border-radius: 5px;
            border: none;
            background-color: #1a3c6b;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 14px;
        }

        .search-bar input[type="submit"]:hover {
            background-color: #0f2a4b;
        }

        .search-bar .clear-btn {
            padding: 12px 25px;
            border-radius: 5px;
            border: none;
            background-color: #ffc107;
            color: #333;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .search-bar .clear-btn:hover {
            background-color: #e0a800;
        }

        .search-info {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 10px;
        }

        /* Sections */
        main { padding: 20px; max-width: 1400px; margin: 0 auto; }

        section {
            margin-bottom: 40px;
        }

        section h2, section h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #1a3c6b;
        }

        /* Search Results Section */
        .search-results {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
        }

        .search-results h2 {
            margin-bottom: 20px;
        }

        .search-result-item {
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 0;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item h4 {
            color: #1a3c6b;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .search-result-item .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e8f0f8;
            color: #1a3c6b;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }

        .search-result-item p {
            color: #666;
            line-height: 1.6;
            margin: 10px 0;
        }

        .search-result-item .details-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #1a3c6b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .search-result-item .details-link:hover {
            background: #0f2a4b;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        /* Program Cards */
        .programs-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .program-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            width: 280px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .program-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .program-card h3 {
            color: #1a3c6b;
            margin-bottom: 10px;
        }

        .program-card a {
            color: #0077cc;
            text-decoration: none;
            font-weight: bold;
        }

        .program-card a:hover {
            text-decoration: underline;
        }

        /* Services & Contact */
        .service-card {
            background: #e8f0f8;
            border-radius: 10px;
            padding: 15px;
            width: 200px;
            text-align: center;
            font-weight: bold;
        }

        .service-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        /* Footer */
        footer {
            background: #1a3c6b;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 50px;
        }

        footer a {
            color: #ffd700;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .program-card, .service-card { width: 90%; }
            nav a { display: block; margin: 5px 0; }
            
            .search-bar form {
                flex-direction: column;
            }
            
            .search-bar input[type="text"] {
                width: 90%;
            }

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
        <a href="#services">Services</a>
        <a href="#contact">Contact</a>
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
    <!-- Search bar -->
    <div class="search-bar">
        <form action="index.php" method="get">
            <input type="text" name="search" placeholder="Search programs, states, or categories..." value="<?php echo htmlspecialchars($searchTerm); ?>" required>
            <input type="submit" value="🔍 Search">
            <?php if (!empty($searchTerm)): ?>
                <a href="index.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>
        <div class="search-info">
            <?php 
            if (empty($searchTerm)) {
                echo "💡 Try searching: \"Minnesota\", \"College Access\", \"TRIO\", or any keyword";
            }
            ?>
        </div>
    </div>

    <?php if ($searchResults !== null): ?>
        <!-- Search Results Section -->
        <section id="search-results" class="search-results">
            <h2>Search Results for "<?php echo htmlspecialchars($searchTerm); ?>"</h2>
            
            <?php if ($searchResults && $searchResults->num_rows > 0): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Found <?php echo $searchResults->num_rows; ?> program(s)
                </p>
                
                <?php while ($program = $searchResults->fetch_assoc()): ?>
                    <div class="search-result-item">
                        <h4><?php echo htmlspecialchars($program['Program_Name']); ?></h4>
                        
                        <div style="margin: 10px 0;">
                            <?php if (!empty($program['Category'])): ?>
                                <span class="badge">📚 <?php echo htmlspecialchars($program['Category']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($program['State'])): ?>
                                <span class="badge">📍 <?php echo htmlspecialchars($program['State']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($program['Notes'])): ?>
                            <p><?php echo htmlspecialchars(substr($program['Notes'], 0, 200)); ?>...</p>
                        <?php elseif (!empty($program['Eligibility'])): ?>
                            <p><?php echo htmlspecialchars(substr($program['Eligibility'], 0, 200)); ?>...</p>
                        <?php endif; ?>
                        
                        <?php
                        // Build the appropriate link based on what information is available
                        $programId = $program['Id'] ?? $program['id'] ?? $program['ID'] ?? 0;
                        $programState = $program['State'] ?? $program['state'] ?? '';
                        
                        if (!empty($programState)) {
                            // Link to programs.php filtered by state and scrolled to this program
                            echo '<a href="programs.php?state=' . urlencode($programState) . '#program-' . $programId . '" class="details-link">View in ' . htmlspecialchars($programState) . ' Programs →</a>';
                        } else {
                            // Link to all programs page
                            echo '<a href="programs.php#program-' . $programId . '" class="details-link">View in All Programs →</a>';
                        }
                        ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>No programs found</h3>
                    <p>Try searching with different keywords or browse programs by state below.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <!-- Welcome Section (shown when no search) -->
        <section id="welcome">
            <h2>Welcome!</h2>
            <p style="text-align:center;">Explore student programs by state or nationwide. Use the search bar above or click a state below to see programs in that area.</p>
        </section>
    <?php endif; ?>

    <section id="programs">
        <h3>Programs by State</h3>
        <div class="programs-container">
            <?php
            $sql = "SELECT DISTINCT State FROM pathways_opportunities_master_dnd 
                    WHERE State IS NOT NULL AND State != '' 
                    ORDER BY State ASC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $state = htmlspecialchars($row['State']);
                    echo "<div class='program-card'>";
                    echo "<h3>$state Programs</h3>";
                    echo "<p><a href='programs.php?state=" . urlencode($state) . "'>View Programs</a></p>";
                    echo "</div>";
                }
            } else {
                echo "<p style='text-align:center;'>No states found in the database.</p>";
            }
            ?>
        </div>
    </section>

    <section id="services">
        <h3>Our Services</h3>
        <div class="service-container">
            <div class="service-card">Program Search Assistance</div>
            <div class="service-card">Application Guidance</div>
            <div class="service-card">Resume & Essay Review</div>
            <div class="service-card">Scholarship Recommendations</div>
        </div>
    </section>

    <section id="contact">
        <h3>Contact Us</h3>
        <p style="text-align:center;">Have questions or need help finding the right program?</p>
        <p style="text-align:center;">Email: <a href="mailto:support@studentprogramsportal.com">support@studentprogramsportal.com</a></p>
        <p style="text-align:center;">Phone: (555) 123-4567</p>
    </section>

    <section id="sitemap">
        <h3>Site Map</h3>
        <ul style="text-align:center; list-style:none; padding:0;">
            <li><a href="index.php">Home</a></li>
            <li><a href="programs.php">All Programs</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
    </section>
</main>

<footer>
    <p>&copy; 2025 Student Programs Portal | All Rights Reserved</p>
</footer>

</body>
</html>

<?php $conn->close(); ?>