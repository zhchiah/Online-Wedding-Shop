<?php
include 'catalogdb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['vendor_id'];

function getSalesReport($vendor_id, $start_date = null, $end_date = null)
{
    $conn = getDBConnection();
    try {
        // Product sales query
        $sql = "SELECT 
                p.name AS product_name, 
                p.image_url AS product_image,
                SUM(oi.quantity) AS total_quantity_sold, 
                SUM(oi.quantity * oi.price) AS total_product_revenue,
                AVG(oi.price) AS average_product_price
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE p.vendor_id = :vendor_id";
        
        // Appointment sales query
        $appointment_sql = "SELECT 
                p.name AS product_name, 
                p.image_url AS product_image,
                COUNT(a.appointment_id) AS total_appointments,
                SUM(bp.amount) AS total_appointment_revenue
                FROM appointments a
                JOIN products p ON a.product_id = p.product_id
                LEFT JOIN booking_payments bp ON a.appointment_id = bp.appointment_id
                WHERE p.vendor_id = :vendor_id AND a.status = 'paid'";
        
        $params = [':vendor_id' => $vendor_id];
        
        // Apply date range filter
        if ($start_date && $end_date) {
            // Ensure dates are in the correct format for MySQL
            $start_date = date('Y-m-d', strtotime($start_date));
            $end_date = date('Y-m-d', strtotime($end_date));
            
            $sql .= " AND DATE(o.order_date) BETWEEN :start_date AND :end_date";
            $appointment_sql .= " AND DATE(a.appointment_date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        // Complete the queries with grouping
        $sql .= " GROUP BY p.product_id, p.name, p.image_url";
        $appointment_sql .= " GROUP BY p.product_id, p.name, p.image_url";
        
        // Prepare and execute queries
        $product_sales_stmt = $conn->prepare($sql);
        $product_sales_stmt->execute($params);
        $product_sales = $product_sales_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $appointment_sales_stmt = $conn->prepare($appointment_sql);
        $appointment_sales_stmt->execute($params);
        $appointment_sales = $appointment_sales_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'product_sales' => $product_sales,
            'appointment_sales' => $appointment_sales
        ];
    } catch (PDOException $e) {
        die("Sales report query failed: " . $e->getMessage());
    }
}

