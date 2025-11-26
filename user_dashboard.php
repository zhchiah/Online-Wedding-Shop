<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$user_query = "SELECT username, email FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch unique product categories from products
$category_query = "SELECT DISTINCT category FROM products";
$category_result = $conn->query($category_query);

// Handle category filtering
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Product query with filtering
$product_query = "
    SELECT 
        p.*,
        v.business_name,
        COUNT(DISTINCT r.review_id) as review_count,
        COALESCE(AVG(r.rating), 0) as average_rating
    FROM 
        products p
    LEFT JOIN 
        vendors v ON p.vendor_id = v.vendor_id
    LEFT JOIN 
        reviews r ON p.product_id = r.product_id
    WHERE 
        p.category LIKE ?
    GROUP BY 
        p.product_id";

$stmt = $conn->prepare($product_query);
$filter_category = $selected_category ? $selected_category : '%';
$stmt->bind_param("s", $filter_category);
$stmt->execute();
$products_result = $stmt->get_result();


// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $vendor_id = $_POST['vendor_id'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];

    $review_query = "
        INSERT INTO reviews (user_id, product_id, vendor_id, rating, review_text) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $review_stmt = $conn->prepare($review_query);
    $review_stmt->bind_param("iiiss", $user_id, $product_id, $vendor_id, $rating, $review_text);
    $review_stmt->execute();
    $success_message = "Review submitted successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background-color: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        select, button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: white;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .product-price {
            font-size: 24px;
            color: #e74c3c;
            margin: 10px 0;
        }

        .product-stats {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .stat {
            flex: 1;
            text-align: center;
        }

        .stat-value {
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .review-form {
            margin-top: 15px;
        }

        .review-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
            resize: vertical;
        }

        .rating-select {
            margin: 10px 0;
        }

        .view-details {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
        }

        .view-details:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="appointments_display.php">Appointments</a>
        <a href="cart.php">Cart</a>

        <div class="logout">
            <a href="logout.php" style="color: white;">Logout</a>
        </div>
    
    <div class="header">
        <div class="container">
            <div class="user-info">
                <div class="user-details">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
                        <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                </div>
                <a href="logout.php" style="text-decoration: none;">
                    <button>Logout</button>
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="filters">
<div>
    <label for="category">Category:</label>
    <select name="category" id="category" onchange="updateFilters()">
        <option value="">All Categories</option>
        <?php while ($category = $category_result->fetch_assoc()): ?>
            <option value="<?php echo $category['category']; ?>" 
                <?php echo $selected_category === $category['category'] ? 'selected' : ''; ?>>
                <?php echo ucfirst($category['category']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

        </div>

        <div class="product-grid">
            <?php while ($product = $products_result->fetch_assoc()): ?>
                <div class="product-card">
                    <img class="product-image" 
                         src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'default.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <div class="product-content">
                        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">RM<?php echo number_format($product['price'], 2); ?></div>
                        
                        <div class="product-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($product['total_reviews']); ?></div>
                                <div class="stat-label">Views</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?php echo number_format($product['review_count']); ?></div>
                                <div class="stat-label">Reviews</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value">
                                    <?php echo $product['average_rating'] > 0 ? number_format($product['average_rating'], 1) : 'N/A'; ?>
                                </div>
                                <div class="stat-label">Rating</div>
                            </div>
                        </div>

                        <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                        <p><strong>Vendor:</strong> <?php echo htmlspecialchars($product['business_name']); ?></p>
                        <p><strong>Stock:</strong> <?php echo $product['stock_quantity']; ?> units</p>

                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="view-details">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

<script>
    function updateFilters() {
        const category = document.getElementById('category').value;
        window.location.href = `user_dashboard.php?category=${category}`;
    }
</script>

</body>
</html>
