<?php
session_start();
require_once 'db_connect.php';

$message = '';
$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT User_Id, First_Name, Last_Name, Email, Role, Hash, Active FROM users WHERE Email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['Active'] !== 'yes') {
                $message = "Account is not active.";
            } elseif (password_verify($password, $user['Hash'])) {
                $_SESSION['user_id'] = $user['User_Id'];
                $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['user_role'] = $user['Role'];

                if ($user['Role'] === 'admin') {
                    header('Location: admin.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $message = "Incorrect password.";
            }
        } else {
            $message = "No user found with that email.";
        }
        $stmt->close();
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $message = 'Please fill in all required fields.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } else {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT User_Id FROM users WHERE Email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = 'An account with this email already exists.';
        } else {
            // Create new user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (First_Name, Last_Name, Email, Phone, Role, Active, Hash, Created_Time) VALUES (?, ?, ?, ?, 'user', 'yes', ?, NOW())");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hash);

            if ($stmt->execute()) {
                $message = 'Registration successful! You can now log in.';
                $mode = 'login';
            } else {
                $message = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'login' ? 'Login' : 'Register'; ?> - Student Programs Portal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        input[type="submit"]:hover {
            transform: translateY(-2px);
        }
        .toggle-mode {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .toggle-mode a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .toggle-mode a:hover {
            text-decoration: underline;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <h2><?php echo $mode === 'login' ? 'Welcome Back!' : 'Create Account'; ?></h2>
    <p class="subtitle">Student Programs Portal</p>
    
    <?php if (!empty($message)): ?>
        <div class="message <?php echo (strpos($message, 'success') !== false) ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($mode === 'login'): ?>
        <!-- Login Form -->
        <form method="post">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <input type="submit" name="login" value="Login">
        </form>
        
        <div class="toggle-mode">
            Don't have an account? <a href="?mode=register">Register here</a>
        </div>
        
    <?php else: ?>
        <!-- Registration Form -->
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="reg_email" required value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="555-0000" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password * (min 6 characters)</label>
                <input type="password" name="reg_password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <input type="submit" name="register" value="Create Account">
        </form>
        
        <div class="toggle-mode">
            Already have an account? <a href="?mode=login">Login here</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>