<?php
require_once 'catalogdb.php';

// Get product ID from URL and validate
$product_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($product_id <= 0) {
    header('Location: catalog.php');
    exit();
}

// Fetch product details and increment views
$product = getProductDetailsAndIncrementViews($product_id);
if (!$product) {
    header('Location: catalog.php');
    exit();
}



// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    $quantity = isset($_POST['quantity']) ?
        filter_var($_POST['quantity'], FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1, "max_range" => 100]
        ]) : 1;

    $user_id = $_SESSION['user_id'];

    if ($quantity && addToCart($product_id, $user_id, $quantity)) {
        $success_message = "Product added to cart successfully!";
    } else {
        $error_message = "Failed to add product to cart. Please try again.";
    }
}


// Handle the booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $vendor_id = filter_var($_POST['vendor_id'], FILTER_VALIDATE_INT); // Add vendor_id from the form
    $appointment_date = filter_var($_POST['appointment_date'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_NUMBER_INT);
    $notes = filter_var($_POST['notes'], FILTER_SANITIZE_STRING);

    // Format the appointment date to match DATETIME format
    $appointment_datetime = date('Y-m-d H:i:s', strtotime($appointment_date));

    // Validate required fields
    if (!$product_id || !$appointment_date || !$phone || !$vendor_id) {
        $_SESSION['error_message'] = "Invalid booking data provided.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare("INSERT INTO appointments (
            vendor_id, 
            user_id, 
            appointment_date, 
            status,
            notes, 
            product_id, 
            phone
        ) VALUES (
            :vendor_id,
            :user_id,
            :appointment_date,
            'scheduled',
            :notes,
            :product_id,
            :phone
        )");

        $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':user_id' => $user_id,
            ':appointment_date' => $appointment_datetime,
            ':notes' => $notes,
            ':product_id' => $product_id,
            ':phone' => $phone
        ]);

        $_SESSION['success_message'] = "Appointment scheduled successfully! We will confirm your booking shortly.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to schedule appointment. Please try again.";
        error_log("Booking error: " . $e->getMessage());
    }

    header('Location:' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Get current view count for display
$view_count = getProductViewCount($product_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Graceful Gatherings</title>
    <link rel="stylesheet" href="catalog.css">
    <style>
.product-details-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 0.8fr 1.2fr;
    gap: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.product-image-section {
    text-align: center;
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
}

.product-image-section img {
    max-width: 90%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.product-info-section {
    padding: 20px;
}

.product-title {
    font-size: 2em;
    margin-bottom: 10px;
    color: #2d3748;
}

.product-category {
    color: #666;
    font-size: 1.1em;
    margin-bottom: 15px;
    padding: 5px 12px;
    background: #f7fafc;
    border-radius: 15px;
    display: inline-block;
}

.product-price {
    font-size: 1.8em;
    color: #2c5282;
    margin-bottom: 20px;
    font-weight: bold;
}

.product-description {
    color: #444;
    line-height: 1.6;
    margin-bottom: 30px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid #2c5282;
}

        .add-to-cart-form {
            margin-top: 20px;
        }

        .quantity-input {
            padding: 8px;
            width: 80px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .add-to-cart-btn {
            background-color: #2c5282;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
        }

        .add-to-cart-btn:hover {
            background-color: #2a4365;
        }

        .success-message {
            background-color: #c6f6d5;
            color: #2f855a;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-to-catalog {
            display: inline-block;
            margin-bottom: 20px;
            color: #2c5282;
            text-decoration: none;
        }

        .back-to-catalog:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .product-details-container {
                grid-template-columns: 1fr;
            }
        }
/* Modal Styles Enhancement */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    position: relative;
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    width: 90%;
    max-width: 500px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { transform: translateY(-30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.close-button {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.close-button:hover {
    background-color: #f0f0f0;
    color: #333;
}

.booking-form {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.form-group {
    display: grid;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 0.95em;
}

.form-group input,
.form-group textarea,
.form-group select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #2c5282;
    box-shadow: 0 0 0 2px rgba(44, 82, 130, 0.1);
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.quantity-input {
    padding: 8px 12px;
    width: 60px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-right: 10px;
    font-size: 14px;
    color: #2d3748;
    background: #f8fafc;
    transition: border-color 0.3s ease;
}

.quantity-input:focus {
    outline: none;
    border-color: #4a5568;
    box-shadow: 0 0 0 2px rgba(74, 85, 104, 0.1);
}

.add-to-cart-btn, 
.booking-btn {
    padding: 8px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #2d3748;
}

.add-to-cart-btn {
    background-color: #2d3748;
    color: white;
}

.add-to-cart-btn:hover {
    background-color: #1a202c;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.booking-btn {
    background-color: white;
    color: #2d3748;
}

.booking-btn:hover {
    background-color: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    align-items: center;
}

/* Added hover effect for both buttons */
.add-to-cart-btn:active,
.booking-btn:active {
    transform: translateY(0);
    box-shadow: none;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: row;
        gap: 8px;
    }
    
    .quantity-input {
        width: 50px;
    }
    
    .add-to-cart-btn, 
    .booking-btn {
        padding: 8px 15px;
        font-size: 13px;
    }
}

.view-count {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #f7fafc;
    border-radius: 15px;
    width: fit-content;
}

.vendor-info {
    margin-bottom: 15px;
    color: #666;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
}

/* Notification Enhancement */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    display: none;
}

.notification.show {
    display: block;
    animation: slideInRight 0.5s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .product-details-container {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 20px;
    }
}
    </style>
</head>
<body>
    <div id="notification" class="notification"></div>
   <script>
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;

            setTimeout(() => {
                notification.className = 'notification';
            }, 4000);
        }

        // Display PHP messages as notifications
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['success_message'])): ?>
                showNotification("<?php echo addslashes($_SESSION['success_message']); ?>", "success");
                <?php unset($_SESSION['success_message']); ?>
            <?php elseif (isset($_SESSION['error_message'])): ?>
                showNotification("<?php echo addslashes($_SESSION['error_message']); ?>", "error");
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        });
    </script>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Graceful Gatherings</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="homepage.html" class="nav-item">Home</a>
                <a href="catalog.html" class="nav-item active">Catalog</a>
                <a href="cart.html" class="nav-item">Shopping Cart</a>
                <a href="History.html" class="nav-item">Purchase History</a>
                <a href="appointment.html" class="nav-item">Make an Appointment</a>
                <a href="profile.html" class="nav-item">My Profile</a>
                <a href="login.html" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <a href="catalog.php" class="back-to-catalog">← Back to Catalog</a>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="product-details-container">
                <div class="product-image-section">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>

                <div class="product-info-section">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <?php if (isset($product['business_name'])): ?>
                    <div class="vendor-info">
                        Vendor: <?php echo htmlspecialchars($product['business_name']); ?>
                        <?php if (isset($product['service_category'])): ?>
                            (<?php echo htmlspecialchars($product['service_category']); ?>)
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="product-category">
                        Category: <?php echo htmlspecialchars($product['category']); ?>
                    </div>
                    
                    <div class="view-count">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        <?php echo number_format($view_count); ?> views
                    </div>
                    <br>
                    <div class="product-price">
                        RM<?php echo number_format($product['price'], 2); ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" class="add-to-cart-form">
                            <input type="number" name="quantity" value="1" min="1" 
                                   class="quantity-input" required>
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                Add to Cart
                            </button>
                       
<button type="button" class="booking-btn" onclick="openBookingModal()">Book it Now!</button>
                        </form>

</div>

<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeBookingModal()">&times;</span>
        <h2>Book Appointment</h2>
        
       <form class="booking-form" method="POST">
    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
    <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($product['vendor_id']); ?>">
    
    <div class="form-group">
        <label for="appointment_date">Appointment Date and Time:</label>
        <input type="datetime-local" id="appointment_date" name="appointment_date" required>
    </div>
    
    <div class="form-group">
        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" required>
    </div>
    
    <div class="form-group">
        <label for="notes">Notes:</label>
        <textarea id="notes" name="notes" rows="4"></textarea>
    </div>
    
    <button type="submit" class="booking-btn">Schedule Appointment</button>
</form>
     </div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>

function openBookingModal() {
    document.getElementById('bookingModal').style.display = 'block';
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Add minimum date restriction to date picker
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('booking_date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
});
</script>

        </main>

        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>

   
</body>


</html>