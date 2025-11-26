<?php

// database_functions.php
session_start();
// Database connection configuration
function getDBConnection()
{
    $host = 'localhost';
    $dbname = 'wedding_application';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

    
function getProductsByCategory($category) {
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE category = :category");
        $stmt->execute([':category' => $category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

// Function to get all products with filtering

function getProducts($filters = array())
{
    $conn = getDBConnection();

    // Start with base query
    $sql = "SELECT * FROM products WHERE status = 'approved'";
    $params = array();

    // Build the query based on filters
    if (!empty($filters['category'])) {
        $sql .= " AND category = :category";
        $params[':category'] = $filters['category'];
    }

    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= :min_price";
        $params[':min_price'] = floatval($filters['min_price']);
    }

    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= :max_price";
        $params[':max_price'] = floatval($filters['max_price']);
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    // Add ordering
    $sql .= " ORDER BY name ASC";

    // Add LIMIT and OFFSET if offset is provided
    if (isset($filters['offset'])) {
        $sql .= " LIMIT 12 OFFSET :offset"; // Show 12 products per page
        $params[':offset'] = intval($filters['offset']);
    }

    try {
        // Prepare and execute the query
        $stmt = $conn->prepare($sql);

        // Bind all parameters
        foreach ($params as $key => &$value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return array();
    }
}
// Function to get total number of products (for pagination)
function getTotalProducts($filters = array())
{
    $conn = getDBConnection();

    // Start with base query
    $sql = "SELECT COUNT(*) as total FROM products WHERE status = 'approved'";
    $params = array();

    // Add filters to query
    if (!empty($filters['category'])) {
        $sql .= " AND category = :category";
        $params[':category'] = $filters['category'];
    }

    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= :min_price";
        $params[':min_price'] = floatval($filters['min_price']);
    }

    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= :max_price";
        $params[':max_price'] = floatval($filters['max_price']);
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    try {
        $stmt = $conn->prepare($sql);

        // Bind all parameters
        foreach ($params as $key => &$value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];

    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return 0;
    }
}
// Function to get all categories
function getCategories()
{
    $conn = getDBConnection();

    try {
        // Changed to select distinct categories from products table
        $stmt = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

// Function to add product to cart

function addToCart($product_id, $user_id, $quantity = 1)
{
    if (!isset($_SESSION['user_id'])) {
        die("User must be logged in to add items to the cart.");
    }

    $conn = getDBConnection();

    try {
        // Check product stock
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['error_message'] = "Product not found.";
            return false;
        }

        $available_stock = $product['stock_quantity'];

        // Debugging: Check stock and requested quantity
        error_log("Stock quantity for product $product_id: $available_stock");
        error_log("Requested quantity: $quantity");

        if ($available_stock < $quantity) {
            $_SESSION['error_message'] = "The product is out of stock or the requested quantity exceeds available stock.";
            return false;
        }

        // Check if cart exists for user
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            // Create a new cart for the user
            $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (:user_id)");
            $stmt->execute([':user_id' => $user_id]);
            $cart_id = $conn->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if the product already exists in the cart
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
        $stmt->execute([
            ':cart_id' => $cart_id,
            ':product_id' => $product_id
        ]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // Check if the new total quantity exceeds available stock
            $new_quantity = $cart_item['quantity'] + $quantity;
            if ($new_quantity > $available_stock) {
                $_SESSION['error_message'] = "The total requested quantity exceeds available stock.";
                return false;
            }

            // Update the quantity of the product in the cart
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity 
                                  WHERE cart_id = :cart_id AND product_id = :product_id");
            $stmt->execute([
                ':cart_id' => $cart_id,
                ':product_id' => $product_id,
                ':quantity' => $new_quantity
            ]);
        } else {
            // Add the product to the cart
            $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) 
                                  VALUES (:cart_id, :product_id, :quantity)");
            $stmt->execute([
                ':cart_id' => $cart_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity
            ]);
        }

        $_SESSION['success_message'] = "Product added to cart successfully!";
        return true;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to add product to cart: " . $e->getMessage();
        return false;
    }
}



function ProductRating($user_id, $product_id, $rating, $review = null)
{
    $conn = getDBConnection(); // Use the existing connection function

    try {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, review_text) VALUES (:user_id, :product_id, :rating, :review_text)");

        $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id,
            ':rating' => $rating,
            ':review_text' => $review
        ]);

        return true;
    } catch (PDOException $e) {
        die("Rating insertion failed: " . $e->getMessage());
    }
}


function incrementTotalReviews($product_id, $conn)
{
    // Prepare update statement
    $query = "UPDATE products 
              SET total_reviews = total_reviews + 1 
              WHERE product_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);

    return $stmt->execute();
}


// Function to get a single product by ID
function getProductById($product_id)
{
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching product: " . $e->getMessage());
        return null;
    }
}

function getProductDetailsAndIncrementViews($product_id)
{
    $conn = getDBConnection();
    try {
        // Start transaction
        $conn->beginTransaction();

        // Get product details with vendor information
        $query = "SELECT p.*, v.business_name, v.service_category 
                 FROM products p 
                 LEFT JOIN vendors v ON p.vendor_id = v.vendor_id 
                 WHERE p.product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':product_id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update view count
        if ($product) {
            $update = "UPDATE products 
                      SET total_reviews = COALESCE(total_reviews, 0) + 1 
                      WHERE product_id = :product_id";
            $updateStmt = $conn->prepare($update);
            $updateStmt->execute([':product_id' => $product_id]);
        }

        // Commit transaction
        $conn->commit();
        return $product;

    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Error updating product views: " . $e->getMessage());

        // Still try to return product details even if view increment fails
        try {
            $stmt = $conn->prepare("SELECT p.*, v.business_name, v.service_category 
                                  FROM products p 
                                  LEFT JOIN vendors v ON p.vendor_id = v.vendor_id 
                                  WHERE p.product_id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            error_log("Error fetching product details: " . $e2->getMessage());
            return null;
        }
    }
}

function getProductViewCount($product_id)
{
    $conn = getDBConnection();
    try {
        $stmt = $conn->prepare("SELECT total_reviews FROM products WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? intval($result['total_reviews']) : 0;
    } catch (PDOException $e) {
        error_log("Error getting product view count: " . $e->getMessage());
        return 0;
    }
}
function getMostViewedProducts($limit = 5)
{
    $conn = getDBConnection();

    if (!$conn) {
        return [];
    }

    try {
        $sql = "SELECT 
                    p.product_id,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.category,
                    p.stock_quantity,
                    p.total_reviews,
                    COALESCE(AVG(r.rating), 0) as average_rating,
                    COUNT(r.review_id) as review_count
                FROM products p
                LEFT JOIN reviews r ON p.product_id = r.product_id
                WHERE p.status = 'approved'
                GROUP BY 
                    p.product_id,
                    p.name,
                    p.description,
                    p.price,
                    p.image_url,
                    p.category,
                    p.stock_quantity,
                    p.total_reviews
                ORDER BY p.total_reviews DESC, average_rating DESC
                LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $products;
    } catch (PDOException $e) {
        error_log("Error fetching most viewed products: " . $e->getMessage());
        return [];
    } finally {
        $conn = null;
    }
}




// Function to update booking status
function updateBookingStatus($booking_id, $status, $user_id)
{
    $conn = getDBConnection();

    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = :status 
                               WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':status' => $status,
            ':id' => $booking_id,
            ':user_id' => $user_id
        ]);
        return true;
    } catch (PDOException $e) {
        die("Status update failed: " . $e->getMessage());
    }
}