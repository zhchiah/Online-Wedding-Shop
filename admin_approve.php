<?php
session_start();
include 'db_connection.php'; // Database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php'); // Redirect to login if not admin
    exit;
}
// Fetch all pending products (products that need admin approval) with service_category from vendors
$query = "
    SELECT 
        p.product_id, 
        p.image_url, 
        p.name, 
        p.description, 
        p.category, 
        p.price, 
        p.stock_quantity, 
        v.service_category 
    FROM 
        products p
    JOIN 
        vendors v 
    ON 
        p.vendor_id = v.vendor_id
    WHERE 
        p.status = 'pending'
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_product'])) {
        $product_id = $_POST['product_id'];
        $update_query = "UPDATE products SET status = 'approved' WHERE product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $product_id);
        $update_stmt->execute();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } 
    elseif (isset($_POST['reject_product'])) {
        $product_id = $_POST['product_id'];
        
        // Get image URL before deleting
        $image_query = "SELECT image_url FROM products WHERE product_id = ?";
        $image_stmt = $conn->prepare($image_query);
        $image_stmt->bind_param("i", $product_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $product = $image_result->fetch_assoc();
        
        // Delete the product
        $delete_query = "DELETE FROM products WHERE product_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            // Delete image file if exists
            if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                unlink($product['image_url']);
            }
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Product Approval</title>
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        table:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: scale(1.01);
        }

        thead {
            background-color: rgba(0, 0, 0, 0.05);
        }

        th {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
            font-weight: 700;
            text-transform: uppercase;
            color: black;
            font-size: 0.9em;
            letter-spacing: 1px;
            background-color: rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        th:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: black;
            transition: all 0.3s ease;
        }

        tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        
        .product-image {
            max-width: 100px;
            max-height: 100px;
            display: block;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .approve-btn, .reject-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .approve-btn {
            background-color: #4CAF50;
            color: white;
        }
        .reject-btn {
            background-color: #f44336;
            color: white;
        }
        .price {
            font-family: monospace;
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
    </style>
</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Product Approval</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item">Home</a>      
                <a href="admin_approve.php" class="nav-item active">Product Approval</a>
                <a href="manageuserpage.php" class="nav-item">User</a>                
                <a href="managevendorpage.php" class="nav-item">Vendor</a>
                <a href="manageadminpage.php" class="nav-item">Admin</a>                
                <a href="admin_account.php" class="nav-item">Account</a>
                <a href="logout.php" class="nav-item">Log Out</a>
            </nav>
        </div>

        <div class="main-content">
            <h1>Admin - Approve or Reject Products</h1>
            
            <h2>Pending Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product Image</th>
                        <th>Vendor</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         class="product-image" 
                                         alt="Product Image">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['service_category']); ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td class="price">RM<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" 
                                               value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" name="approve_product" 
                                                class="approve-btn">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" 
                                               value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" name="reject_product" 
                                                class="reject-btn"
                                                onclick="return confirm('Are you sure you want to reject this product?')">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>
</body>
</html>