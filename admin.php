<?php
session_start();
require_once 'db_connect.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $active = $_POST['active'] ?? 'yes';
    $password = $_POST['password'] ?? 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (First_Name, Last_Name, Email, Phone, Role, Active, Hash, Created_Time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", $firstName, $lastName, $email, $phone, $role, $active, $hash);
    
    if ($stmt->execute()) {
        $message = "User created successfully! Default password: $password";
        $messageType = 'success';
    } else {
        $message = "Error creating user: " . $stmt->error;
        $messageType = 'error';
    }
    $stmt->close();
}

// Handle user update/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = intval($_POST['user_id']);
    $firstName = trim($_POST['edit_first_name'] ?? '');
    $lastName = trim($_POST['edit_last_name'] ?? '');
    $email = trim($_POST['edit_email'] ?? '');
    $phone = trim($_POST['edit_phone'] ?? '');
    $role = $_POST['edit_role'] ?? 'user';
    $active = $_POST['edit_active'] ?? 'yes';
    
    // Check if password should be updated
    if (!empty($_POST['edit_password'])) {
        $newPassword = $_POST['edit_password'];
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET First_Name=?, Last_Name=?, Email=?, Phone=?, Role=?, Active=?, Hash=? WHERE User_Id=?");
        $stmt->bind_param("sssssssi", $firstName, $lastName, $email, $phone, $role, $active, $hash, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET First_Name=?, Last_Name=?, Email=?, Phone=?, Role=?, Active=? WHERE User_Id=?");
        $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $role, $active, $userId);
    }
    
    if ($stmt->execute()) {
        $message = "User updated successfully!" . (!empty($_POST['edit_password']) ? " New password: " . $_POST['edit_password'] : "");
        $messageType = 'success';
    } else {
        $message = "Error updating user: " . $stmt->error;
        $messageType = 'error';
    }
    $stmt->close();
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    if ($userId != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE User_Id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $message = "User deleted successfully!";
            $messageType = 'success';
        } else {
            $message = "Error deleting user.";
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "You cannot delete your own account!";
        $messageType = 'error';
    }
}

// Handle user status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $userId = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE users SET Active = IF(Active = 'yes', 'no', 'yes') WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $message = "User status updated!";
        $messageType = 'success';
    }
    $stmt->close();
}

// ==================== PROGRAM MANAGEMENT ====================

