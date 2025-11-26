<?php
session_start();
include 'db_connection.php'; 

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php'); // Redirect to login if not admin
    exit;
}

$admin_id = $_SESSION['user_id']; 

// Fetch the admin's details
$query = "SELECT username, email, user_type FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
} else {
    die('Admin details not found.');
}

// Handle form submission for updating admin details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];

    $update_query = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ssi', $username, $email, $admin_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Profile updated successfully.');</script>";
        // Reload the updated data
        header("Refresh:0");
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management</title>
</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Account</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item">Home</a>      
                <a href="admin_approve.php" class="nav-item ">Product Approval</a>
                <a href="manageuserpage.php" class="nav-item">User</a>                
                <a href="managevendorpage.php" class="nav-item">Vendor</a>
                <a href="manageadminpage.php" class="nav-item ">Admin</a>                
                <a href="admin_account.php" class="nav-item active">Account</a>
                <a href="logout.php" class="nav-item">Log Out</a>
            </nav>
        </div>

        <div class="main-content">
            <h1>Profile</h1>
<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
        </div>
        
        <button type="submit" class="submit-btn">Update Profile</button>
    </form>
</div>
        </div>
    </div>
    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>
</body>
</html>


    <style>
        body {
            background-image: url('https://www.wallpapergap.com/wp-content/uploads/2024/10/half-couple-wallpapers.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
        }

        .main-content h1 {
            color: black;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            letter-spacing: 1px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .main-content h1:hover {
            transform: scale(1.05);
            color: #2c3e50;
        }

        .main-content h2 {
            color: black;
            text-align: center;
            font-size: 1.8em;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .main-content h2:hover {
            transform: scale(1.03);
            color: #34495e;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .edit-btn {
            background-color: #2196F3;
            color: white;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .close-btn {
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            float: right;
        }

        /* New Sidebar Styles */
        .hover-trigger {
            position: fixed;
            left: 0;
            top: 0;
            width: 20px;
            height: 100vh;
            z-index: 998;
        }
        .sidebar {
            position: fixed;
            left: -250px;
            width: 250px;
            height: 100vh;
            background-color: #FFF3E0;
            padding: 20px 0;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .hover-trigger:hover + .container .sidebar,
        .sidebar:hover {
            transform: translateX(250px);
        }
        .hover-trigger:hover + .container .main-content,
        .sidebar:hover + .main-content {
            margin-left: 250px;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            padding: 20px 0;
        }
        .nav-item {
            display: block;
            padding: 15px 20px;
            color: #666;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .nav-item:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        .nav-item.active {
            background-color: hotpink;
            color: white;
            font-weight: bold;
        }

        /* Footer Styles */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #FFF3E0;
            padding: 10px 20px;
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 997;
            transition: margin-left 0.3s ease-in-out;
        }
        .footer p {
            margin: 5px 0;
        }

        /* Container for main content */
        .container {
            min-height: 100vh;
            padding-bottom: 60px; /* Space for footer */
        }
        .main-content {
            transition: margin-left 0.3s ease-in-out;
            padding: 20px;
        }

    .form-container {
        background: rgba(255, 255, 255, 0.9);
        max-width: 350px;
        margin: 50px auto;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .form-container:hover {
        transform: scale(1.02);
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .form-group input {
        width: 100%;
        padding: 12px 4px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: hotpink;
        box-shadow: 0 0 8px rgba(255, 105, 180, 0.3);
    }

    .form-group input:hover {
        border-color: #b0b0b0;
    }

    .submit-btn {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: hotpink;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #ff1493;
        transform: translateY(-3px);
        box-shadow: 0 5px 10px rgba(255, 20, 147, 0.3);
    }

    </style>