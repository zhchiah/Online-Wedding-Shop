<?php
session_start();
include 'db_connection.php';

// Function to sanitize input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $username = sanitize_input($_POST['username']);
            $password = $_POST['password'];

            if (empty($username) || empty($password)) {
                $_SESSION['error'] = "Username and password are required.";
            } else {
                $query = "SELECT * FROM users WHERE username=? OR email=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    if (password_verify($password, $user['password_hash'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_type'] = $user['user_type'];

                        if ($user['user_type'] == 'admin') {
                            header('Location: admindashboard.php');
                            exit();
                        } elseif ($user['user_type'] == 'vendor') {
                            $vendor_check_query = "SELECT vendor_id FROM vendors WHERE user_id=?";
                            $vendor_stmt = $conn->prepare($vendor_check_query);
                            $vendor_stmt->bind_param("i", $user['user_id']);
                            $vendor_stmt->execute();
                            $vendor_result = $vendor_stmt->get_result();

                            if ($vendor_result->num_rows > 0) {
                                $vendor = $vendor_result->fetch_assoc();
                                $_SESSION['vendor_id'] = $vendor['vendor_id'];
                                header('Location: dashboard.php');
                                exit();
                            } else {
                                $_SESSION['error'] = "Vendor account not properly set up.";
                            }
                        } else {
                            header('Location: homepage.php');
                            exit();
                        }
                    } else {
                        $_SESSION['error'] = "Invalid username/email or password.";
                    }
                } else {
                    $_SESSION['error'] = "User not found.";
                }
            }
        } elseif ($_POST['action'] === 'register') {
            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm-password'];
            $user_type = sanitize_input($_POST['user_type']); // Add this line

            // Validate user type
            $allowed_types = ['user', 'vendor']; // Don't allow direct admin registration
            if (!in_array($user_type, $allowed_types)) {
                $_SESSION['error'] = "Invalid user type selected.";
            } elseif (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $_SESSION['error'] = "All fields are required.";
            } elseif ($password !== $confirm_password) {
                $_SESSION['error'] = "Passwords do not match.";
            } else {
                // Check if username or email exists
                $check_query = "SELECT user_id FROM users WHERE username = ? OR email = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $_SESSION['error'] = "Username or email already exists.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert_query = "INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ssss", $username, $email, $password_hash, $user_type);

                    if ($stmt->execute()) {
                        // If registering as a vendor, create vendor entry
                        if ($user_type === 'vendor') {
                            $user_id = $stmt->insert_id;
                            $vendor_query = "INSERT INTO vendors (user_id) VALUES (?)";
                            $vendor_stmt = $conn->prepare($vendor_query);
                            $vendor_stmt->bind_param("i", $user_id);
                            $vendor_stmt->execute();
                        }

                        $_SESSION['success'] = "Registration successful! Please login.";
                        header('Location: login.php');
                        exit();
                    } else {
                        $_SESSION['error'] = "Registration failed.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register - Your System</title>
    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #FFD5E5, #FFBCBC, #FFF0F5);
            background-size: 400% 400%;
            animation: gradientFlow 15s ease infinite;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            perspective: 1000px;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            margin: 20px;
            background-color: #FFF3E0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }

        .auth-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
            user-select: none;
        }

        .auth-tab.active {
            color: hotpink;
        }

        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: hotpink;
        }

        .forms-container {
            position: relative;
            min-height: 300px;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .form-group label {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: hotpink;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 105, 180, 0.1);
        }

        .auth-button {
            width: 100%;
            background-color: hotpink;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .auth-button:hover {
            background-color: #ff429a;
            transform: scale(1.02);
        }

        .error-message, .success-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background-color: #ffe6e6;
            color: #d63031;
        }

        .success-message {
            background-color: #e6ffe6;
            color: #27ae60;
        }

        .form-select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

            .form-select:focus {
                border-color: hotpink;
                outline: none;
                box-shadow: 0 0 0 2px rgba(255, 105, 180, 0.1);
            }

            .form-select:hover {
                border-color: #ccc;
            }

            /* Style for the options within select */
            .form-select option {
                padding: 12px;
            }

                /* Custom styling for the selected option */
                .form-select option:checked {
                    background-color: hotpink;
                    color: white;
                }


.wedding-form-container {
    background: white;
    border-radius: 10%   ; 
    box-shadow: 0 20px 40px rgba(255, 105, 180, 0.2);
    padding: 40px;
    width: 450px;

    transform: rotateX(0deg); 
    transition: all 0.9s;
    position: relative;
    overflow: hidden;
}

.wedding-form-container::before {
    content: '💕';
    position: absolute;
    top: -20px;
    right: -20px;
    font-size: 100px;
    opacity: 0.1;
    z-index: 1;
}

        
    h1.form-title, h2.form-title {
        font-family: 'Great Vibes', cursive;
        color: #FF69B4;
        text-align: center;
        text-shadow: 2px 2px 4px rgba(255,105,180,0.2);
        margin-bottom: 20px;
    }

    h1.form-title {
        font-size: 3em; 
    }

    h2.form-title {
        font-size: 1.5em; /
        margin-top: -10px; 
    }

        input, select {
            width: 95%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #FFB6C1;
            border-radius: 15px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #FF1493;
            box-shadow: 0 0 15px rgba(255,20,147,0.2);
        }
        
        button {
            background: linear-gradient(45deg, #FF69B4, #FF1493);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 15px;
            width: 100%;
            font-weight: bold;
            letter-spacing: 1px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        button:hover {
            transform: scale(1.05) rotate(2deg);
            box-shadow: 0 10px 20px rgba(255,20,147,0.3);
        }
        
        #vendor_fields {
            background: rgba(255,182,193,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
        }
    </style>
</head>
<body>


    <div class="wedding-form-container">
        <div class="auth-header">
            <img src="logo.png" alt="logo" width="200px">
        <h2 class="form-title">Welcome To <br>Graceful Gatherings</h2>
        </div>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='success-message'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        ?>

        <div class="auth-tabs">
            <div class="auth-tab active" onclick="switchTab('login', this)">Login</div>
            <div class="auth-tab" onclick="switchTab('register', this)">Register</div>
        </div>

        <div class="forms-container">
            <!-- Login Form -->
            <form class="auth-form active" id="loginForm" method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="auth-button">Sign In</button>
            </form>

            <!-- Register Form -->
           <form class="auth-form" id="registerForm" method="POST" action="">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="reg-username">Username</label>
                    <input type="text" id="reg-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="user_type">Account Type</label>
                    <select id="user_type" name="user_type" required class="form-select">
                        <option value="">Select Account Type</option>
                        <option value="user">Customer</option>
                        <option value="vendor">Vendor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <button type="submit" class="auth-button">Create Account</button>
            </form>
        </div>
    </div>


    <script>
        function switchTab(tab, clickedTab) {
            // Update tabs
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            clickedTab.classList.add('active');

            // Switch forms
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (tab === 'login') {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
            } else {
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
            }
        }
    </script>
</body>
</html>