<?php
session_start();
include 'db_connection.php'; // Include your database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php'); // Redirect to login if not admin
    exit;
}

// Fetch all users with user_type = 'user'
$query = "SELECT user_id, username, email, user_type FROM users WHERE user_type = 'user'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Handle form submission for updating a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];

    $update_query = "UPDATE users SET username = ?, email = ?, user_type = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $username, $email, $user_type, $user_id);
    $update_stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle form submission for deleting a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <style>
        /* Existing Styles */
        body {
            background-image: url('https://www.wallpapergap.com/wp-content/uploads/2024/10/half-couple-wallpapers.jpg');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        table:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: scale(1.01);
        }

        thead {
            background-color: rgba(0, 0, 0, 0.05);
        }

        th {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
            font-weight: 700;
            text-transform: uppercase;
            color: black;
            font-size: 0.9em;
            letter-spacing: 1px;
            background-color: rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        th:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: black;
            transition: all 0.3s ease;
        }

        tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.1);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .main-content h1 {
            color: black;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            letter-spacing: 1px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .main-content h1:hover {
            transform: scale(1.05);
            color: #2c3e50;
        }

        .main-content h2 {
            color: black;
            text-align: center;
            font-size: 1.8em;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .main-content h2:hover {
            transform: scale(1.03);
            color: #34495e;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .edit-btn {
            background-color: #2196F3;
            color: white;
        }
        #editFormContainer {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            box-shadow: 
            0 10px 25px rgba(0, 0, 0, 0.2), 
            0 0 0 1px rgba(0, 0, 0, 0.05);
            padding: 30px;
            z-index: 1000;
            max-width: 500px;
            width: 90%;
            animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            backdrop-filter: blur(5px);
        }

        @keyframes slideIn {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.7);
        }
        1   00% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        }

        #editFormContainer:hover {
            box-shadow: 
            0 15px 35px rgba(0, 0, 0, 0.3), 
            0 0 0 1px rgba(0, 0, 0, 0.08);
            transform: translate(-50%, -50%) scale(1.02);
            transition: all 0.3s ease;
        }

        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .close-btn {
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            float: right;
        }

        /* New Sidebar Styles */
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

        /* Footer Styles */
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

        /* Container for main content */
        .container {
            min-height: 100vh;
            padding-bottom: 60px; /* Space for footer */
        }
        .main-content {
            transition: margin-left 0.3s ease-in-out;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="hover-trigger"></div>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>User</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admindashboard.php" class="nav-item">Home</a>      
                <a href="admin_approve.php" class="nav-item ">Product Approval</a>
                <a href="manageuserpage.php" class="nav-item active">User</a>                
                <a href="managevendorpage.php" class="nav-item">Vendor</a>
                <a href="manageadminpage.php" class="nav-item ">Admin</a>                
                <a href="admin_account.php" class="nav-item">Account</a>
                <a href="logout.php" class="nav-item">Log Out</a>
            </nav>
        </div>

        <div class="main-content">
            <h1>User Management</h1>
            <h2>List of Users</h2>

            <!-- Users Table -->
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <div class="actions">
                                    <button class="edit-btn" onclick="openEditForm(<?php echo $row['user_id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo $row['user_type']; ?>')">Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                        <button type="submit" name="delete_user" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Form -->
    <div id="overlay"></div>
    <div id="editFormContainer">
        <form method="POST">
            <input type="hidden" name="user_id" id="editUserId">
            <label for="editUsername">Username:</label>
            <input type="text" name="username" id="editUsername" required>
            <label for="editEmail">Email:</label>
            <input type="email" name="email" id="editEmail" required>
            <label for="editUserType">Role:</label>
            <select name="user_type" id="editUserType" required>
                <option value="user">User</option>
                <option value="staff">Staff</option>
                <option value="vendor">Vendor</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit" name="update_user" class="update-btn">Update</button>
            <button type="button" class="close-btn" onclick="closeEditForm()">Close</button>
        </form>
    </div>

    <footer class="footer">
    <p>This website is a fictional project created for educational purposes as part of a university course.</p>
    <p>@-Not a Real Business</p>    
</footer>

    <script>
        function openEditForm(userId, username, email, userType) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUsername').value = username;
            document.getElementById('editEmail').value = email;
            document.getElementById('editUserType').value = userType;

            document.getElementById('editFormContainer').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeEditForm() {
            document.getElementById('editFormContainer').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</body>
</html>