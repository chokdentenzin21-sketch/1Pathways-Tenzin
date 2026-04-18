<?php
session_start();
require_once 'db_connect.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle CSV Export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="programs_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Get column names
    $columns_query = "SHOW COLUMNS FROM pathways_opportunities_master_dnd";
    $columns_result = $conn->query($columns_query);
    $headers = [];
    while ($col = $columns_result->fetch_assoc()) {
        $headers[] = $col['Field'];
    }
    fputcsv($output, $headers);
    
    // Export based on type
    if ($export_type === 'all') {
        $query = "SELECT * FROM pathways_opportunities_master_dnd ORDER BY Program_Name ASC";
    } elseif ($export_type === 'state' && isset($_GET['state'])) {
        $state = $conn->real_escape_string($_GET['state']);
        $query = "SELECT * FROM pathways_opportunities_master_dnd WHERE State = '$state' ORDER BY Program_Name ASC";
    } elseif ($export_type === 'category' && isset($_GET['category'])) {
        $category = $conn->real_escape_string($_GET['category']);
        $query = "SELECT * FROM pathways_opportunities_master_dnd WHERE Category = '$category' ORDER BY Program_Name ASC";
    } else {
        $query = "SELECT * FROM pathways_opportunities_master_dnd ORDER BY Program_Name ASC";
    }
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Handle CSV Import
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle !== false) {
            // Read header row
            $headers = fgetcsv($handle);
            
            // Clean up headers (remove BOM, trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);
            
            $imported = 0;
            $errors = 0;
            $error_details = [];
            
            // Read data rows
            while (($data = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($data))) continue;
                
                // Skip if not enough columns
                if (count($data) < count($headers)) {
                    $errors++;
                    continue;
                }
                
                // Map CSV columns to database columns
                $row_data = array_combine($headers, $data);
                
                // Prepare insert statement
                $columns = [];
                $placeholders = [];
                $values = [];
                
                foreach ($row_data as $col => $val) {
                    // Skip id column and empty column names
                    if (empty($col) || strtolower($col) === 'id' || strtolower($col) === 'created_at') continue;
                    
                    // Only include non-empty values
                    if ($val !== '' && $val !== null) {
                        $columns[] = "`" . $conn->real_escape_string($col) . "`";
                        $placeholders[] = '?';
                        $values[] = $val;
                    }
                }
                
                if (!empty($columns)) {
                    $sql = "INSERT INTO pathways_opportunities_master_dnd (" 
                         . implode(', ', $columns) 
                         . ") VALUES (" 
                         . implode(', ', $placeholders) 
                         . ")";
                    
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt) {
                        // Create types string (all strings)
                        $types = str_repeat('s', count($values));
                        
                        // Bind parameters
                        $stmt->bind_param($types, ...$values);
                        
                        if ($stmt->execute()) {
                            $imported++;
                        } else {
                            $errors++;
                            $error_details[] = "Row error: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $errors++;
                        $error_details[] = "Prepare error: " . $conn->error;
                    }
                }
            }
            
            fclose($handle);
            
            if ($imported > 0) {
                $success_message = "Successfully imported $imported program(s)!" . ($errors > 0 ? " ($errors failed)" : "");
            } else {
                $error_message = "No programs were imported. " . ($errors > 0 ? "$errors errors occurred." : "") . " " . implode("; ", array_slice($error_details, 0, 3));
            }
        } else {
            $error_message = "Error reading CSV file.";
        }
    } else {
        $error_message = "Error uploading file.";
    }
}

// Handle program deletion
if (isset($_POST['delete_program'])) {
    $program_id = $_POST['program_id'];
    $stmt = $conn->prepare("DELETE FROM pathways_opportunities_master_dnd WHERE id = ?");
    $stmt->bind_param("i", $program_id);
    if ($stmt->execute()) {
        $success_message = "Program deleted successfully!";
    } else {
        $error_message = "Error deleting program.";
    }
    $stmt->close();
}

// Get filter parameters
$report_type = $_GET['report'] ?? 'overview';
$state_filter = $_GET['state'] ?? '';
$category_filter = $_GET['category'] ?? '';
$field_filter = $_GET['field'] ?? '';

// Get statistics
$totalPrograms = $conn->query("SELECT COUNT(*) as count FROM pathways_opportunities_master_dnd")->fetch_assoc()['count'];
$totalStates = $conn->query("SELECT COUNT(DISTINCT State) as count FROM pathways_opportunities_master_dnd WHERE State IS NOT NULL AND State != ''")->fetch_assoc()['count'];

