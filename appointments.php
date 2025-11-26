<?php
session_start();
include 'db_connection.php';

// Security check to ensure the vendor is logged in
if (!isset($_SESSION['vendor_id'])) {
    header('Location: login.php'); // Redirect to login if vendor not logged in
    exit();
}

// Handle status update (if applicable)
if (isset($_POST['update_status']) && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $update_query = "UPDATE appointments SET status = ? WHERE appointment_id = ? AND vendor_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $status, $appointment_id, $_SESSION['vendor_id']);
    $stmt->execute();

    if ($stmt->error) {
        error_log("Update error: " . $stmt->error);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch appointments with product and user details based on vendor_id
try {
    $vendor_id = $_SESSION['vendor_id']; // Get the vendor ID from session

    // Base query to get appointments
    $query = "SELECT a.appointment_id, a.appointment_date, a.status, a.notes, 
                     a.product_id, p.name, p.category, p.price, p.image_url, u.username
              FROM appointments a
              JOIN products p ON a.product_id = p.product_id
              JOIN users u ON a.user_id = u.user_id
              WHERE a.vendor_id = ?";

    // Add category filter if selected
    $category_filter = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : null;
    if ($category_filter) {
        $query .= " AND p.category = ?";
    }

    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters based on whether category filter is applied
    if ($category_filter) {
        $stmt->bind_param("is", $vendor_id, $category_filter);
    } else {
        $stmt->bind_param("i", $vendor_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $appointments = $result->fetch_all(MYSQLI_ASSOC); // Fetch all appointments for the vendor
} catch (Exception $e) {
    error_log("Error in fetching appointments: " . $e->getMessage());
    $error_message = "An error occurred while fetching appointments.";
    $appointments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Appointments</title>
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
        .appointments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .appointments-table th, .appointments-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            text-align: left;
        }
        .appointments-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .debug-info {
            background-color: #fff3cd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ffeeba;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
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
                    <a href="appointments.php" class="nav-item active">Appointments</a>
                    <a href="products.php" class="nav-item ">Add Product</a>
                    <a href="product_list.php" class="nav-item">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>

                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Log Out</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <h2 style="text-align: center;">Appointment</h2>            

<div class="category-filter">
    <form method="get" action="">
        <label for="category-select" >Filter by Category:</label>
        <select name="category" id="category-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <option value="wedding_dress" <?= isset($_GET['category']) && $_GET['category'] == 'wedding_dress' ? 'selected' : '' ?>>Wedding Dress</option>
            <option value="tuxedo" <?= isset($_GET['category']) && $_GET['category'] == 'tuxedo' ? 'selected' : '' ?>>Tuxedo</option>
            <option value="bouquet" <?= isset($_GET['category']) && $_GET['category'] == 'bouquet' ? 'selected' : '' ?>>Bouquet</option>
            <option value="centerpiece" <?= isset($_GET['category']) && $_GET['category'] == 'centerpiece' ? 'selected' : '' ?>>Centerpiece</option>
            <option value="invitation" <?= isset($_GET['category']) && $_GET['category'] == 'invitation' ? 'selected' : '' ?>>Invitation</option>
            <option value="favor" <?= isset($_GET['category']) && $_GET['category'] == 'favor' ? 'selected' : '' ?>>Favor</option>
            <option value="ring" <?= isset($_GET['category']) && $_GET['category'] == 'ring' ? 'selected' : '' ?>>Ring</option>
        </select>
    </form>
</div>            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Debug information - Comment out in production -->
            <div class="debug-info">
                <p>Number of appointments found: <?= isset($appointments) ? count($appointments) : '0' ?></p>
            </div>

            <?php if (empty($appointments)): ?>
                <p>No appointments found.</p>
            <?php else: ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Product Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Date & Time</th>
                            <th>Notes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?= htmlspecialchars($appointment['username']) ?></td>
                                <td><img src="<?= htmlspecialchars($appointment['image_url']) ?>" alt="Product Image" class="product-image"></td>
                                <td><?= htmlspecialchars($appointment['name']) ?></td>
                                <td><?= htmlspecialchars($appointment['category']) ?></td>
                                <td>RM<?= htmlspecialchars($appointment['price']) ?></td>
                                <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($appointment['notes']) ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="appointment_id" 
                                               value="<?= htmlspecialchars($appointment['appointment_id']) ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="scheduled" <?= $appointment['status'] == 'scheduled' ? 'selected' : '' ?>>
                                                Scheduled
                                            </option>
                                            <option value="confirmed" <?= $appointment['status'] == 'confirmed' ? 'selected' : '' ?>>
                                                Confirmed
                                            </option>
                                            <option value="completed" <?= $appointment['status'] == 'completed' ? 'selected' : '' ?>>
                                                Completed
                                            </option>
                                            <option value="cancelled" <?= $appointment['status'] == 'cancelled' ? 'selected' : '' ?>>
                                                Cancelled
                                            </option>
                                            <option value="paid" <?= $appointment['status'] == 'paid' ? 'selected' : '' ?>>
                                                Paid
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>
        </footer>
</body>
</html>