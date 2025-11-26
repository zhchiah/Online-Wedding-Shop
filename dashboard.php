<?php
session_start();
require_once 'db_connection.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

// Get the vendor_id from the session
$vendor_id = $_SESSION['vendor_id'];

// Get total products count
$total_products = $conn->query("
    SELECT COUNT(*) as count 
    FROM products 
    WHERE vendor_id = $vendor_id
")->fetch_assoc()['count'];

$total_sales = $conn->query("
    SELECT 
        COALESCE(
            (SELECT SUM(oi.price * oi.quantity) 
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE p.vendor_id = $vendor_id
            ) +
            (SELECT COALESCE(SUM(bp.amount), 0)
             FROM booking_payments bp
             JOIN appointments a ON bp.appointment_id = a.appointment_id
             JOIN products p ON a.product_id = p.product_id
             WHERE p.vendor_id = $vendor_id AND a.status = 'paid'
            ), 
        0) as total
")->fetch_assoc()['total'];

// Get pending appointments count
$pending_appointments = $conn->query("
    SELECT COUNT(*) as count
    FROM appointments
    WHERE vendor_id = $vendor_id 
    AND status = 'scheduled'
")->fetch_assoc()['count'];

// Get total reviews count
$total_reviews = $conn->query("
    SELECT COUNT(*) as count
    FROM reviews
    WHERE vendor_id = $vendor_id
")->fetch_assoc()['count'];

// Fetch the 3 nearest upcoming appointments for the logged-in vendor
$now = date('Y-m-d H:i:s');
$upcoming_appointments = $conn->query("
    SELECT a.appointment_id, a.vendor_id, u.username, a.appointment_date, a.status
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.vendor_id = $vendor_id AND a.appointment_date >= '$now' AND a.status = 'scheduled'
    ORDER BY a.appointment_date ASC
    LIMIT 3
");

// Fetch top 3 most viewed products for the logged-in vendor
$top_viewed_products = $conn->query("
    SELECT * FROM products
    WHERE vendor_id = $vendor_id
    ORDER BY total_reviews DESC
    LIMIT 3
");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>

</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <div class="sidebar-container">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2>Vendor Dashboard</h2>
                </div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-item active">Home</a>
                    <a href="appointments.php" class="nav-item">Appointments</a>
                    <a href="products.php" class="nav-item">Add Products</a>
                    <a href="product_list.php" class="nav-item ">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>
                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Log Out</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <h1 style="text-align: center;">Welcome to Your Vendor Dashboard</h1>

            <!-- Statistics Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="value"><?php echo number_format($total_products); ?></div>
                    <div class="description">Active listings in your store</div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <div class="value">RM<?php echo number_format($total_sales, 2); ?></div>
                    <div class="description">Revenue from all orders</div>
                </div>
                
                <div class="stat-card">
                    <h3>Pending Appointments</h3>
                    <div class="value"><?php echo number_format($pending_appointments); ?></div>
                    <div class="description">Scheduled appointments</div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Reviews</h3>
                    <div class="value"><?php echo number_format($total_reviews); ?></div>
                    <div class="description">Customer feedback received</div>
                </div>
            </div>

            <!-- Existing Upcoming Appointments section -->
            <div class="appointments-section">
                <h2>Upcoming Appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Appointment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $upcoming_appointments->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $appointment['username']; ?></td>
                                <td><?php echo $appointment['appointment_date']; ?></td>
                                <td><?php echo $appointment['status']; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Existing Top 3 Most Viewed Products section -->
            <div class="top-products-section">
                <h2>Top 3 Most Viewed Products</h2>
                <div class="product-grid">
                    <?php while ($product = $top_viewed_products->fetch_assoc()) { ?>
                        <div class="product-card">
                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                            <h3><?php echo $product['name']; ?></h3>
                            <p>Price: RM<?php echo $product['price']; ?></p>
                            <p>Stock: <?php echo $product['stock_quantity']; ?></p>
                            <p>Total Reviews: <?php echo $product['total_reviews']; ?></p>
                        </div>
                    <?php } ?>
                </div>
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
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        .stat-card h3 {
            margin: 0;
            color: #4b5563;
            font-size: 0.875rem;
        }
        .stat-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
            margin-top: 8px;
        }
        .stat-card .description {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 4px;
        }
        /* Hover trigger area */
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
        body {
            background-image: url('https://static.vecteezy.com/system/resources/previews/022/769/837/non_2x/beautiful-pink-rose-flower-frame-with-watercolor-for-wedding-birthday-card-background-invitation-wallpaper-sticker-decoration-etc-vector.jpg');
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .container {
            display: flex;
            background-color: rgba(255, 255, 255, 0.8); 
        }
        .sidebar-container {
            position: relative;
        }
        .main-content {
            flex-grow: 1;
            padding: 40px;
            transition: margin-left 0.3s ease-in-out;
        }
        .appointments-section, .top-products-section {
            margin-top: 40px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 10px 5px rgba(0, 0, 0, 0.1);
        }
        .appointments-section table, .top-products-section .product-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .appointments-section th, .appointments-section td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .top-products-section .product-card {
            display: inline-block;
            width: 30%;
            margin: 10px;
            padding: 10px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .top-products-section .product-card img {
            max-width: 70%;
            height: auto;
        }

        
    </style>