<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['vendor_id'];

// Handle Edit Product
if (isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    
    // Handle image upload
    $image_url = $_POST['current_image_url']; // Default to current image
    
    // Check if a new image is uploaded
    if (!empty($_FILES['image_url']['name'])) {
        $upload_dir = 'uploads/products/'; // Make sure this directory exists and is writable
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . basename($_FILES['image_url']['name']);
        $target_path = $upload_dir . $filename;
        
        // Upload new image
        if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_path)) {
            // Delete old image if it exists
            if (!empty($image_url) && file_exists($image_url)) {
                unlink($image_url);
            }
            $image_url = $target_path;
        } else {
            $error = "Failed to upload image.";
        }
    }
    
    // Prepare SQL to update product
    $query = "UPDATE products SET 
              name = ?, 
              description = ?, 
              category = ?, 
              price = ?, 
              stock_quantity = ?, 
              image_url = ? 
              WHERE product_id = ? AND vendor_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssdisii", 
        $name, 
        $description, 
        $category, 
        $price, 
        $stock_quantity, 
        $image_url, 
        $product_id, 
        $vendor_id
    );
    
    if ($stmt->execute()) {
        $success = "Product updated successfully!";
    } else {
        $error = "Failed to update product: " . $stmt->error;
    }
}

// Handle Delete Product
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Get current image URL before deleting
    $image_query = "SELECT image_url FROM products WHERE product_id = ? AND vendor_id = ?";
    $image_stmt = $conn->prepare($image_query);
    $image_stmt->bind_param("ii", $product_id, $vendor_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    $product = $image_result->fetch_assoc();
    
    $query = "DELETE FROM products WHERE product_id=? AND vendor_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $product_id, $vendor_id);
    if ($stmt->execute()) {
        // Delete product image if it exists
        if (!empty($product['image_url']) && file_exists($product['image_url'])) {
            unlink($product['image_url']);
        }
        $success = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product: " . $stmt->error;
    }
}

// Fetch Approved Products for the Vendor
$query = "SELECT * FROM products WHERE vendor_id=? AND status='approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Products</title>
<script>
function openEditForm(product) {
    // Populate edit modal with product details
    document.getElementById('edit_product_id').value = product.product_id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_category').value = product.category;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_stock_quantity').value = product.stock_quantity;
    
    // Set current image URL in hidden input
    document.getElementById('edit_current_image_url').value = product.image_url;
    
    // Display current image if exists
    var currentImageDiv = document.getElementById('current_image');
    currentImageDiv.innerHTML = ''; // Clear previous image
    if (product.image_url) {
        var img = document.createElement('img');
        img.src = product.image_url;
        currentImageDiv.appendChild(img);
    } else {
        var noImageText = document.createElement('p');
        noImageText.textContent = 'No Image Available';
        noImageText.style.color = '#888';
        currentImageText.appendChild(noImageText);
    }
    
    // Show modal
    document.getElementById('editModal').style.display = 'block';

        function showPopupMessage(message) {
            const popup = document.createElement('div');
            popup.classList.add('popup-message');
            popup.textContent = message;
            
            const closeBtn = document.createElement('span');
            closeBtn.classList.add('close-btn');
            closeBtn.textContent = '×';
            closeBtn.onclick = function() {
                popup.style.opacity = '0';
                setTimeout(() => {
                    popup.remove();
                }, 300);
            };
            
            popup.appendChild(closeBtn);
            document.body.appendChild(popup);
            popup.classList.add('show');
            
            setTimeout(() => {
                popup.style.opacity = '0';
                setTimeout(() => {
                    popup.remove();
                }, 300);
            }, 3000);
        }
    }

</script>
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
                    <a href="appointments.php" class="nav-item">Appointments</a>
                    <a href="products.php" class="nav-item ">Add Product</a>
                    <a href="product_list.php" class="nav-item active">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>

                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Logout</a>
                </nav>
            </div>
        </div>
        <div class="main-content">
            <!-- Product Table -->
            <h2 style="text-align: center;">Product List</h2>            
            <table>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Rating</th>
                    <th>Reviews</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="product-image" alt="Product Image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>RM<?php echo htmlspecialchars($product['price']); ?></td>
                        <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                        <td>
                            <span class="rating">
                                <?php echo number_format($product['rating'], 1); ?> ★
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($product['total_reviews']); ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <button type="button" onclick='openEditForm(<?php echo json_encode($product); ?>)'>Edit</button>
                                <button type="submit" name="delete_product" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    
