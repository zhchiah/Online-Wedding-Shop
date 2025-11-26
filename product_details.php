<?php
session_start();
include 'db_connection.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: user_dashboard.php');
    exit;
}

$product_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch product details
$query = "
    SELECT 
        p.*, 
        v.business_name, 
        v.service_category
    FROM 
        products p
    LEFT JOIN 
        vendors v ON p.vendor_id = v.vendor_id
    WHERE 
        p.product_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Update view count (total_reviews)
$update_views = "UPDATE products SET total_reviews = total_reviews + 1 WHERE product_id = ?";
$stmt = $conn->prepare($update_views);
$stmt->bind_param("i", $product_id);
$stmt->execute();

// Handle "Add to Cart"
if (isset($_POST['add_to_cart'])) {
    $quantity = (int)$_POST['quantity'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product is already in the cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity; // Update quantity
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'vendor' => $product['business_name'],
            'price' => $product['price'],
            'quantity' => $quantity
        ];
    }

    echo "<script>alert('Product added to cart!');</script>";
}

// Handle Remove from Cart
if (isset($_POST['remove_from_cart'])) {
    $remove_id = (int)$_POST['remove_id'];
    unset($_SESSION['cart'][$remove_id]);
    echo "<script>alert('Product removed from cart!');</script>";
}

// Handle appointment booking
if (isset($_POST['book_appointment'])) {
    $appointment_date = $_POST['appointment_date'];
    $notes = $_POST['notes'];

    $insert_query = "
        INSERT INTO appointments (user_id, vendor_id, product_id, appointment_date, status, notes) 
        VALUES (?, ?, ?, ?, 'Scheduled', ?)
    ";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiiss", $user_id, $product['vendor_id'], $product_id, $appointment_date, $notes);
    $stmt->execute();

    echo "<script>alert('Appointment booked successfully!'); window.location.href='appointments_display.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - Book Appointment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .product-container {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }
        .product-image {
            flex: 0 0 400px;
        }
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        .product-info {
            flex: 1;
        }
        .price {
            font-size: 24px;
            color: #e44d26;
            margin: 20px 0;
        }
        .make-appointment-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-button {
            display: block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>

    <div class="product-container">
        <div class="product-image">
            <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'default.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <div class="product-info">
            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
            <p><strong>Vendor:</strong> <?php echo htmlspecialchars($product['business_name']); ?></p>
            <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
            <p><strong>Stock Quantity:</strong> <?php echo $product['stock_quantity']; ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>

            <!-- Add to Cart Button -->
            <form method="POST" style="display:inline;">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" value="1" min="1" required>
                <button type="submit" name="add_to_cart" class="add-to-cart-button">Add to Cart</button>
            </form>

            <!-- Make Appointment Button -->
            <button class="make-appointment-button" id="openModalBtn">Make an Appointment</button>
        </div>
    </div>

    <!-- Modal for Appointment -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModalBtn">&times;</span>
            <h2>Book an Appointment</h2>
            <form method="POST">
                <label for="appointment_date">Appointment Date:</label>
                <input type="datetime-local" name="appointment_date" required><br><br>
                <label for="notes">Notes:</label>
                <textarea name="notes" rows="4" cols="50"></textarea><br><br>
                <button type="submit" name="book_appointment" class="modal-button">Submit Appointment</button>
            </form>
        </div>
    </div>


    <script>
        // Get modal and buttons
        var modal = document.getElementById("appointmentModal");
        var openModalBtn = document.getElementById("openModalBtn");
        var closeModalBtn = document.getElementById("closeModalBtn");

        // Open the modal
        openModalBtn.onclick = function() {
            modal.style.display = "block";
        }

        // Close the modal
        closeModalBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Close modal if clicked outside of modal content
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>