// Programs by State
$programsByState = $conn->query("
    SELECT State, COUNT(*) as count 
    FROM pathways_opportunities_master_dnd 
    WHERE State IS NOT NULL AND State != ''
    GROUP BY State 
    ORDER BY count DESC 
    LIMIT 10
");

// Programs by Category
$programsByCategory = $conn->query("
    SELECT Category, COUNT(*) as count 
    FROM pathways_opportunities_master_dnd 
    WHERE Category IS NOT NULL AND Category != ''
    GROUP BY Category 
    ORDER BY count DESC
");

// Programs by Field
$programsByField = $conn->query("
    SELECT Field, COUNT(*) as count 
    FROM pathways_opportunities_master_dnd 
    WHERE Field IS NOT NULL AND Field != ''
    GROUP BY Field 
    ORDER BY count DESC 
    LIMIT 10
");

// Get all programs for management
$allPrograms = $conn->query("
    SELECT id, Program_Name, State, Category, Field 
    FROM pathways_opportunities_master_dnd 
    ORDER BY Program_Name ASC
    LIMIT 100
");

// Get distinct values for filters
$statesForFilter = $conn->query("SELECT DISTINCT State FROM pathways_opportunities_master_dnd WHERE State IS NOT NULL AND State != '' ORDER BY State");
$categoriesForFilter = $conn->query("SELECT DISTINCT Category FROM pathways_opportunities_master_dnd WHERE Category IS NOT NULL AND Category != '' ORDER BY Category");
$fieldsForFilter = $conn->query("SELECT DISTINCT Field FROM pathways_opportunities_master_dnd WHERE Field IS NOT NULL AND Field != '' ORDER BY Field");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <style>
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
        header h1 { font-size: 2rem; margin-bottom: 10px; }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 12px;
            font-weight: bold;
            transition: color 0.3s;
        }
        nav a:hover { color: #ffd700; }

        main {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            margin: 30px 0;
            color: #1a3c6b;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Import/Export Section */
        .import-export-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .import-export-section h3 {
            color: #1a3c6b;
            margin-bottom: 20px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .action-box {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .action-box:hover {
            border-color: #1a3c6b;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .action-box h4 {
            color: #1a3c6b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-box p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* Stats Overview */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 3rem;
            font-weight: bold;
            color: #1a3c6b;
        }

        /* Filters */
        .filters {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .filters h3 {
            color: #1a3c6b;
            margin-bottom: 15px;
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group button {
            padding: 10px 20px;
            background: #1a3c6b;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .filter-group button:hover {
            background: #0f2a4b;
        }

        /* Report Sections */
        .report-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .report-section h3 {
            color: #1a3c6b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        thead {
            background: #e8f0f8;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1a3c6b;
            font-size: 0.9rem;
        }

        td {
            padding: 12px;
            border-top: 1px solid #f0f0f0;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        /* Charts */
        .chart-container {
            margin-top: 20px;
        }

        .bar-chart {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .bar-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .bar-label {
            min-width: 150px;
            font-weight: 500;
        }

        .bar-visual {
            flex: 1;
            height: 30px;
            background: linear-gradient(90deg, #1a3c6b, #0077cc);
            border-radius: 5px;
            position: relative;
        }

        .bar-value {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: #1a3c6b;
            color: white;
        }

        .btn-primary:hover {
            background: #0f2a4b;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        /* File Upload */
        .file-upload {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .file-upload input[type="file"] {
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Export Dropdown */
        .export-dropdown {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Footer */
        footer {
            background: #1a3c6b;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .bar-label {
                min-width: 100px;
                font-size: 0.85rem;
            }
            .action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<main>
    <h2 class="page-title"> Admin Reports</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Import/Export Section -->
    <div class="import-export-section">
        <h3> Import & Export </h3>
        
        <div class="action-grid">
            <!-- Import Section -->
            <div class="action-box">
                <h4>Import Programs</h4>
                <p>Upload a CSV file to import multiple programs at once. The CSV should include all program fields.</p>
                <form method="post" enctype="multipart/form-data">
                    <div class="file-upload">
                        <input type="file" name="csv_file" accept=".csv" required>
                        <button type="submit" name="import_csv" class="btn btn-primary btn-sm">Upload & Import</button>
                    </div>
                </form>
                <p style="margin-top: 10px; font-size: 0.85rem; color: #999;">
                </p>
            </div>

            <!-- Export Section -->
            <div class="action-box">
                <h4> Export Programs</h4>
                <p>Download program data as CSV for backup, analysis, or sharing.</p>
                <div class="export-dropdown">
                    <a href="?export=all" class="btn btn-success btn-sm">Export All Programs</a>
                    <a href="#" onclick="showExportOptions()" class="btn btn-warning btn-sm">Export Filtered</a>
                </div>
                
                <!-- Hidden export options -->
                <div id="exportOptions" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                    <p style="margin-bottom: 10px; font-weight: 600;">Export by State:</p>
                    <select id="exportState" class="filter-group" style="width: 100%; margin-bottom: 10px;">
                        <option value="">Select a state...</option>
                        <?php
                        $statesForExport = $conn->query("SELECT DISTINCT State FROM pathways_opportunities_master_dnd WHERE State IS NOT NULL AND State != '' ORDER BY State");
                        while ($row = $statesForExport->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['State']) . '">' . htmlspecialchars($row['State']) . '</option>';
                        }
                        ?>
                    </select>
                    <button onclick="exportByState()" class="btn btn-primary btn-sm">Export Selected State</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Programs</h3>
            <div class="number"><?php echo $totalPrograms; ?></div>
        </div>
        <div class="stat-card">
            <h3>States Covered</h3>
            <div class="number"><?php echo $totalStates; ?></div>
        </div>
        <div class="stat-card">
            <h3>Avg per State</h3>
            <div class="number"><?php echo round($totalPrograms / max($totalStates, 1)); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <h3>Filter Reports</h3>
        <form method="get" action="reports.php">
            <div class="filter-group">
                <select name="state">
                    <option value="">All States</option>
                    <?php
                    if ($statesForFilter) {
                        $statesForFilter->data_seek(0); // Reset pointer
                        while ($row = $statesForFilter->fetch_assoc()) {
                            $selected = ($state_filter === $row['State']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($row['State']) . '" ' . $selected . '>' . htmlspecialchars($row['State']) . '</option>';
                        }
                    }
                    ?>
                </select>

                <select name="category">
                    <option value="">All Categories</option>
                    <?php
                    if ($categoriesForFilter) {
                        while ($row = $categoriesForFilter->fetch_assoc()) {
                            $selected = ($category_filter === $row['Category']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($row['Category']) . '" ' . $selected . '>' . htmlspecialchars($row['Category']) . '</option>';
                        }
                    }
                    ?>
                </select>

                <select name="field">
                    <option value="">All Fields</option>
                    <?php
                    if ($fieldsForFilter) {
                        while ($row = $fieldsForFilter->fetch_assoc()) {
                            $selected = ($field_filter === $row['Field']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($row['Field']) . '" ' . $selected . '>' . htmlspecialchars($row['Field']) . '</option>';
                        }
                    }
                    ?>
                </select>

                <button type="submit">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Programs by State -->
    <div class="report-section">
        <h3>Top 10 States by Program Count</h3>
        <div class="chart-container">
            <div class="bar-chart">
                <?php
                if ($programsByState && $programsByState->num_rows > 0) {
                    $maxCount = 0;
                    $data = [];
                    while ($row = $programsByState->fetch_assoc()) {
                        $data[] = $row;
                        if ($row['count'] > $maxCount) $maxCount = $row['count'];
                    }

                    foreach ($data as $row) {
                        $percentage = ($row['count'] / $maxCount) * 100;
                        echo '<div class="bar-item">';
                        echo '<div class="bar-label">' . htmlspecialchars($row['State']) . '</div>';
                        echo '<div class="bar-visual" style="width: ' . $percentage . '%;">';
                        echo '<span class="bar-value">' . $row['count'] . '</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No data available.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Programs by Category -->
    <div class="report-section">
        <h3>Programs by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($programsByCategory && $programsByCategory->num_rows > 0) {
                    while ($row = $programsByCategory->fetch_assoc()) {
                        $percentage = ($row['count'] / $totalPrograms) * 100;
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['Category']) . '</td>';
                        echo '<td><strong>' . $row['count'] . '</strong></td>';
                        echo '<td>' . number_format($percentage, 1) . '%</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Programs by Field -->
    <div class="report-section">
        <h3>Top 10 Fields of Focus</h3>
        <table>
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($programsByField && $programsByField->num_rows > 0) {
                    while ($row = $programsByField->fetch_assoc()) {
                        $percentage = ($row['count'] / $totalPrograms) * 100;
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
                        echo '<td><strong>' . $row['count'] . '</strong></td>';
                        echo '<td>' . number_format($percentage, 1) . '%</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<footer>
    <p>&copy; 2025 Student Programs Portal | All Rights Reserved</p>
</footer>

<script>
function showExportOptions() {
    const options = document.getElementById('exportOptions');
    options.style.display = options.style.display === 'none' ? 'block' : 'none';
}

function exportByState() {
    const state = document.getElementById('exportState').value;
    if (state) {
        window.location.href = '?export=state&state=' + encodeURIComponent(state);
    } else {
        alert('Please select a state to export.');
    }
}
</script>

</body>
</html>
<?php $conn->close(); ?>