<?php
require_once 'catalogdb.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Graceful Gatherings</title>

</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Graceful Gatherings</h2>
            </div>
            <nav class="sidebar-nav">

                <a href="homepage.php" class="nav-item active">Home</a>
                <a href="catalog.php" class="nav-item">Catalog</a>
                <a href="cart.php" class="nav-item">Shopping Cart</a>
                <a href="history.php" class="nav-item">Purchase History</a>
                <a href="bookinghistory.php" class="nav-item">Appointments</a>
                <a href="userprofile.php" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="tab-content active">
        <main class="main-content">
            <div class="tab-content active">
                <h2>Homepage</h2>
            </div>
            <div class="content">
                <div class="hero-section">
                    <img src="https://assets.vogue.com/photos/6457fe35c943a2672e3e6c65/master/w_1600%2Cc_limit/vg-125.jpg" alt="Wedding Bouquet" />
                    <div class="hero-content">
                        <h1 class="hero-title">The Gift of Life</h1>
                        <p class="hero-text">
                            Love doesn't make the world go around...Love is what makes the world worthwhile!
                        </p>
                        <a href="catalog.php"> <button class="btn-primary"> 
                            Click to View More
                        </button>
                        </a>
                    </div>
                </div>

                <!-- Top Rated Products Section -->

<div class="top-rated-section">
    <h2>Most Viewed Products</h2>
    <div class="top-rated-grid">
                <?php
        $mostViewedProducts = getMostViewedProducts(3);
        if (!empty($mostViewedProducts)) {
            foreach ($mostViewedProducts as $product):
                $rating = number_format($product['average_rating'], 1);
                $reviews = $product['review_count'];
                $views = $product['total_reviews'];
                ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? '/api/placeholder/200/200'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image"
                         onerror="this.src='/api/placeholder/200/200'">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="views-info">
                        <span class="views-text"><?php echo number_format($views); ?> views</span>
                    </div>
                    <div class="rating-info">
                        <div class="stars">
                            <?php
                            // Full stars
                            for ($i = 1; $i <= floor($rating); $i++) {
                                echo '<span class="star">★</span>';
                            }
                            // Half star
                            if ($rating - floor($rating) >= 0.5) {
                                echo '<span class="star">★</span>';
                            }
                            // Empty stars
                            for ($i = ceil($rating); $i < 5; $i++) {
                                echo '<span class="star empty">☆</span>';
                            }
                            ?>
                        </div>
                        <span class="rating-text"><?php echo $rating; ?> (<?php echo $reviews; ?> reviews)</span>
                    </div>
                    <p class="product-price">RM<?php echo number_format($product['price'], 2); ?></p>
                    <a href="product.php?id=<?php echo $product['product_id']; ?>" class="product-button">
                        View Details
                    </a>
                </div>
                <?php
            endforeach;
        } else {
            ?>
            <div class="no-products">
                <p>No products available yet.</p>
            </div>
            <?php
        }
        ?>
    </div>
</div>

                <!-- About Us Section -->
                <div class="main">
                    <h2>About Us</h2>
                    <div class="context">
                        <p>
                            At Graceful Gathering, we believe every wedding deserves elegance and heart. 
                            We're here to make your celebration special, with a curated collection of bridal accessories, decor, and 
                            essentials designed to reflect your unique love story. Our handpicked items combine quality with charm, 
                            making it easy for you to plan a day you'll cherish forever.
                            Thank you for letting us be part of your journey. Here's to love, beauty, and unforgettable moments.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>
    <script>
        function viewProduct(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
    </script>
</body>
</html>


    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }
.tab-content h2 {
    text-align: center;
    margin: 0rem 0;
    font-size: 2.5rem;
    color: #333;
    position: relative;
    padding-bottom: 15px;
}

.tab-content h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: hotpink;
}

