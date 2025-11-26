<?php
require_once 'catalogdb.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Modified function to get orders
function getUserTransactionHistory($user_id)
{
    $conn = getDBConnection();
    $query = "
        SELECT 
            'order' as type,
            o.id AS transaction_id,
            o.total_amount,
            o.order_date AS transaction_date,
            o.status,
            oi.product_id,
            p.name AS item_name,
            p.image_url,
            oi.quantity,
            oi.price AS item_price,
            r.rating AS previous_rating,
            r.review_text AS previous_review
        FROM 
            orders o
        JOIN 
            order_items oi ON o.id = oi.order_id
        JOIN 
            products p ON oi.product_id = p.product_id
        LEFT JOIN 
            reviews r ON r.user_id = o.user_id AND r.product_id = oi.product_id
        WHERE 
            o.user_id = :user_id

        UNION ALL

        SELECT 
            'appointment' as type,
            a.appointment_id AS transaction_id,
            bp.amount AS total_amount,
            a.appointment_date AS transaction_date,
            a.status,
            a.product_id,
            p.name AS item_name,
            p.image_url,
            1 AS quantity,
            p.price AS item_price,
            r.rating AS previous_rating,
            r.review_text AS previous_review
        FROM 
            appointments a
        JOIN 
            booking_payments bp ON a.appointment_id = bp.appointment_id
        JOIN 
            products p ON a.product_id = p.product_id
        LEFT JOIN 
            reviews r ON r.user_id = a.user_id AND r.product_id = a.product_id
        WHERE 
            a.user_id = :user_id AND a.status = 'paid'
        ORDER BY 
            transaction_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get all transactions
$transactions = getUserTransactionHistory($user_id);

// Group transactions by ID
$grouped_transactions = [];
foreach ($transactions as $transaction) {
    $transaction_id = $transaction['transaction_id'];
    $type = $transaction['type'];

    if (!isset($grouped_transactions[$type . '_' . $transaction_id])) {
        $grouped_transactions[$type . '_' . $transaction_id] = [
            'type' => $type,
            'total_amount' => $transaction['total_amount'],
            'transaction_date' => $transaction['transaction_date'],
            'status' => $transaction['status'],
            'items' => []
        ];
    }

    $grouped_transactions[$type . '_' . $transaction_id]['items'][] = [
        'item_name' => $transaction['item_name'],
        'quantity' => $transaction['quantity'],
        'item_price' => $transaction['item_price'],
        'product_id' => $transaction['product_id'],
        'image_url' => $transaction['image_url'],
        'previous_rating' => $transaction['previous_rating'],
        'previous_review' => $transaction['previous_review']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Graceful Gatherings</title>
    <link rel="stylesheet" href="catalog.css">
    <style>
.transaction-history {
    max-width: 800px;
    margin: 20px auto;
    padding: 30px;
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
}

.transaction-card {
    background: white;
    margin-bottom: 25px;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.transaction-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: rgba(0, 0, 0, 0.08);
}

.transaction-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: #4CAF50;
    opacity: 0.8;
}

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.transaction-items {
    margin-top: 15px;
}

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 15px;
            background: rgba(248, 249, 250, 0.5);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .transaction-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .transaction-details {
            flex-grow: 1;
            margin-right: 20px;
        }

        .transaction-item-content {
            display: flex;
            align-items: flex-start;
            width: 100%;
        }

.transaction-item:hover {
    background: rgba(248, 249, 250, 0.9);
    transform: translateX(5px);
}

.transaction-details {
    flex-grow: 1;
    margin-right: 20px;
}

.transaction-type {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
    margin-right: 12px;
    transition: all 0.3s ease;
}

.type-order {
    background-color: rgba(25, 118, 210, 0.1);
    color: #1976d2;
}

.type-order:hover {
    background-color: rgba(25, 118, 210, 0.15);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
    transition: all 0.3s ease;
}

.status-pending {
    background-color: rgba(230, 81, 0, 0.1);
    color: #e65100;
}

.status-completed {
    background-color: rgba(46, 125, 50, 0.1);
    color: #2e7d32;
}

.status-cancelled {
    background-color: rgba(198, 40, 40, 0.1);
    color: #c62828;
}

.status-paid {
    background-color: rgba(46, 125, 50, 0.1);
    color: #2e7d32;
}

.rating-section {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed rgba(0, 0, 0, 0.08);
}

.star-rating {
    display: inline-flex;
    flex-direction: row-reverse;
    margin-bottom: 15px;
    gap: 4px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 28px;
    color: #ddd;
    transition: all 0.3s ease;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffd700;
    transform: scale(1.1);
}

.star {
    color: #ddd;
    font-size: 22px;
    transition: all 0.3s ease;
}

.star.filled {
    color: #ffd700;
    transform: scale(1.05);
}

.rating-form textarea {
    width: 100%;
    padding: 12px;
    margin: 12px 0;
    border: 2px solid rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    resize: vertical;
    transition: all 0.3s ease;
    font-family: inherit;
}

.rating-form textarea:focus {
    border-color: #4CAF50;
    outline: none;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.submit-rating {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.submit-rating:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.submit-rating:active {
    transform: translateY(1px);
}

.existing-rating {
    background: linear-gradient(145deg, #f8f9fa, #ffffff);
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.transaction-card {
    animation: fadeIn 0.3s ease;
}

    </style>
</head>
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
                <a href="cart.php" class="nav-item ">Shopping Cart</a>
                <a href="History.php" class="nav-item active">Purchase History</a>
                <a href="bookinghistory.php" class="nav-item">Appointment</a>
                <a href="userprofile.php" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="transaction-history">
                <h1>Transaction History</h1>
                
                <?php if (empty($grouped_transactions)): ?>
                    <p>You have no transaction history.</p>
                <?php else: ?>
                    <?php foreach ($grouped_transactions as $transaction_id => $transaction): ?>
                        <div class="transaction-card">
                            <div class="transaction-header">
                                <div>
                                    <span class="transaction-type type-order">
                                        Order
                                    </span>
                                    <strong>#<?php echo explode('_', $transaction_id)[1]; ?></strong>
                                    <p><?php echo date('F j, Y, g:i a', strtotime($transaction['transaction_date'])); ?></p>
                                </div>
                                <div>
                                    <p>Total: RM<?php echo number_format($transaction['total_amount'], 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="transaction-items">
                                <?php foreach ($transaction['items'] as $item): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-item-content">
                                            <div class="transaction-details">

                    <!-- Product Image -->
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                         class="transaction-item-image">
                                                <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>

                                                <p>
                                                    Quantity: <?php echo $item['quantity']; ?> x 
                                                    RM<?php echo number_format($item['item_price'], 2); ?>
                                                </p>
                                                
                                                <!-- Rating System -->
                                                <div class="rating-section">
                                                    <?php if ($item['previous_rating']): ?>
                                                        <div class="existing-rating">
                                                            <p>Your Rating: 
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <span class="star <?php echo $i <= $item['previous_rating'] ? 'filled' : ''; ?>">★</span>
                                                                <?php endfor; ?>
                                                            </p>
                                                            <p>Your Review: <?php echo htmlspecialchars($item['previous_review']); ?></p>
                                                        </div>
                                                    <?php else: ?>
                                                        <form class="rating-form" action="product_ratings.php" method="POST">
                                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                            <div class="star-rating">
                                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                    <input type="radio" id="star<?php echo $i; ?>_<?php echo $item['product_id']; ?>" 
                                                                           name="rating" value="<?php echo $i; ?>">
                                                                    <label for="star<?php echo $i; ?>_<?php echo $item['product_id']; ?>">★</label>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <textarea name="review" placeholder="Write your review here..." rows="3"></textarea>
                                                            <button type="submit" class="product_ratings.php">Submit Review</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>

        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>
</body>
</html>