<?php if (isset($success)) : ?>
    <script>
        showPopupMessage("<?php echo htmlspecialchars($success); ?>");
    </script>
<?php endif; ?>

<?php if (isset($error)) : ?>
    <script>
        showPopupMessage("<?php echo htmlspecialchars($error); ?>");
    </script>
<?php endif; ?>
    
    <!-- Edit Product Modal -->
    <div id="editModal" style="display:none;">
        <div class="edit-modal-content">
            <div id="current_image"></div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                <input type="hidden" name="current_image_url" id="edit_current_image_url">
                
                <div class="form-group">    
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <option value="wedding_dress">Wedding Dress</option>
                        <option value="tuxedo">Tuxedo</option>
                        <option value="bouquet">Bouquet</option>
                        <option value="centerpiece">Centerpiece</option>
                        <option value="invitation">Invitation</option>
                        <option value="favor">Favor</option>
                        <option value="ring">Ring</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price</label>
                    <input type="number" id="edit_price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_stock_quantity">Stock Quantity</label>
                    <input type="number" id="edit_stock_quantity" name="stock_quantity" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_product_image">Update Product Image</label>
                    <input type="file" id="edit_product_image" name="image_url" accept="image/*">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="edit_product">Update Product</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
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
            max-width: 1200px;
            margin: 0 auto;
        }
        .message { color: green; }
        .error { color: red; }
        .product-form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .popup-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            text-align: center;
        }
        .popup-message.show {
            opacity: 1;
        }
        .popup-message .close-btn {
            float: right;
            margin-left: 15px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }

    #editModal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-width: 400px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        z-index: 1000;
    }

    #current_image {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
        text-align: center;
    }

    #current_image img {
        max-width: 200px;
        max-height: 200px;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    #current_image::before {
        display: block;
        width: 100%;
        text-align: center;
        font-weight: bold;
        margin-bottom: 10px;
        color: #555;
    }

    .edit-modal-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .form-group {
        width: 100%;
        max-width: 350px;
    }

    .form-group label {
        display: block;
        margin-bottom: 3px;
        font-weight: bold;
        font-size: 0.9em;
        color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
        max-height: 150px;
    }

    #current_image img {
        max-width: 150px;
        max-height: 150px;
        display: block;
        margin: 10px 0;
        border-radius: 4px;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
    }

    .form-actions button {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
    }

    .form-actions button[type="submit"] {
        background-color: #4CAF50;
        color: white;
    }

    .form-actions button[type="button"] {
        background-color: #f44336;
        color: white;
    }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }
        .rating {
            color: #f8ce0b;
        }
        #editModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }

    /* Product Form Styling */
    .product-form {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 20px auto;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-form:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    .product-form h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .form-group input, 
    .form-group textarea, 
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        box-sizing: border-box;
        transition: all 0.3s ease;
        outline: none;
    }
    .form-group input:focus, 
    .form-group textarea:focus, 
    .form-group select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    .product-form button {
        width: 100%;
        padding: 14px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 1px;
    }
    .product-form button:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }

    /* Product Table Styling */
    table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: hidden;
    }
    table th {
        background-color: #3498db;
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    table tr:hover {
        background-color: #f1f3f5;
        transition: background-color 0.3s ease;
    }
    table td {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
    }
    .product-image {
        max-width: 100px;
        max-height: 100px;
        border-radius: 8px;
        object-fit: cover;
    }
    .rating {
        color: #f39c12;
        font-weight: bold;
    }
    table button {
        padding: 8px 15px;
        margin: 0 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    table button:first-child {
        background-color: #2ecc71;
        color: white;
    }
    table button:last-child {
        background-color: #e74c3c;
        color: white;
    }
    table button:hover {
        opacity: 0.8;
        transform: translateY(-2px);
    }

    /* Message Styling */
    .message {
        background-color: #2ecc71;
        color: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 15px;
    }
    .error {
        background-color: #e74c3c;
        color: white;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 15px;
    }
    </style>