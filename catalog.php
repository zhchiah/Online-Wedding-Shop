<?php
require_once 'catalogdb.php';

// Get filter parameters from URL
$filters = array(
    'category' => $_GET['category'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'search' => $_GET['search'] ?? null,
    'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0
);

// Get products and total count
$products = getProducts($filters);
$total_products = getTotalProducts($filters);

// Get all categories for the filter sidebar
$categories = getCategories();

if (isset($_GET['ajax'])) {
    foreach ($products as $product): ?>
        <div class="product-card">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                 class="product-image">
            <div class="product-details">
                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-price">RM<?php echo number_format($product['price'], 2); ?></p>
                <a href="product.php?id=<?php echo $product['product_id']; ?>" 
                   class="view-details-btn">View Details</a>
            </div>
        </div>
    <?php endforeach;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog - Graceful Gatherings</title>
    <link rel="stylesheet" href="catalog.css">
<style >
.catalog-header {
  background-color: #f8f8f8;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  animation: headerFade 0.5s ease;
}

@keyframes headerFade {
  0% {
    opacity: 0;
    transform: translateY(-10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.search-and-sort {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  align-items: center;
}

.search-bar {
  flex: 1;
  display: flex;
  gap: 10px;
}

.search-input {
  padding: 10px;
  border: 2px solid #ffa7d1;
  border-radius: 25px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
  animation: searchFade 0.5s ease;
}

.search-input:focus {
  outline: none;
  border-color: #ff69b4;
}

@keyframes searchFade {
  0% {
    opacity: 0;
    transform: translateX(-10px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

.catalog-container {
  display: flex;
  gap: 20px;
}

.filters {
  width: 220px;
  padding: 15px;
  border-radius: 6px;
  background-color: white;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  animation: filtersFade 0.5s ease;
}

@keyframes filtersFade {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

.filter-section {
  margin-bottom: 20px;
}

.filter-section h3 {
  margin-bottom: 8px;
  color: #ff69b4;
  font-weight: bold;
  animation: sectionTitleFade 0.5s ease;
}

@keyframes sectionTitleFade {
  0% {
    opacity: 0;
    transform: translateX(-10px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

.price-button {
  padding: 8px 12px;
  background-color: white;
  border: 1px solid #ff69b4;
  border-radius: 20px;
  color: #ff69b4;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  text-align: left;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.price-button:hover {
  background-color: #ff69b4;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.price-button.active {
  background-color: #ff69b4;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.product-card {
  width: 180px;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  animation: cardFade 0.5s ease;
}

@keyframes cardFade {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.product-image img {
  max-width: 100%;
  height: auto;
  object-fit: contain;
  margin-bottom: 10px;
  animation: imageZoom 0.5s ease;
}

@keyframes imageZoom {
  0% {
    transform: scale(0.8);
  }
  100% {
    transform: scale(1);
  }
}

.product-title {
  font-size: 14px;
  margin: 8px 0;
  color: #333;
  font-weight: bold;
  animation: titleFade 0.5s ease;
}

@keyframes titleFade {
  0% {
    opacity: 0;
    transform: translateY(5px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-price {
  font-size: 13px;
  color: #666;
  animation: priceFade 0.5s ease;
}

@keyframes priceFade {
  0% {
    opacity: 0;
    transform: translateY(5px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.products-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  justify-content: center;
}

.product-details {
  text-align: center;
}
.view-details-btn {
padding: 12px 20px;
border-radius: 20px;
background-color: #ff69b4;
color: white;
border: none;
cursor: pointer;
transition: background-color 0.3s ease;
font-size: 0.9rem;
font-weight: 500;
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.view-details-btn:hover {
background-color: #ff4081;
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 5px;
  margin-top: 30px;
  margin-bottom: 30px;
  animation: paginationFade 0.5s ease;
}

@keyframes paginationFade {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.page-button {
  min-width: 40px;
  height: 40px;
  border: 1px solid #ff69b4;
  background-color: white;
  color: #ff69b4;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-button:hover,
.page-button.active {
  background-color: #ff69b4;
  color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.page-button:disabled {
  background-color: #f5f5f5;
  border-color: #ddd;
  color: #999;
  cursor: not-allowed;
  box-shadow: none;
}

.pagination-info {
  color: #4a5568;
  text-align: center;
  margin-top: 0.5rem;
  font-size: 0.875rem;
  animation: paginationInfoFade 0.5s ease;
}

@keyframes paginationInfoFade {
  0% {
    opacity: 0;
    transform: translateY(5px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
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
                <a href="catalog.php" class="nav-item active">Catalog</a>
                <a href="cart.php" class="nav-item">Shopping Cart</a>
                <a href="History.php" class="nav-item">Purchase History</a>
                <a href="bookinghistory.php" class="nav-item">Appointment</a>
                <a href="userprofile.php" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="catalog-header">
                <h1>Wedding Essentials Catalog</h1>
                <br>
                <div class="search-and-sort">
                    <div class="search-bar">
                        <form method="GET" action="catalog.php" class="search-bar">
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search for products..."
                                   value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                            <button type="submit" class="search-button">Search</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="catalog-container">
                <div class="filters">
                    <form method="GET" action="catalog.php" id="filter-form">
                        <div class="filter-section">
                            <h3>Categories</h3>
                            <?php foreach ($categories as $category): ?>
                                <div class="filter-option">
                                    <input type="checkbox" name="category" 
                                           value="<?php echo htmlspecialchars($category['category']); ?>" 
                                           <?php echo ($filters['category'] == $category['category']) ? 'checked' : ''; ?>>
                                    <label><?php echo htmlspecialchars($category['category']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="filter-section">
                            <h3>Price Range</h3>
                            <div class="price-range">
                                <input type="number" name="min_price" placeholder="Min Price" 
                                       value="<?php echo htmlspecialchars($filters['min_price'] ?? ''); ?>">
                                <input type="number" name="max_price" placeholder="Max Price"
                                       value="<?php echo htmlspecialchars($filters['max_price'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="apply-filters-btn">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <div class="products-grid">
                    <?php if (empty($products)): ?>
    <div class="no-products">
        <p>No products found matching your criteria.</p>
    </div>
<?php else: ?>
    <?php foreach ($products as $product): ?>
<div class="product-card">
    <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
            <div class="product-details">
                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-price">RM<?php echo number_format($product['price'], 2); ?></p>
                <br><a href="product.php?id=<?php echo $product['product_id']; ?>" 
                   class="view-details-btn">View Details</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
                
                <div class="loading" style="display: none;">Loading more products...</div>
            </div>
        </main>

        <footer class="footer">
            <p>This website is a fictional project created for educational purposes as part of a university course.</p>
            <p>@-Not a Real Business</p>
        </footer>
    </div>

    <script>
        let offset = <?php echo count($products); ?>;
        let loading = false;
        const totalProducts = <?php echo $total_products; ?>;
        
        function isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
        
        async function loadMoreProducts() {
            if (loading || offset >= totalProducts) return;
            
            loading = true;
            const loadingElement = document.querySelector('.loading');
            loadingElement.style.display = 'block';
            
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('offset', offset);
            urlParams.set('ajax', '1');
            
            try {
                const response = await fetch(`catalog.php?${urlParams.toString()}`);
                const html = await response.text();
                
                if (html.trim()) {
                    const productsGrid = document.querySelector('.products-grid');
                    productsGrid.insertAdjacentHTML('beforeend', html);
                    offset += <?php echo count($products); ?>;
                }
            } catch (error) {
                console.error('Error loading more products:', error);
            } finally {
                loading = false;
                loadingElement.style.display = 'none';
            }
        }
        
        window.addEventListener('scroll', function() {
            const loadingElement = document.querySelector('.loading');
            if (isInViewport(loadingElement)) {
                loadMoreProducts();
            }
        });
        
        document.getElementById('filter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = 'catalog.php?' + params.toString();
        });

        function handleImageError(img) {
    // Remove any previous error handlers to prevent loops
    img.onerror = null;
    
    // Try to load the image from the full path if it failed with relative path
    if (!img.src.startsWith(window.location.origin)) {
        img.src = window.location.origin + '/' + img.src;
        return;
    }
    
    // If that fails too, use the placeholder
    img.src = 'images/placeholder.jpg';
}

    </script>
</body>
</html>