// Handle new program creation
// Handle new program creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_program'])) {
    $programName = trim($_POST['program_name'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $field = trim($_POST['field'] ?? '');
    $gradeLevels = trim($_POST['grade_levels'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $costFunding = trim($_POST['cost_funding'] ?? '');
    $deadlines = trim($_POST['deadlines'] ?? '');
    $deliveryContext = trim($_POST['delivery_context'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO pathways_opportunities_master_dnd 
        (Program_Name, State, Category, Field, Grade_Levels, Eligibility, Cost_Funding, Deadlines, Delivery_Context, Notes, Website) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $programName, $state, $category, $field, $gradeLevels, $eligibility, $costFunding, $deadlines, $deliveryContext, $notes, $website);
    
    if ($stmt->execute()) {
        $message = "Program created successfully!";
        $messageType = 'success';
    } else {
        $message = "Error creating program: " . $stmt->error;
        $messageType = 'error';
    }
    $stmt->close();
}
// Handle program update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_program'])) {
    $programId = intval($_POST['program_id']);
    $programName = trim($_POST['edit_program_name'] ?? '');
    $category = trim($_POST['edit_category'] ?? '');
    $description = trim($_POST['edit_description'] ?? '');
    $eligibility = trim($_POST['edit_eligibility'] ?? '');
    $benefits = trim($_POST['edit_benefits'] ?? '');
    $applicationProcess = trim($_POST['edit_application_process'] ?? '');
    $contactInfo = trim($_POST['edit_contact_info'] ?? '');
    $websiteUrl = trim($_POST['edit_website_url'] ?? '');
    $deadline = trim($_POST['edit_deadline'] ?? '');
    $additionalNotes = trim($_POST['edit_additional_notes'] ?? '');

    $stmt = $conn->prepare("UPDATE pathways_opportunities_master_dnd SET 
        Program_Name=?, Category=?, Description=?, Eligibility=?, Benefits=?, 
        Application_Process=?, Contact_Info=?, Website_URL=?, Deadline=?, Additional_Notes=? 
        WHERE Id=?");
    $stmt->bind_param("ssssssssssi", $programName, $category, $description, $eligibility, $benefits, 
        $applicationProcess, $contactInfo, $websiteUrl, $deadline, $additionalNotes, $programId);
    
    if ($stmt->execute()) {
        $message = "Program updated successfully!";
        $messageType = 'success';
    } else {
        $message = "Error updating program: " . $stmt->error;
        $messageType = 'error';
    }
    $stmt->close();
}

// Handle program deletion
if (isset($_GET['delete_program']) && is_numeric($_GET['delete_program'])) {
    $programId = intval($_GET['delete_program']);
    $stmt = $conn->prepare("DELETE FROM pathways_opportunities_master_dnd WHERE Id = ?");
    $stmt->bind_param("i", $programId);
    if ($stmt->execute()) {
        $message = "Program deleted successfully!";
        $messageType = 'success';
    } else {
        $message = "Error deleting program.";
        $messageType = 'error';
    }
    $stmt->close();
    
    // Redirect back to the same page/tab with preserved parameters
    $redirect_url = '?';
    if (isset($_GET['search'])) $redirect_url .= 'search=' . urlencode($_GET['search']) . '&';
    if (isset($_GET['filter_category'])) $redirect_url .= 'filter_category=' . urlencode($_GET['filter_category']) . '&';
    if (isset($_GET['page'])) $redirect_url .= 'page=' . urlencode($_GET['page']) . '&';
    $redirect_url .= 'tab=programs&msg=' . urlencode($message) . '&msg_type=' . $messageType;
    header('Location: ' . $redirect_url);
    exit;
}

// Handle bulk program deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_programs'])) {
    if (!empty($_POST['program_ids']) && is_array($_POST['program_ids'])) {
        $programIds = array_map('intval', $_POST['program_ids']);
        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        
        $stmt = $conn->prepare("DELETE FROM pathways_opportunities_master_dnd WHERE Id IN ($placeholders)");
        $types = str_repeat('i', count($programIds));
        $stmt->bind_param($types, ...$programIds);
        
        if ($stmt->execute()) {
            $deletedCount = $stmt->affected_rows;
            $message = "$deletedCount program(s) deleted successfully!";
            $messageType = 'success';
        } else {
            $message = "Error deleting programs.";
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "No programs selected for deletion.";
        $messageType = 'error';
    }
    
    // Redirect back to the same page/tab with preserved parameters
    $redirect_url = '?';
    if (isset($_GET['search'])) $redirect_url .= 'search=' . urlencode($_GET['search']) . '&';
    if (isset($_GET['filter_category'])) $redirect_url .= 'filter_category=' . urlencode($_GET['filter_category']) . '&';
    if (isset($_GET['page'])) $redirect_url .= 'page=' . urlencode($_GET['page']) . '&';
    $redirect_url .= 'tab=programs&msg=' . urlencode($message) . '&msg_type=' . $messageType;
    header('Location: ' . $redirect_url);
    exit;
}

// Check for redirected messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['msg_type'] ?? 'success';
}

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$activeUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE Active = 'yes'")->fetch_assoc()['count'];
$adminCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE Role = 'admin'")->fetch_assoc()['count'];
$totalPrograms = $conn->query("SELECT COUNT(*) as count FROM pathways_opportunities_master_dnd")->fetch_assoc()['count'];

// Get program categories count
$categoriesResult = $conn->query("SELECT Category, COUNT(*) as count FROM pathways_opportunities_master_dnd GROUP BY Category ORDER BY count DESC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch all users
$usersResult = $conn->query("SELECT * FROM users ORDER BY Created_Time DESC");

// Fetch all programs with search/filter capability and pagination
$searchTerm = $_GET['search'] ?? '';
$filterCategory = $_GET['filter_category'] ?? '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;
$offset = ($current_page - 1) * $items_per_page;

// Count total programs for pagination
$countQuery = "SELECT COUNT(*) as total FROM pathways_opportunities_master_dnd WHERE 1=1";
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $countQuery .= " AND (Program_Name LIKE '%$searchTerm%' OR Description LIKE '%$searchTerm%' OR Category LIKE '%$searchTerm%')";
}
if (!empty($filterCategory)) {
    $filterCategory = $conn->real_escape_string($filterCategory);
    $countQuery .= " AND Category = '$filterCategory'";
}
$totalResult = $conn->query($countQuery);
$total_programs = $totalResult->fetch_assoc()['total'];
$total_pages = ceil($total_programs / $items_per_page);

// Fetch programs with pagination
$programQuery = "SELECT * FROM pathways_opportunities_master_dnd WHERE 1=1";
if (!empty($searchTerm)) {
    $programQuery .= " AND (Program_Name LIKE '%$searchTerm%' OR Description LIKE '%$searchTerm%' OR Category LIKE '%$searchTerm%')";
}
if (!empty($filterCategory)) {
    $programQuery .= " AND Category = '$filterCategory'";
}
$programQuery .= " ORDER BY Program_Name ASC LIMIT $items_per_page OFFSET $offset";
$programsResult = $conn->query($programQuery);

// Get unique categories for filter dropdown
$allCategoriesResult = $conn->query("SELECT DISTINCT Category FROM pathways_opportunities_master_dnd ORDER BY Category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Programs Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Reset and body */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f8;
            color: #333;
        }

        /* Header - Matching index.php */
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

        /* Main content */
        main { 
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 30px;
            background: white;
            border: 2px solid #1a3c6b;
            color: #1a3c6b;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: #e8f0f8;
        }

        .tab-btn.active {
            background: #1a3c6b;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Welcome section */
        .admin-welcome {
            text-align: center;
            margin: 30px 0;
        }

        .admin-welcome h2 {
            color: #1a3c6b;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .admin-welcome p {
            color: #666;
            font-size: 1.1rem;
        }

        .admin-info {
            display: inline-block;
            background: #e8f0f8;
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* Messages - matching program card style */
        .message {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 800px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .message.error {
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        /* Statistics - using program card style */
        .stats-section {
            margin: 30px 0;
        }

        .stats-section h3 {
            text-align: center;
            color: #1a3c6b;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .stats-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            width: 280px;
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .stat-card h4 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 3rem;
            font-weight: bold;
            color: #1a3c6b;
            margin: 10px 0;
        }

        .stat-card .subtitle {
            color: #999;
            font-size: 0.85rem;
        }

        /* Section containers - matching program sections */
        section {
            margin-bottom: 50px;
        }

        section h3 {
            text-align: center;
            color: #1a3c6b;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }

        /* Form container - card style */
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a3c6b;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            transition: border 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a3c6b;
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-filter-section input,
        .search-filter-section select {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        .search-filter-section .btn {
            flex: 0;
        }

        /* Buttons - matching index style */
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
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
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-info:hover {
            background: #138496;
        }

        /* Table container - card style */
        .table-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #e8f0f8;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1a3c6b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-top: 1px solid #f0f0f0;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-admin {
            background: #1a3c6b;
            color: white;
        }

        .badge-user {
            background: #e8f0f8;
            color: #1a3c6b;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-category {
            background: #e8f0f8;
            color: #1a3c6b;
        }

        .action-btns {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bulk-actions {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .bulk-actions .btn {
            margin: 0;
        }

        .checkbox-cell {
            text-align: center;
            width: 40px;
        }

        .checkbox-cell input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .truncate {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
            max-height: 85vh;
            overflow-y: auto;
        }

        @keyframes slideIn {
            from { 
                transform: translateY(-50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: #1a3c6b;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h3 {
            margin: 0;
            color: white;
            font-size: 1.3rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #ffd700;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            position: sticky;
            bottom: 0;
        }

        /* Footer - matching index.php */
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
            nav a { 
                display: block; 
                margin: 5px 0; 
            }

            .tab-navigation {
                flex-direction: column;
            }

            .tab-btn {
                width: 100%;
            }

            .stats-container {
                flex-direction: column;
                align-items: center;
            }

            .stat-card {
                width: 90%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-container,
            .table-wrapper {
                padding: 15px;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 10px 5px;
            }

            .action-btns {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                padding: 20px;
            }

            .search-filter-section {
                flex-direction: column;
            }

            .search-filter-section input,
            .search-filter-section select {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>
<main>
    
    <!-- Welcome Section -->
    <div class="admin-welcome">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! </h2>
        <p>Manage users, programs, and monitor system statistics</p>
        <div class="admin-info">
            <strong>Role:</strong> Administrator | <strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <strong><?php echo $messageType === 'success' ? '✓' : '✗'; ?></strong>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Section -->
    <div class="stats-section">
        <h3>System Statistics</h3>
        <div class="stats-container">
            <div class="stat-card">
                <h4>Total Users</h4>
                <div class="number"><?php echo $totalUsers; ?></div>
                <div class="subtitle">Registered accounts</div>
            </div>
            <div class="stat-card">
                <h4>Active Users</h4>
                <div class="number"><?php echo $activeUsers; ?></div>
                <div class="subtitle">Currently active</div>
            </div>
            <div class="stat-card">
                <h4>Administrators</h4>
                <div class="number"><?php echo $adminCount; ?></div>
                <div class="subtitle">Admin accounts</div>
            </div>
            <div class="stat-card">
                <h4>Total Programs</h4>
                <div class="number"><?php echo $totalPrograms; ?></div>
                <div class="subtitle">Available programs</div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'users') ? 'active' : ''; ?>" onclick="switchTab('users')"> User Management</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'programs') ? 'active' : ''; ?>" onclick="switchTab('programs')"> Program Management</button>
    </div>

    <!-- USER MANAGEMENT TAB -->
    <div id="users-tab" class="tab-content <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'users') ? 'active' : ''; ?>">
        <!-- Create User Form -->
        <section id="create-user">
            <h3>Create New User</h3>
            <div class="form-container">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="555-0000">
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="text" name="password" value="password123" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="active">
                                <option value="yes">Active</option>
                                <option value="no">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </section>

        <!-- Users Table -->
        <section id="all-users">
            <h3>All Users</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($usersResult && $usersResult->num_rows > 0) {
                            while ($row = $usersResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>#" . $row['User_Id'] . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['Phone'] ?? 'N/A') . "</td>";
                                echo "<td><span class='badge badge-" . strtolower($row['Role']) . "'>" . htmlspecialchars($row['Role']) . "</span></td>";
                                echo "<td><span class='badge badge-" . ($row['Active'] === 'yes' ? 'active' : 'inactive') . "'>" . ($row['Active'] === 'yes' ? 'Active' : 'Inactive') . "</span></td>";
                                echo "<td>" . date('M d, Y', strtotime($row['Created_Time'])) . "</td>";
                                echo "<td><div class='action-btns'>";
                                
                                // Edit button for all users
                                echo "<button class='btn btn-success' onclick='openEditModal(" . json_encode($row) . ")'>Edit</button>";
                                
                                if ($row['User_Id'] != $_SESSION['user_id']) {
                                    echo "<a href='?toggle=" . $row['User_Id'] . "' class='btn btn-warning' onclick='return confirm(\"Toggle user status?\")'>Toggle</a>";
                                    echo "<a href='?delete=" . $row['User_Id'] . "' class='btn btn-danger' onclick='return confirm(\"Delete this user?\")'>Delete</a>";
                                } else {
                                    echo "<span style='color: #999; font-size: 12px;'>You</span>";
                                }
                                
                                echo "</div></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center; color: #999;'>No users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- PROGRAM MANAGEMENT TAB -->
    <div id="programs-tab" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'programs') ? 'active' : ''; ?>">
        
        <!-- Create Program Form -->
        <section id="create-program">
            <h3>Create New Program</h3>
            <div class="form-container">
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Program Name *</label>
                            <input type="text" name="program_name" required>
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <input type="text" name="category" placeholder="e.g., College Access, Career Development" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Description *</label>
                            <textarea name="description" required placeholder="Brief description of the program"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Eligibility</label>
                            <textarea name="eligibility" placeholder="Who can apply? Requirements?"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Benefits</label>
                            <textarea name="benefits" placeholder="What does this program offer?"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Application Process</label>
                            <textarea name="application_process" placeholder="How to apply"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Contact Info</label>
                            <input type="text" name="contact_info" placeholder="Email or phone">
                        </div>
                        <div class="form-group">
                            <label>Website URL</label>
                            <input type="url" name="website_url" placeholder="https://example.com">
                        </div>
                        <div class="form-group">
                            <label>Deadline</label>
                            <input type="text" name="deadline" placeholder="e.g., Rolling, May 1st">
                        </div>
                        <div class="form-group full-width">
                            <label>Additional Notes</label>
                            <textarea name="additional_notes" placeholder="Any other important information"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="create_program" class="btn btn-primary">Create Program</button>
                </form>
            </div>
        </section>

        <!-- Programs Table -->
        <section id="all-programs">
            <h3>All Programs</h3>
            
            <!-- Search and Filter -->
            <div class="search-filter-section">
                <form method="get" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    <input type="hidden" name="tab" value="programs">
                    <input type="text" name="search" placeholder="Search programs..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <select name="filter_category">
                        <option value="">All Categories</option>
                        <?php
                        if ($allCategoriesResult && $allCategoriesResult->num_rows > 0) {
                            while ($cat = $allCategoriesResult->fetch_assoc()) {
                                $selected = ($filterCategory === $cat['Category']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($cat['Category']) . "' $selected>" . htmlspecialchars($cat['Category']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="?tab=programs" class="btn btn-warning">Clear</a>
                </form>
            </div>

            <!-- Bulk Actions -->
            <form method="post" id="bulkDeleteForm" action="?tab=programs<?php 
                if (isset($_GET['search'])) echo '&search=' . urlencode($_GET['search']); 
                if (isset($_GET['filter_category'])) echo '&filter_category=' . urlencode($_GET['filter_category']); 
                if (isset($_GET['page'])) echo '&page=' . urlencode($_GET['page']); 
            ?>">
                <div class="bulk-actions">
                    <label style="font-weight: 600; color: #1a3c6b;">
                        <input type="checkbox" id="selectAll" style="width: 18px; height: 18px; margin-right: 8px; cursor: pointer;">
                        Select All
                    </label>
                    <button type="submit" name="bulk_delete_programs" class="btn btn-danger" onclick="return confirmBulkDelete()">
                         Delete Selected
                    </button>
                    <span id="selectedCount" style="color: #666; font-size: 14px;">0 selected</span>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAllHeader" style="width: 18px; height: 18px; cursor: pointer;">
                                </th>
                                <th>ID</th>
                                <th>Program Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($programsResult && $programsResult->num_rows > 0) {
                                while ($row = $programsResult->fetch_assoc()) {
                                    // Handle both possible column name formats
                                    $id = $row['Id'] ?? $row['id'] ?? $row['ID'] ?? 0;
                                    $programName = $row['Program_Name'] ?? $row['program_name'] ?? '';
                                    $category = $row['Category'] ?? $row['category'] ?? '';
                                    $description = $row['Description'] ?? $row['description'] ?? '';
                                    $deadline = $row['Deadline'] ?? $row['deadline'] ?? '';
                                    
                                    echo "<tr>";
                                    echo "<td class='checkbox-cell'><input type='checkbox' name='program_ids[]' value='" . $id . "' class='program-checkbox'></td>";
                                    echo "<td><strong>#" . $id . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($programName) . "</td>";
                                    echo "<td><span class='badge badge-category'>" . htmlspecialchars($category) . "</span></td>";
                                    echo "<td><div class='truncate'>" . htmlspecialchars(substr($description, 0, 100)) . "...</div></td>";
                                    echo "<td>" . htmlspecialchars($deadline ?: 'N/A') . "</td>";
                                    echo "<td><div class='action-btns'>";
                                    echo "<button type='button' class='btn btn-info' onclick='viewProgram(" . json_encode($row) . ")'>View</button>";
                                    echo "<button type='button' class='btn btn-success' onclick='openEditProgramModal(" . json_encode($row) . ")'>Edit</button>";
                                    
                                    // Build delete URL with preserved parameters
                                    $delete_url = '?delete_program=' . $id . '&tab=programs';
                                    if (isset($_GET['search'])) $delete_url .= '&search=' . urlencode($_GET['search']);
                                    if (isset($_GET['filter_category'])) $delete_url .= '&filter_category=' . urlencode($_GET['filter_category']);
                                    if (isset($_GET['page'])) $delete_url .= '&page=' . urlencode($_GET['page']);
                                    
                                    echo "<a href='" . $delete_url . "' class='btn btn-danger' onclick='return confirm(\"Delete this program?\")'>Delete</a>";
                                    echo "</div></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; color: #999;'>No programs found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                
                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <?php
                    // Build base URL
                    $base_url = '?tab=programs&';
                    if ($searchTerm) {
                        $base_url .= 'search=' . urlencode($searchTerm) . '&';
                    }
                    if ($filterCategory) {
                        $base_url .= 'filter_category=' . urlencode($filterCategory) . '&';
                    }
                    
                    // Ensure integers
                    $current_page = (int)$current_page;
                    $total_pages = (int)$total_pages;
                    
                    // Previous button
                    if ($current_page > 1) {
                        $prev_page = $current_page - 1;
                        echo '<a href="' . $base_url . 'page=' . $prev_page . '" class="btn btn-warning" style="margin: 0 5px;">&laquo; Previous</a>';
                    } else {
                        echo '<span class="btn" style="margin: 0 5px; background: #ccc; cursor: not-allowed;">&laquo; Previous</span>';
                    }
                    
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="' . $base_url . 'page=1" class="btn btn-primary" style="margin: 0 3px;">1</a>';
                        if ($start_page > 2) {
                            echo '<span style="margin: 0 3px;">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $current_page) {
                            echo '<span class="btn" style="margin: 0 3px; background: #1a3c6b; color: white; cursor: default;">' . $i . '</span>';
                        } else {
                            echo '<a href="' . $base_url . 'page=' . $i . '" class="btn btn-primary" style="margin: 0 3px;">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span style="margin: 0 3px;">...</span>';
                        }
                        echo '<a href="' . $base_url . 'page=' . $total_pages . '" class="btn btn-primary" style="margin: 0 3px;">' . $total_pages . '</a>';
                    }
                    
                    // Next button
                    if ($current_page < $total_pages) {
                        $next_page = $current_page + 1;
                        echo '<a href="' . $base_url . 'page=' . $next_page . '" class="btn btn-warning" style="margin: 0 5px;">Next &raquo;</a>';
                    } else {
                        echo '<span class="btn" style="margin: 0 5px; background: #ccc; cursor: not-allowed;">Next &raquo;</span>';
                    }
                    
                    echo '<div style="margin-top: 10px; color: #666; font-size: 14px;">Page ' . $current_page . ' of ' . $total_pages . ' (Total: ' . $total_programs . ' programs)</div>';
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </form>
    </div>

</main>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="edit_first_name" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="edit_last_name" id="edit_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="edit_email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="edit_phone" id="edit_phone" placeholder="555-0000">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="text" name="edit_password" id="edit_password" placeholder="Leave blank to keep current">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="edit_role" id="edit_role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="edit_active" id="edit_active">
                            <option value="yes">Active</option>
                            <option value="no">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Program Modal -->
<div id="editProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Program</h3>
            <span class="close" onclick="closeEditProgramModal()">&times;</span>
        </div>
        <form method="post">
            <div class="modal-body">
                <input type="hidden" name="program_id" id="edit_program_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Program Name *</label>
                        <input type="text" name="edit_program_name" id="edit_program_name" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <input type="text" name="edit_category" id="edit_category" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Description *</label>
                        <textarea name="edit_description" id="edit_description" required></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Eligibility</label>
                        <textarea name="edit_eligibility" id="edit_eligibility"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Benefits</label>
                        <textarea name="edit_benefits" id="edit_benefits"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Application Process</label>
                        <textarea name="edit_application_process" id="edit_application_process"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Contact Info</label>
                        <input type="text" name="edit_contact_info" id="edit_contact_info">
                    </div>
                    <div class="form-group">
                        <label>Website URL</label>
                        <input type="url" name="edit_website_url" id="edit_website_url">
                    </div>
                    <div class="form-group">
                        <label>Deadline</label>
                        <input type="text" name="edit_deadline" id="edit_deadline">
                    </div>
                    <div class="form-group full-width">
                        <label>Additional Notes</label>
                        <textarea name="edit_additional_notes" id="edit_additional_notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeEditProgramModal()">Cancel</button>
                <button type="submit" name="update_program" class="btn btn-primary">Update Program</button>
            </div>
        </form>
    </div>
</div>

<!-- View Program Modal -->
<div id="viewProgramModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="view_program_name">Program Details</h3>
            <span class="close" onclick="closeViewProgramModal()">&times;</span>
        </div>
        <div class="modal-body" id="view_program_details" style="line-height: 1.8;">
            <!-- Content will be populated by JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeViewProgramModal()">Close</button>
        </div>
    </div>
</div>

<!-- Footer - Matching index.php -->
<footer>
    <p>&copy; 2025 Student Programs Portal | All Rights Reserved</p>
</footer>

<script>
// Tab Switching
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tab + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Bulk Selection Functions
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const selectAllHeader = document.getElementById('selectAllHeader');
    const checkboxes = document.querySelectorAll('.program-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    
    // Update count display
    function updateCount() {
        const checked = document.querySelectorAll('.program-checkbox:checked').length;
        selectedCount.textContent = checked + ' selected';
        selectAll.checked = checked === checkboxes.length && checkboxes.length > 0;
        selectAllHeader.checked = selectAll.checked;
    }
    
    // Select all checkboxes
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            selectAllHeader.checked = this.checked;
            updateCount();
        });
    }
    
    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            selectAll.checked = this.checked;
            updateCount();
        });
    }
    
    // Individual checkbox change
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
    
    // Initial count
    updateCount();
});

// Confirm bulk delete
function confirmBulkDelete() {
    const checked = document.querySelectorAll('.program-checkbox:checked').length;
    if (checked === 0) {
        alert('Please select at least one program to delete.');
        return false;
    }
    return confirm(`Are you sure you want to delete ${checked} program(s)? This action cannot be undone.`);
}

// User Management Functions
function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.User_Id;
    document.getElementById('edit_first_name').value = user.First_Name;
    document.getElementById('edit_last_name').value = user.Last_Name;
    document.getElementById('edit_email').value = user.Email;
    document.getElementById('edit_phone').value = user.Phone || '';
    document.getElementById('edit_role').value = user.Role;
    document.getElementById('edit_active').value = user.Active;
    document.getElementById('edit_password').value = '';
    
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Program Management Functions
function openEditProgramModal(program) {
    // Handle both possible column name formats
    document.getElementById('edit_program_id').value = program.Id || program.id || program.ID || 0;
    document.getElementById('edit_program_name').value = program.Program_Name || program.program_name || '';
    document.getElementById('edit_category').value = program.Category || program.category || '';
    document.getElementById('edit_description').value = program.Description || program.description || '';
    document.getElementById('edit_eligibility').value = program.Eligibility || program.eligibility || '';
    document.getElementById('edit_benefits').value = program.Benefits || program.benefits || '';
    document.getElementById('edit_application_process').value = program.Application_Process || program.application_process || '';
    document.getElementById('edit_contact_info').value = program.Contact_Info || program.contact_info || '';
    document.getElementById('edit_website_url').value = program.Website_URL || program.website_url || '';
    document.getElementById('edit_deadline').value = program.Deadline || program.deadline || '';
    document.getElementById('edit_additional_notes').value = program.Additional_Notes || program.additional_notes || '';
    
    document.getElementById('editProgramModal').style.display = 'block';
}

function closeEditProgramModal() {
    document.getElementById('editProgramModal').style.display = 'none';
}

function viewProgram(program) {
    // Handle both possible column name formats
    const programName = program.Program_Name || program.program_name || 'N/A';
    const category = program.Category || program.category || 'N/A';
    const description = program.Description || program.description || 'N/A';
    const eligibility = program.Eligibility || program.eligibility || 'N/A';
    const benefits = program.Benefits || program.benefits || 'N/A';
    const applicationProcess = program.Application_Process || program.application_process || 'N/A';
    const contactInfo = program.Contact_Info || program.contact_info || 'N/A';
    const websiteUrl = program.Website_URL || program.website_url || '';
    const deadline = program.Deadline || program.deadline || 'N/A';
    const additionalNotes = program.Additional_Notes || program.additional_notes || 'N/A';
    
    document.getElementById('view_program_name').textContent = programName;
    
    let details = `
        <p><strong>Category:</strong> <span class="badge badge-category">${category}</span></p>
        <p><strong>Description:</strong><br>${description}</p>
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
        <p><strong>Eligibility:</strong><br>${eligibility}</p>
        <p><strong>Benefits:</strong><br>${benefits}</p>
        <p><strong>Application Process:</strong><br>${applicationProcess}</p>
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
        <p><strong>Contact Info:</strong> ${contactInfo}</p>
        <p><strong>Website:</strong> ${websiteUrl ? '<a href="' + websiteUrl + '" target="_blank">' + websiteUrl + '</a>' : 'N/A'}</p>
        <p><strong>Deadline:</strong> ${deadline}</p>
        <p><strong>Additional Notes:</strong><br>${additionalNotes}</p>
    `;
    
    document.getElementById('view_program_details').innerHTML = details;
    document.getElementById('viewProgramModal').style.display = 'block';
}

function closeViewProgramModal() {
    document.getElementById('viewProgramModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const editProgramModal = document.getElementById('editProgramModal');
    const viewProgramModal = document.getElementById('viewProgramModal');
    
    if (event.target == editModal) {
        closeEditModal();
    }
    if (event.target == editProgramModal) {
        closeEditProgramModal();
    }
    if (event.target == viewProgramModal) {
        closeViewProgramModal();
    }
}
</script>

</body>
</html>

<?php $conn->close(); ?>