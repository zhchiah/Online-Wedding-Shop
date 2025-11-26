<?php
session_start();
include'db_connection.php';



if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['vendor_id'];

// Verify vendor exists
$vendor_check_query = "SELECT * FROM vendors WHERE vendor_id = ?";
$vendor_stmt = $conn->prepare($vendor_check_query);
$vendor_stmt->bind_param("i", $vendor_id);
$vendor_stmt->execute();
$vendor_result = $vendor_stmt->get_result();

if ($vendor_result->num_rows === 0) {
    echo "Vendor does not exist.";
    exit;
}

// Handle Add Product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === 0) {
        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $destination)) {
                $image_url = $destination;
            }
        }
    }

    $query = "INSERT INTO products (vendor_id, name, description, category, price, stock_quantity, image_url, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssdis", $vendor_id, $name, $description, $category, $price, $stock_quantity, $image_url);
    if ($stmt->execute()) {
        $success = "Product is under review and pending approval from the admin.";
    } else {
        $error = "Failed to add product: " . $stmt->error;
    }
}

// Fetch Approved Products for the Vendor
$query = "SELECT * FROM products WHERE vendor_id=? AND status='approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add a New Products</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
        .rating {
            color: #f8ce0b;
        }
        #editModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }

    /* Product Form Styling */
    .product-form {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 20px auto;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    .product-form h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .form-group input, 
    .form-group textarea, 
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        box-sizing: border-box;
        transition: all 0.3s ease;
        outline: none;
    }
    .form-group input:focus, 
    .form-group textarea:focus, 
    .form-group select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    .product-form button {
        width: 100%;
        padding: 14px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 1px;
    }
    .product-form button:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }

    /* Product Table Styling */
    table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: hidden;
    }
    table th {
        background-color: #3498db;
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    table tr:hover {
        background-color: #f1f3f5;
        transition: background-color 0.3s ease;
    }
    table td {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
    }
    .product-image {
        max-width: 100px;
        max-height: 100px;
        border-radius: 8px;
        object-fit: cover;
    }
    .rating {
        color: #f39c12;
        font-weight: bold;
    }
    table button {
        padding: 8px 15px;
        margin: 0 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    table button:first-child {
        background-color: #2ecc71;
        color: white;
    }
    table button:last-child {
        background-color: #e74c3c;
        color: white;
    }
    table button:hover {
        opacity: 0.8;
        transform: translateY(-2px);
    }

    /* Message Styling */
    .message {
        background-color: #2ecc71;
        color: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 15px;
    }
    .error {
        background-color: #e74c3c;
        color: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 15px;
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
                    <a href="products.php" class="nav-item active">Add Product</a>
                    <a href="product_list.php" class="nav-item">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>

                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Log Out</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <h2 style="text-align: center;">Product Information</h2>            

            <?php if (isset($success)) echo "<p class='message'>$success</p>"; ?>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <!-- Add Product Form -->
            <div class="product-form">
                <h2>Add Product</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="wedding_dress">Wedding Dress</option>
                            <option value="tuxedo">Tuxedo</option>
                            <option value="bouquet">Bouquet</option>
                            <option value="centerpiece">Centerpiece</option>
                            <option value="invitation">Invitation</option>
                            <option value="favor">Favor</option>
                            <option value="ring">Ring</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" required>
                    </div>
                    <div class="form-group">
                        <label for="image_url">Product Image</label>
                        <input type="file" id="image_url" name="image_url" accept="image/*" required>
                    </div>
                    <button type="submit" name="add_product">Add Product</button>
                </form>
            </div>
    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>
        </footer>
</body>
</html>