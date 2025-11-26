<?php

require_once 'catalogdb.php';

// Get cart items for current user
function getCartItems($user_id)
{
    $conn = getDBConnection();
    try {
        $sql = "SELECT ci.*, p.name, p.price, p.image_url 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.product_id 
                JOIN cart c ON ci.cart_id = c.id 
                WHERE c.user_id = :user_id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $cart_item_id = $_POST['cart_item_id'];
    $new_quantity = $_POST['quantity'];

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
        $stmt->execute([
            ':quantity' => $new_quantity,
            ':id' => $cart_item_id
        ]);
        header('Location: cart.php');
        exit();
    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $cart_item_id = $_POST['cart_item_id'];

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = :id");
        $stmt->execute([':id' => $cart_item_id]);
        header('Location: cart.php');
        exit();
    } catch (PDOException $e) {
        die("Removal failed: " . $e->getMessage());
    }
}

$user_id = $_SESSION['user_id'];

$cart_items = getCartItems($user_id);
$total_price = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Graceful Gatherings</title>
    <link rel="stylesheet" href="catalog.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px;
        }

        .cart-section {
            background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08); 
            transition: transform 0.3s ease;
        }

        .cart-section:hover {
            transform: translateY(-2px);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 2fr 1fr 1fr 100px;
            gap: 24px;
            align-items: center;
            padding: 20px;
            margin: 15px 0;
            background: white;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid #eef2f7;
        }

        .cart-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transform: translateX(5px);
        }

        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .cart-item img:hover {
            transform: scale(1.05);
        }

        .cart-item h3 {
            color: #2c5282;
            margin: 0 0 8px 0;
            font-size: 1.2em;
        }

        .cart-item p {
            color: #4a5568;
            margin: 0;
            font-size: 1.1em;
        }

        .quantity-input {
            width: 60px;
            padding: 6px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            text-align: center;
        }

        .quantity-input:focus {
            border-color: #2c5282;
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }

        .update-btn, .remove-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .update-btn {
            background-color: #2c5282;
            color: white;
        }

        .update-btn:hover {
            background-color: #2a4365;
            transform: translateY(-2px);
        }

        .remove-btn {
            background-color: #fff;
            color: #e53e3e;
            border: 2px solid #e53e3e;
        }

        .remove-btn:hover {
            background-color: #e53e3e;
            color: white;
            transform: translateY(-2px);
        }

        .cart-total {
            text-align: right;
            font-size: 1.4em;
            font-weight: 600;
            margin-top: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
            color: #2c5282;
        }

        .section-title {
            font-size: 1.8em;
            color: #2c5282;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: #2c5282;
            border-radius: 2px;
        }

        .checkout-btn {
            background: linear-gradient(135deg, #2c5282, #4299e1);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            float: right;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(44, 82, 130, 0.3);
        }



        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .cart-item img {
                margin: 0 auto;
            }

            .section-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }
    </style>
</head>
<link rel="stylesheet" href="cart.css" />
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Graceful Gatherings</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="homepage.php" class="nav-item">Home</a>
                <a href="catalog.php" class="nav-item ">Catalog</a>
                <a href="cart.php" class="nav-item active">Shopping Cart</a>
                <a href="History.php" class="nav-item">Purchase History</a>
                <a href="bookinghistory.php" class="nav-item">Appointment</a>
                <a href="userprofile.php" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="cart-container">
                <!-- Shopping Cart Section -->
                <div class="cart-section">
                    <h2 class="section-title">Shopping Cart</h2>
                    <?php if (empty($cart_items)): ?>
                        <p>Your cart is empty.</p>
                    <?php else: ?>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div>
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p>RM<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <form method="POST" style="display: inline">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" class="quantity-input">
                                    <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                </form>
                                <div>
                                    RM<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    <?php $total_price += $item['price'] * $item['quantity']; ?>
                                </div>
                                <form method="POST" style="display: inline">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <div class="cart-total">
                            Total: RM<?php echo number_format($total_price, 2); ?>
                        </div>
                        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                            Proceed to Checkout
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>
</body>
</html>