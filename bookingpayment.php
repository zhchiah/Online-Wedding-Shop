<?php
require_once 'catalogdb.php';
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Get appointment details from URL
if (!isset($_GET['appointment_id'])) {
    header('Location: bookinghistory.php');
    exit();
}

$appointment_id = filter_var($_GET['appointment_id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch appointment details
function getAppointmentDetails($appointment_id, $user_id)
{
    $conn = getDBConnection();
    $query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.status,
            a.notes,
            p.name AS service_name,
            p.price,
            v.business_name AS vendor_name
        FROM 
            appointments a
        LEFT JOIN 
            products p ON a.product_id = p.product_id
        LEFT JOIN 
            vendors v ON a.vendor_id = v.vendor_id
        WHERE 
            a.appointment_id = :appointment_id
            AND a.user_id = :user_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':appointment_id' => $appointment_id,
        ':user_id' => $user_id
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$appointment = getAppointmentDetails($appointment_id, $user_id);

// Verify appointment exists and belongs to user
if (!$appointment) {
    header('Location: bookinghistory.php');
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = ['card_number', 'expiry', 'cvv'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // Process payment if no errors
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $conn->beginTransaction();

            // Update appointment status
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET status = 'paid' 
                WHERE appointment_id = :appointment_id
            ");
            $stmt->execute([':appointment_id' => $appointment_id]);

            // Record payment
            $stmt = $conn->prepare("
                INSERT INTO booking_payments 
                (appointment_id, amount, payment_date) 
                VALUES 
                (:appointment_id, :amount, NOW())
            ");
            $stmt->execute([
                ':appointment_id' => $appointment_id,
                ':amount' => $appointment['price']
            ]);

            $conn->commit();
            header('Location: bookingpayment.php?payment_success=1');
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Payment processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Payment - Graceful Gatherings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .payment-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .appointment-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card-input {
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h1 class="mb-4">Complete Your Booking Payment</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="appointment-summary">
            <h3>Appointment Details</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_name']); ?></p>
                    <p><strong>Vendor:</strong> <?php echo htmlspecialchars($appointment['vendor_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Total Amount:</strong> $<?php echo number_format($appointment['price'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?></p>
                </div>
            </div>
        </div>

        <div class="payment-form">
            <h3 class="mb-4">Payment Information</h3>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="card_number" class="form-label">Card Number</label>
                    <input type="text" id="card_number" name="card_number" class="card-input" 
                           required maxlength="16" placeholder="1234 5678 9012 3456">
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="expiry" class="form-label">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" class="card-input" 
                               required placeholder="MM/YY">
                    </div>
                    <div class="col-md-6">
                        <label for="cvv" class="form-label">CVV</label>
                        <input type="text" id="cvv" name="cvv" class="card-input" 
                               required maxlength="4" placeholder="123">
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Complete Payment</button>
                    <a href="bookinghistory.php" class="btn btn-outline-secondary">Back to Appointments</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Card number formatting
            document.getElementById('card_number').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 16) value = value.slice(0, 16);
                e.target.value = value;
            });

            // Expiry date formatting
            document.getElementById('expiry').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            });

            // CVV formatting
            document.getElementById('cvv').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 4) value = value.slice(0, 4);
                e.target.value = value;
            });

            // Form validation
            const form = document.querySelector('form.needs-validation');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>