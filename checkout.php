<?php
require_once 'catalogdb.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($user_id);
$total_price = 0;

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation
    $required_fields = ['name', 'email', 'delivery_method', 'card_number', 'expiry', 'cvv'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // Additional validations based on delivery method
    if ($_POST['delivery_method'] == 'delivery') {
        $delivery_required_fields = ['address', 'city', 'state', 'zip'];
        foreach ($delivery_required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required for delivery.";
            }
        }
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // If no errors, process order
    if (empty($errors)) {
        $conn = getDBConnection();

        try {
            // Start transaction
            $conn->beginTransaction();

            // Calculate total price
            $total_price = 0;
            foreach ($cart_items as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }

            // Add delivery fee for delivery method
            $total_price += ($_POST['delivery_method'] == 'delivery') ? 5.00 : 0;

            // Insert order with delivery method
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, order_date, status, delivery_method, pickup_location) VALUES (:user_id, :total, NOW(), 'pending', :delivery_method, :pickup_location)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':total' => $total_price,
                ':delivery_method' => $_POST['delivery_method'],
                ':pickup_location' => ($_POST['delivery_method'] == 'pickup') ? '123 Main St, Storefront Location' : NULL
            ]);

            $order_id = $conn->lastInsertId();

            // Insert payment details
            $stmt = $conn->prepare("INSERT INTO payment_details (order_id, card_number, expiry, cvv) VALUES (:order_id, :card_number, :expiry, :cvv)");
            $stmt->execute([
                ':order_id' => $order_id,
                ':card_number' => $_POST['card_number'],
                ':expiry' => $_POST['expiry'],
                ':cvv' => $_POST['cvv']
            ]);

            // Insert delivery/shipping details if delivery method is delivery
            if ($_POST['delivery_method'] == 'delivery') {
                $stmt = $conn->prepare("INSERT INTO shipping_details (order_id, name, email, address, city, state, zip) VALUES (:order_id, :name, :email, :address, :city, :state, :zip)");
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':name' => $_POST['name'],
                    ':email' => $_POST['email'],
                    ':address' => $_POST['address'],
                    ':city' => $_POST['city'],
                    ':state' => $_POST['state'],
                    ':zip' => $_POST['zip']
                ]);
            }

            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");

            foreach ($cart_items as $item) {
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price']
                ]);
            }

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM cart WHERE user_id = :user_id)");
            $stmt->execute([':user_id' => $user_id]);

            // Commit transaction
            $conn->commit();
    // Show a success message
    echo "<script>
        alert('Order completed successfully!');
        window.location.href = 'history.php';
    </script>";
    exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Order processing failed: " . $e->getMessage();
        }
    }
}


//Fetching and display at the checkout's total
$total_price = 0;
foreach ($cart_items as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Get the cart to display
    function getCartItems($user_id)
{
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT 
            p.product_id AS product_id, 
            p.name, 
            p.price, 
            ci.quantity
        FROM 
            cart_items ci
        JOIN 
            cart c ON ci.cart_id = c.id
        JOIN 
            products p ON ci.product_id = p.product_id
        WHERE 
            c.user_id = :user_id
    ");

    $stmt->execute([':user_id' => $user_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Graceful Gatherings</title>
    <link rel="stylesheet" href="catalog.css">
    <style>
.checkout-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 30px;
    background: linear-gradient(145deg, #ffffff, #f6f7f9);
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.checkout-container:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
}

.checkout-form {
    display: grid;
    gap: 25px;
}

.form-section {
    background: rgba(255, 255, 255, 0.8);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(44, 82, 130, 0.1);
    transition: all 0.3s ease;
}

.form-section:hover {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(44, 82, 130, 0.2);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.form-group {
    display: grid;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #2c5282;
    font-size: 0.95em;
    transition: color 0.3s ease;
}

.form-group input {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 1em;
}

.form-group input:focus {
    border-color: #2c5282;
    box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
    outline: none;
}

.cart-summary {
    margin-bottom: 30px;
    border-bottom: 2px solid rgba(44, 82, 130, 0.1);
    padding-bottom: 25px;
}

.cart-summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    transition: all 0.3s ease;
}

.cart-summary-item:hover {
    transform: translateX(5px);
    color: #2c5282;
}

.total-price {
    font-weight: 700;
    font-size: 1.4em;
    color: #2c5282;
    padding-top: 15px;
    margin-top: 15px;
    border-top: 2px dashed rgba(44, 82, 130, 0.1);
}

.checkout-btn {
    background: linear-gradient(135deg, #2c5282, #1a365d);
    color: white;
    padding: 16px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1em;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(44, 82, 130, 0.3);
}

.checkout-btn:active {
    transform: translateY(1px);
}

.error-message {
    background: linear-gradient(135deg, #fff5f5, #fecaca);
    color: #c53030;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid #c53030;
    font-weight: 500;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.delivery-option {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
}

.delivery-option label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: rgba(44, 82, 130, 0.05);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delivery-option label:hover {
    background: rgba(44, 82, 130, 0.1);
}

.delivery-option input[type="radio"] {
    accent-color: #2c5282;
    transform: scale(1.2);
}

#delivery-details {
    display: none;
    animation: fadeIn 0.3s ease;
}

#delivery-details.visible {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Graceful Gatherings</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="homepage.html" class="nav-item">Home</a>
                <a href="catalog.php" class="nav-item">Catalog</a>
                <a href="cart.php" class="nav-item">Shopping Cart</a>
                <a href="History.php" class="nav-item">Purchase History</a>
                <a href="profile.html" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

           <main class="main-content">
        <div class="checkout-container">
            <h1>Checkout</h1>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-summary-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="cart-summary-item total-price">
                    <strong>Total:</strong>
                    <strong>RM<?php echo number_format($total_price, 2); ?></strong>
                </div>
                </div>

                <form method="POST" class="checkout-form">
                <div class="form-section">
                    <h2>Delivery Method</h2>
                    <div class="delivery-option">
                        <label>
                            <input type="radio" name="delivery_method" value="pickup" onclick="toggleDeliveryDetails()" checked>
                            Pickup (Free) - Pickup Location: J&T Express 
                        </label>
                        <label>
                            <input type="radio" name="delivery_method" value="delivery" onclick="toggleDeliveryDetails()">
                            Delivery (RM 5.00)
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Customer Information</h2>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                  
                <div id="delivery-details" class="form-section" style="display:none;">
                    <h2>Shipping Information</h2>
                    <div class="form-group">
                        <label for="address">Street Address</label>
                        <input type="text" id="address" name="address">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state">
                    </div>
                    <div class="form-group">
                        <label for="zip">Zip Code</label>
                        <input type="text" id="zip" name="zip">
                    </div>
                </div>

                <div id="payment-details" class="form-section" style="display:none;">
                    <h2>Payment Information</h2>
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number">
                    </div>
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv">
                    </div>
                </div>

                <button type="submit" class="checkout-btn">Complete Order</button>
            </form>
        </div>
    </main>

        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>
        <script>
function toggleDeliveryDetails() {
    const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked').value;
    const deliveryDetails = document.getElementById('delivery-details');
    const paymentDetails = document.getElementById('payment-details');

    if (deliveryMethod === 'delivery') {
        deliveryDetails.style.display = 'grid';
    } else {
        deliveryDetails.style.display = 'none';
    }
    
    // Always show payment details for both pickup and delivery
    paymentDetails.style.display = 'block';
}

// Call the function initially to set the correct display state
toggleDeliveryDetails();
    </script>
</body>
</html>