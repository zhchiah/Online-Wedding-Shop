<?php
require_once 'catalogdb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    
    try {
        $conn = getDBConnection();
        
        // First, get the vendor_id from the products table
        $vendor_query = "SELECT vendor_id FROM products WHERE product_id = :product_id";
        $vendor_stmt = $conn->prepare($vendor_query);
        $vendor_stmt->execute([':product_id' => $product_id]);
        $vendor_result = $vendor_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vendor_result) {
            throw new Exception('Product not found');
        }
        
        $vendor_id = $vendor_result['vendor_id'];
        
        // Check if review already exists
        $check_query = "SELECT review_id FROM reviews 
                       WHERE user_id = :user_id AND product_id = :product_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing review
            $update_query = "UPDATE reviews 
                           SET rating = :rating, 
                               review_text = :review, 
                               review_date = CURRENT_TIMESTAMP 
                           WHERE user_id = :user_id 
                           AND product_id = :product_id";
            $stmt = $conn->prepare($update_query);
            $params = [
                ':user_id' => $user_id,
                ':product_id' => $product_id,
                ':rating' => $rating,
                ':review' => $review
            ];
        } else {
            // Insert new review
            $insert_query = "INSERT INTO reviews (user_id, product_id, vendor_id, rating, review_text, review_date) 
                           VALUES (:user_id, :product_id, :vendor_id, :rating, :review, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($insert_query);
            $params = [
                ':user_id' => $user_id,
                ':product_id' => $product_id,
                ':vendor_id' => $vendor_id,
                ':rating' => $rating,
                ':review' => $review
            ];
        }
        
        $stmt->execute($params);
        
        // Update product ratings
        updateProductRatings($conn, $product_id);
        
        // Update vendor ratings
        updateVendorRatings($conn, $vendor_id);
        
        header('Location: history.php');
        exit();
        
    } catch (Exception $e) {
        // Log the error and show user-friendly message
        error_log($e->getMessage());
        $_SESSION['error'] = "There was an error submitting your review. Please try again.";
        header('Location: history.php');
        exit();
    }
}

// Function to update product ratings
function updateProductRatings($conn, $product_id) {
    $update_query = "UPDATE products p 
                    SET rating = (
                        SELECT AVG(rating) 
                        FROM reviews 
                        WHERE product_id = :product_id
                    ),
                    total_reviews = (
                        SELECT COUNT(*) 
                        FROM reviews 
                        WHERE product_id = :product_id
                    )
                    WHERE product_id = :product_id";
    
    $stmt = $conn->prepare($update_query);
    $stmt->execute([':product_id' => $product_id]);
}

// Function to update vendor ratings
function updateVendorRatings($conn, $vendor_id) {
    $update_query = "UPDATE vendors v 
                    SET rating = (
                        SELECT AVG(rating) 
                        FROM reviews 
                        WHERE vendor_id = :vendor_id
                    ),
                    total_reviews = (
                        SELECT COUNT(*) 
                        FROM reviews 
                        WHERE vendor_id = :vendor_id
                    )
                    WHERE vendor_id = :vendor_id";
    
    $stmt = $conn->prepare($update_query);
    $stmt->execute([':vendor_id' => $vendor_id]);
}
?>