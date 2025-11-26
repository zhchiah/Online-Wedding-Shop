<?php
session_start();

// Separate database connection file recommended
function getDatabaseConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "wedding_application";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    // Check connection
    if (!$conn) {
        error_log("Database Connection Failed: " . mysqli_connect_error());
        die("Connection failed. Please check server logs.");
    }

    return $conn;
}

// Ensure connection is established
$connection = getDatabaseConnection();

// Check admin authentication
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Function to get total count with error handling
function getTotalCount($connection, $table, $condition = '') {
    if (!$connection) {
        error_log("Invalid database connection in getTotalCount");
        return 0;
    }

    $query = "SELECT COUNT(*) as total FROM $table $condition";
    $result = mysqli_query($connection, $query);
    
    if (!$result) {
        error_log("Query failed: " . mysqli_error($connection));
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Fetch dashboard statistics
$totalUsers = getTotalCount($connection, 'users', "WHERE user_type = 'user'");
$totalVendors = getTotalCount($connection, 'users', "WHERE user_type = 'vendor'");
$totalPendingProducts = getTotalCount($connection, 'products', "WHERE status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        /* Global Styles */
        body {
            background-image: url('https://www.wallpapergap.com/wp-content/uploads/2024/10/half-couple-wallpapers.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;


            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Dashboard Card Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); 
            gap: 20px;
        }

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: scale(1.05);
        }

        .card-title {
            color: #555;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }

        /* Color-coded Card Borders */
        .users-card { border-top: 5px solid #3498db; }
        .vendors-card { border-top: 5px solid #2ecc71; }
        .products-card { border-top: 5px solid #f39c12; }
        .appointments-card { border-top: 5px solid #e74c3c; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Hover Trigger Area Styles */
        .hover-trigger {
            position: fixed;
            left: 0;
            top: 0;
            width: 20px;
            height: 100vh;
            z-index: 998;
        }

        /* Animated Sidebar Styles */
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

        /* Show sidebar when hovering over trigger area or sidebar itself */
        .hover-trigger:hover + .container .sidebar,
        .sidebar:hover {
            transform: translateX(250px);
        }

        /* Shift main content when sidebar is visible */
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
    </style>
</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Home</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item active">Home</a>      
                <a href="admin_approve.php" class="nav-item ">Product Approval</a>
                <a href="manageuserpage.php" class="nav-item">User</a>                
                <a href="managevendorpage.php" class="nav-item">Vendor</a>
                <a href="manageadminpage.php" class="nav-item">Admin</a>                
                <a href="admin_account.php" class="nav-item">Account</a>
                <a href="logout.php" class="nav-item">Log Out</a>
            </nav>
        </div>

    <div class="main-content">
        <h1 class="text-center mb-4">Admin Dashboard</h1>
        
        <div class="dashboard-grid">
            <div class="dashboard-card users-card">
                <h3 class="card-title">Total Users</h3>
                <div class="card-value"><?php echo $totalUsers; ?></div>
            </div>
            
            <div class="dashboard-card vendors-card">
                <h3 class="card-title">Total Vendors</h3>
                <div class="card-value"><?php echo $totalVendors; ?></div>
            </div>
            
            <div class="dashboard-card products-card">
                <h3 class="card-title">Pending Products</h3>
                <div class="card-value"><?php echo $totalPendingProducts; ?></div>
            </div>
            

        </div>
    </div>

    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>
</body>
</html>