function getTotalSales($vendor_id, $start_date = null, $end_date = null)
{
    $conn = getDBConnection();
    try {
        // Product sales query
        $sql = "SELECT 
                SUM(oi.quantity * oi.price) AS total_product_revenue
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE p.vendor_id = :vendor_id";
        
        // Appointment sales query
        $appointment_sql = "SELECT 
                SUM(bp.amount) AS total_appointment_revenue
                FROM appointments a
                JOIN products p ON a.product_id = p.product_id
                LEFT JOIN booking_payments bp ON a.appointment_id = bp.appointment_id
                WHERE p.vendor_id = :vendor_id AND a.status = 'paid'";
        
        $params = [':vendor_id' => $vendor_id];
        
        // Apply date range filter
        if ($start_date && $end_date) {
            // Ensure dates are in the correct format for MySQL
            $start_date = date('Y-m-d', strtotime($start_date));
            $end_date = date('Y-m-d', strtotime($end_date));
            
            $sql .= " AND DATE(o.order_date) BETWEEN :start_date AND :end_date";
            $appointment_sql .= " AND DATE(a.appointment_date) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        // Prepare and execute queries
        $product_revenue_stmt = $conn->prepare($sql);
        $product_revenue_stmt->execute($params);
        $product_revenue = $product_revenue_stmt->fetchColumn() ?? 0;
        
        $appointment_revenue_stmt = $conn->prepare($appointment_sql);
        $appointment_revenue_stmt->execute($params);
        $appointment_revenue = $appointment_revenue_stmt->fetchColumn() ?? 0;
        
        return [
            'total_product_revenue' => $product_revenue,
            'total_appointment_revenue' => $appointment_revenue,
            'total_sales' => $product_revenue + $appointment_revenue
        ];
    } catch (PDOException $e) {
        die("Total sales query failed: " . $e->getMessage());
    }
}

// Handle date filter form submission
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Fetch data
$sales_report = getSalesReport($vendor_id, $start_date, $end_date);
$total_sales = getTotalSales($vendor_id, $start_date, $end_date);

// Display the sales report
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

.product-image:hover {
    transform: scale(1.1);
}

.sales-summary {
    background-color: #4CAF50;
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-top: 30px;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.sales-summary h2 {
    margin-top: 0;
    font-size: 24px;
    font-weight: bold;
}

.sales-summary p {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0 0;
}

.filter-form {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}

.filter-form label {
    margin-right: 10px;
    font-weight: bold;
}

.filter-form input[type="date"] {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    margin-right: 10px;
}

.filter-form button {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.filter-form button:hover {
    background-color: #45a049;
}
    </style>
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
                    <a href="dashboard.php" class="nav-item">Home</a>
                    <a href="appointments.php" class="nav-item">Appointments</a>
                    <a href="products.php" class="nav-item ">Add Product</a>
                    <a href="product_list.php" class="nav-item ">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>

                    <a href="reports.php" class="nav-item active">Sales</a>

                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Logout</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <h2 style="text-align: center;">Sales Report</h2>            
        <!-- Filter Form -->

    <form method="GET" action="reports.php" class="filter-form">

        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        <button type="submit">Filter</button>
    </form>

    <table>
    <h3>Product Sales</h3>
    <table>
        <thead>
            <tr>
                <th>Product Image</th>
                <th>Product Name</th>
                <th>Quantity Sold</th>
                <th>Average Price</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales_report['product_sales'])): ?>
                <?php foreach ($sales_report['product_sales'] as $product): ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars($product['product_image'] ?? '/api/placeholder/100/100'); ?>" 
                             alt="<?= htmlspecialchars($product['product_name']); ?>" 
                             class="product-image"
                             onerror="this.src='/api/placeholder/100/100';">
                    </td>
                    <td><?= htmlspecialchars($product['product_name']); ?></td>
                    <td><?= $product['total_quantity_sold']; ?></td>
                    <td>RM<?= number_format($product['average_product_price'], 2); ?></td>
                    <td>RM<?= number_format($product['total_product_revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No product sales data available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Appointment Sales -->
    <h3>Appointment Sales</h3>
    <table>
        <thead>
            <tr>
                <th>Product Image</th>
                <th>Product Name</th>
                <th>Total Appointments</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
       <?php if (!empty($sales_report['appointment_sales'])): ?>
            <?php foreach ($sales_report['appointment_sales'] as $appointment): ?>
            <tr>
                <td>
                    <img src="<?= htmlspecialchars($appointment['product_image']); ?>" 
                         alt="<?= htmlspecialchars($appointment['product_name']); ?>" 
                         style="max-width: 100px; max-height: 100px;">
                </td>
                <td><?= htmlspecialchars($appointment['product_name']); ?></td>

                <td><?= $appointment['total_appointments']; ?></td>
                <td>RM<?= number_format($appointment['total_appointment_revenue'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No appointment sales data available.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
    <!-- Total Sales Summary -->
   <div class="sales-summary">
    <p>Total Product Revenue: RM<?= number_format($total_sales['total_product_revenue'], 2); ?></p>
    <p>Total Appointment Revenue: RM<?= number_format($total_sales['total_appointment_revenue'], 2); ?></p>
    <p>Total Sales: RM<?= number_format($total_sales['total_sales'], 2); ?></p>
    </div>
        <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>
</body>
</html>

<style>
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
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            max-width: 1200px;
            margin: 0 auto;
        }
        .message { color: green; }
        .error { color: red; }
        .product-form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
</style>