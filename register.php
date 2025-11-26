<?php
include 'db_connection.php'; // Database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $business_name = $_POST['business_name'] ?? null;
    $service_category = $_POST['service_category'] ?? null;

    // Password validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Start a transaction
        $conn->begin_transaction();
        try {
            // Insert user
            $query_user = "INSERT INTO users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)";
            $stmt_user = $conn->prepare($query_user);
            $stmt_user->bind_param("ssss", $username, $email, $password_hash, $user_type);
            $stmt_user->execute();
            $user_id = $stmt_user->insert_id;

            // If user is a vendor, insert into vendors table
            if ($user_type === 'vendor') {
                $query_vendor = "INSERT INTO vendors (user_id, business_name, service_category) VALUES (?, ?, ?)";
                $stmt_vendor = $conn->prepare($query_vendor);
                $stmt_vendor->bind_param("iss", $user_id, $business_name, $service_category);
                $stmt_vendor->execute();
            }

            // Commit the transaction
            $conn->commit();
            $success = "Registration successful! You can now log in.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wedding Dream Registry</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@300;400;600&display=swap');
        
        body {
            background: linear-gradient(135deg, #FFD5E5, #FFBCBC, #FFF0F5);
            background-size: 400% 400%;
            animation: gradientFlow 15s ease infinite;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            perspective: 1000px;
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .wedding-form-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(255,105,180,0.2);
            padding: 40px;
            width: 450px;
            transform: rotateX(-10deg);
            transition: all 0.6s;
            position: relative;
            overflow: hidden;
        }
        
        .wedding-form-container::before {
            content: '💕';
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 100px;
            opacity: 0.1;
            z-index: 1;
        }
        
    h1.form-title, h2.form-title {
        font-family: 'Great Vibes', cursive;
        color: #FF69B4;
        text-align: center;
        text-shadow: 2px 2px 4px rgba(255,105,180,0.2);
        margin-bottom: 20px;
    }

    h1.form-title {
        font-size: 3em; 
    }

    h2.form-title {
        font-size: 1.5em; /
        margin-top: -10px; 
    }

        input, select {
            width: 95%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid #FFB6C1;
            border-radius: 15px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #FF1493;
            box-shadow: 0 0 15px rgba(255,20,147,0.2);
        }
        
        button {
            background: linear-gradient(45deg, #FF69B4, #FF1493);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 15px;
            width: 100%;
            font-weight: bold;
            letter-spacing: 1px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        button:hover {
            transform: scale(1.05) rotate(2deg);
            box-shadow: 0 10px 20px rgba(255,20,147,0.3);
        }
        
        #vendor_fields {
            background: rgba(255,182,193,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="wedding-form-container">
        <h1 class="form-title">Wedding Dreams</h1>
        <h2 class="form-title">Register Your Account</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <form method="POST">
            <input type="text" id="username" name="username" placeholder="Username" required>
            <input type="email" id="email" name="email" placeholder="Email Address" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            
            <select name="user_type" id="user_type" onchange="toggleVendorFields()" required>
                <option value="user">User</option>
                <option value="vendor">Vendor</option>
                <option value="admin">Admin</option>
            </select>
            
            <div id="vendor_fields" style="display:none;">
                <input type="text" id="business_name" name="business_name" placeholder="Business Name">
                
                <select name="service_category" id="service_category">
                    <option value="attire">ATTIRE</option>
                    <option value="flowers">FLOWERS</option>
                    <option value="venue">VENUE</option>
                    <option value="photography">PHOTOGRAPHY</option>
                    <option value="jewelry">JEWELRY</option>
                </select>
            </div>
            
            <button type="submit">Create My Wedding Journey</button>
        </form>
    </div>

    <script>
        function toggleVendorFields() {
            const userType = document.getElementById('user_type').value;
            const vendorFields = document.getElementById('vendor_fields');
            vendorFields.style.display = (userType === 'vendor') ? 'block' : 'none';
        }
    </script>
</body>
</html>