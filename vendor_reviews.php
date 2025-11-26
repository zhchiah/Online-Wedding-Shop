<?php
include 'catalogdb.php';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: login.php');
    exit();
}

try {
    $conn = getDBConnection();
    $vendor_id = null;
    
    // Get vendor_id for the logged-in user
    $vendor_query = "SELECT vendor_id FROM vendors WHERE user_id = :user_id";
    $vendor_stmt = $conn->prepare($vendor_query);
    $vendor_stmt->execute([':user_id' => $_SESSION['user_id']]);
    $vendor_result = $vendor_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vendor_result) {
        $vendor_id = $vendor_result['vendor_id'];
    } else {
        throw new Exception('Vendor profile not found');
    }
    
    // Get all products for this vendor with their reviews
    $products_query = "
        SELECT 
            p.product_id,
            p.name AS product_name,
            p.description,
            p.category,
            p.price,
            p.image_url,
            p.rating AS product_rating,
            p.total_reviews,
            r.review_id,
            r.rating AS review_rating,
            r.review_text,
            r.review_date,
            u.username
        FROM products p
        LEFT JOIN reviews r ON p.product_id = r.product_id
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE p.vendor_id = :vendor_id
        ORDER BY p.product_id, r.review_date DESC";
    
    $products_stmt = $conn->prepare($products_query);
    $products_stmt->execute([':vendor_id' => $vendor_id]);
    $results = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group reviews by product
    $products = [];
    foreach ($results as $row) {
        $product_id = $row['product_id'];
        
        // Initialize product if not exists
        if (!isset($products[$product_id])) {
            $products[$product_id] = [
                'name' => $row['product_name'],
                'description' => $row['description'],
                'category' => $row['category'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'rating' => $row['product_rating'],
                'total_reviews' => $row['total_reviews'],
                'reviews' => []
            ];
        }
        
        // Add review if exists
        if ($row['review_id']) {
            $products[$product_id]['reviews'][] = [
                'rating' => $row['review_rating'],
                'text' => $row['review_text'],
                'date' => $row['review_date'],
                'username' => $row['username']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving reviews.";
    header('Location: vendor_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews - Vendor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

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
                    <a href="appointments.php" class="nav-item ">Appointments</a>
                    <a href="products.php" class="nav-item ">Add Product</a>
                    <a href="product_list.php" class="nav-item">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item active ">Review</a>

                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Log Out</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <h2 style="text-align: center;">Product Reviews</h2>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                No products or reviews found.
            </div>
        <?php else: ?>
            <?php foreach ($products as $product_id => $product): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="text-muted">
                                    Category: <?php echo htmlspecialchars($product['category']); ?> | 
                                    Price: RM<?php echo number_format($product['price'], 2); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="star-rating">
                                    <?php
                                    $rating = $product['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="bi bi-star-fill"></i>';
                                        } else {
                                            echo '<i class="bi bi-star"></i>';
                                        }
                                    }
                                    ?>
                                    <span class="ms-2">(<?php echo $product['total_reviews']; ?> reviews)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="Product Image" 
                                 class="img-fluid mb-3" 
                                 style="max-height: 150px; object-fit: cover;">
                        <?php endif; ?>

                        <?php if (empty($product['reviews'])): ?>
                            <div class="alert alert-info">
                                No reviews yet for this product.
                            </div>
                        <?php else: ?>
                            <h4 class="mt-3">Reviews</h4>
                            <?php foreach ($product['reviews'] as $review): ?>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="star-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '<i class="bi bi-star-fill"></i>';
                                                    } else {
                                                        echo '<i class="bi bi-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($review['date'])); ?>
                                            </small>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars($review['text']); ?></p>
                                        <small class="text-muted">
                                            By: <?php echo htmlspecialchars($review['username']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        }
        .star-rating {
            color: #ffd700;
        }
        .review-card {
            transition: transform 0.2s;
        }
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(45deg, #4a90e2, #67b26f);
            color: white;
        }
    </style>