<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['user_id'];

// Fetch current vendor information
$query = "SELECT * FROM vendors WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$vendor_info = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_name = $_POST['business_name'];
    $address = $_POST['address'];
    $contact_email = $_POST['contact_email'];
    $phone = $_POST['phone'];
    $description = $_POST['description'];
    $service_category = $_POST['service_category'];

    // Update query to include description and service_category
    $query = "UPDATE vendors SET business_name = ?, address = ?, contact_email = ?, phone = ?, description = ?, service_category = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssi", $business_name, $address, $contact_email, $phone, $description, $service_category, $vendor_id);
    $stmt->execute();
    $success = "Account settings updated!";

    // Refresh vendor info after update
    $stmt = $conn->prepare("SELECT * FROM vendors WHERE user_id = ?");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vendor_info = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Account Settings</title>
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
        select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
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
    /* New CSS for form and table styling */
    form {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        margin: 20px auto;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    form:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    form label {
        display: block;
        margin-bottom: 8px;
        color: #2c3e50;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    form input, 
    form textarea, 
    form select {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        box-sizing: border-box;
        transition: all 0.3s ease;
        outline: none;
    }
    form input:focus, 
    form textarea:focus, 
    form select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    form button {
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
    form button:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }
    form button:active {
        transform: translateY(1px);
        box-shadow: 0 2px 5px rgba(52, 152, 219, 0.2);
    }
    /* Subtle animation for input validation */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 90% { transform: translateX(-2px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-2px); }
        40%, 60% { transform: translateX(2px); }
    }
    form input:invalid, 
    form textarea:invalid, 
    form select:invalid {
        animation: shake 0.5s;
        border-color: #e74c3c;
    }    

</style>

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
                    <a href="product_list.php" class="nav-item ">Shop</a>
                    <a href="vendor_reviews.php" class="nav-item  ">Review</a>

                    <a href="reports.php" class="nav-item">Sales</a>
                    <a href="account_settings.php" class="nav-item active">My Profile</a>
                    <a href="logout.php" class="nav-item logout">Log Out</a>
                </nav>
            </div>
        </div>
        
        <div class="main-content">
            <h2 style="text-align: center;">Edit Profile</h2>            
            <?php if (isset($success)) : ?>
            <div id="popupMessage" class="popup-message">
                <?php echo htmlspecialchars($success); ?>
                <span class="close-btn" onclick="closePopup()">&times;</span>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <label>Business Name:</label>
                <input type="text" name="business_name" value="<?php echo htmlspecialchars($vendor_info['business_name'] ?? ''); ?>" required>
                
                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($vendor_info['address'] ?? ''); ?>">
                
                <label>Contact Email:</label>
                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($vendor_info['contact_email'] ?? ''); ?>">
                
                <label>Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($vendor_info['phone'] ?? ''); ?>">
                
                <label>Description:</label>
                <textarea name="description" rows="4" cols="50"><?php echo htmlspecialchars($vendor_info['description'] ?? ''); ?></textarea>
                
                <label>Service Category:</label>
                <select name="service_category">
                    <option value="attire" <?php echo ($vendor_info['service_category'] == 'attire') ? 'selected' : ''; ?>>Attire</option>
                    <option value="flowers" <?php echo ($vendor_info['service_category'] == 'flowers') ? 'selected' : ''; ?>>Flowers</option>
                    <option value="venue" <?php echo ($vendor_info['service_category'] == 'venue') ? 'selected' : ''; ?>>Venue</option>
                    <option value="photography" <?php echo ($vendor_info['service_category'] == 'photography') ? 'selected' : ''; ?>>Photography</option>
                    <option value="jewelry" <?php echo ($vendor_info['service_category'] == 'jewelry') ? 'selected' : ''; ?>>Jewelry</option>
                </select>
                
                <button type="submit">Update</button>
            </form>
        </div>
    </div>
    
    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>
    <script>
        function closePopup() {
            const popup = document.getElementById('popupMessage');
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.remove();
            }, 300);
        }

        // Automatically close the popup after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('popupMessage');
            if (popup) {
                popup.classList.add('show');
                setTimeout(closePopup, 3000);
            }
        });
    </script>
</body>
</html>