/* Ensure the containing element is also properly centered */
.tab-content {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 0;
}
        .container {
            min-height: 100vh;
            display: flex;
            position: relative;
        }

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

        .main-content {
            flex: 1;
            padding: 30px;
            padding-bottom: 80px;
            margin-left: 0;
            overflow-y: auto;
            max-height: 100vh;
            transition: margin-left 0.3s ease-in-out;
        }

        .hero-section {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 80vh;
            overflow: hidden;
            border-radius: 15px;
            margin-bottom: 30px;
            position: relative;
        }

        .hero-section img {
            width: 80%;
            height: 80%;
            object-fit: cover;
            border-radius: 15px;
            display: block;
            margin: 0 auto;
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 2rem;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-primary {
            background-color: hotpink;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary:hover {
            background-color: #ff429a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Top Rated Section */
.top-rated-section {
    padding: 3rem;
    margin: 3rem 0;
    background: linear-gradient(135deg, #fff6f6 0%, antiquewhite 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.top-rated-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, hotpink, #ff429a);
}

.top-rated-section h2 {
    text-align: center;
    margin-bottom: 2.5rem;
    font-size: 2.5rem;
    color: #333;
    position: relative;
    padding-bottom: 15px;
}

.top-rated-section h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: hotpink;
}

.top-rated-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
    padding: 1.5rem;
    perspective: 1000px;
}
        /* Featured Products Section */
        .featured-products {
            background-color: antiquewhite;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            position: relative;
        }

        .product-carousel {
            display: flex;
            overflow-x: hidden;
            scroll-behavior: smooth;
            padding: 20px 0;
            gap: 20px;
            position: relative;
        }

.product-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transform-style: preserve-3d;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.product-card:hover {
            transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}


.product-image {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}


.product-title {
    font-size: 1.3rem;
    font-weight: bold;
    margin: 15px 0;
    color: #333;
    text-align: center;
    transition: color 0.3s ease;
}

.product-card:hover .product-title {
    color: hotpink;
}
.views-info {
    background: rgba(255, 192, 203, 0.1);
    padding: 8px 15px;
    border-radius: 20px;
    margin: 10px 0;
    text-align: center;
}

.views-text {
    color: #666;
    font-size: 0.9rem;
}
.rating-info {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 15px 0;
    gap: 10px;
}

.product-price {
    font-size: 1.4rem;
    font-weight: bold;
    color: hotpink;
    margin: 15px 0;
    text-align: center;
    transition: transform 0.3s ease;
}

.product-card:hover .product-price {
    transform: scale(1.1);
}

.product-button {
    width: 100%;
    background: linear-gradient(45deg, hotpink, #ff429a);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    display: block;
}

.product-button:hover {
    background: linear-gradient(45deg, #ff429a, hotpink);
    box-shadow: 0 5px 15px rgba(255, 105, 180, 0.4);
    transform: translateY(-2px);
}
        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .carousel-button.prev {
            left: 10px;
        }

        .carousel-button.next {
            right: 10px;
        }

.stars {
    display: inline-flex;
    gap: 2px;
}

.star {
    font-size: 1.3rem;
    transition: transform 0.2s ease;
}
.product-card:hover .star {
    transform: scale(1.2);
    color: #ffd700;
}

        .star.empty {
            color: #e0e0e0;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
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
            border-top: 1px solid gray;
            z-index: 997;
            transition: margin-left 0.3s ease-in-out;
        }

        .footer p {
            margin: 5px 0;
        }

.main {
    padding: 3rem;
    margin: 3rem 0;
    background: linear-gradient(135deg, #fff6f6 0%, antiquewhite 100%);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.main h2 {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2.5rem;
    color: #333;
    position: relative;
    padding-bottom: 15px;
}

.main h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: hotpink;
}

.context {
    position: relative;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.context:hover {
    transform: translateY(-5px);
}

.context p {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #444;
    text-align: center;
    position: relative;
    padding: 20px;
}

.context p::before,
.context p::after {
    content: '"';
    font-size: 4rem;
    color: hotpink;
    opacity: 0.2;
    position: absolute;
}

.context p::before {
    top: -20px;
    left: 0;
}

.context p::after {
    bottom: -40px;
    right: 0;
}

@media (max-width: 768px) {
    .top-rated-section,
    .main {
        padding: 2rem 1rem;
    }

    .top-rated-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .product-card {
        max-width: 400px;
        margin: 0 auto;
    }

    .main h2,
    .top-rated-section h2 {
        font-size: 2rem;
    }
}
    </style>