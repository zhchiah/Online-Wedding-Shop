<?php
session_start();
include 'db_connection.php';

$user_id = $_SESSION['user_id'];

$query = "
    SELECT 
        a.appointment_date, 
        a.status, 
        a.notes,
        p.name AS product_name, 
        v.business_name AS vendor_name
    FROM 
        appointments a
    LEFT JOIN 
        products p ON a.product_id = p.product_id
    LEFT JOIN 
        vendors v ON a.vendor_id = v.vendor_id
    WHERE 
        a.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Appointments</title>
</head>
<body>
    <h1>Your Appointments</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Product</th>
                <th>Vendor</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
