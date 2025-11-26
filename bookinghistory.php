<?php
require_once 'catalogdb.php';
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$errors = [];
$success_message = '';
$appointment = null;
$payment_success = isset($_GET['payment_success']) && $_GET['payment_success'] == 1;





// Handle appointment payment processing
if (isset($_GET['appointment_id'])) {
    $appointment_id = filter_var($_GET['appointment_id'], FILTER_SANITIZE_NUMBER_INT);
    $appointment = getAppointmentById($appointment_id);

    // Verify appointment exists and belongs to user
    if (!$appointment || $appointment['user_id'] != $_SESSION['user_id']) {
        header('Location: appointments.php');
        exit();
    }

    // Handle payment form submission
    if (isset($_GET['appointment_id'])) {
        $appointment_id = filter_var($_GET['appointment_id'], FILTER_SANITIZE_NUMBER_INT);

        // Verify appointment belongs to user before redirecting
        $conn = getDBConnection();
        $stmt = $conn->prepare("
        SELECT user_id 
        FROM appointments 
        WHERE appointment_id = :appointment_id
    ");
        $stmt->execute([':appointment_id' => $appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($appointment && $appointment['user_id'] == $_SESSION['user_id']) {
            header('Location: bookingpayment.php?appointment_id=' . $appointment_id);
            exit();
        } else {
            header('Location: bookinghistory.php');
            exit();
        }
    }
}

// Fetch user's appointment history with enhanced details
function getUserAppointments($user_id)
{
    $conn = getDBConnection();
    $query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.status,
            a.notes,
            p.name AS product_name,
            p.price,
            v.business_name AS vendor_name,
            COALESCE(bp.payment_date, '') as payment_date
        FROM 
            appointments a
        LEFT JOIN 
            products p ON a.product_id = p.product_id
        LEFT JOIN 
            vendors v ON a.vendor_id = v.vendor_id
        LEFT JOIN 
            booking_payments bp ON a.appointment_id = bp.appointment_id
        WHERE 
            a.user_id = :user_id
        ORDER BY 
            a.appointment_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$appointments = getUserAppointments($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Graceful Gatherings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .container {
            min-height: 100vh;
            display: flex;
            position: relative;
        }

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

    .main-content {
        flex: 1;
        padding: 30px;
        padding-bottom: 80px;
        margin-left: 0;
        overflow-y: auto; /* Enables vertical scrolling */
        max-height: 100vh; /* Prevents content from exceeding viewport height */
        transition: margin-left 0.3s ease-in-out;
    }

        /* Enhanced styles for the main content */
        .appointment-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Table styling */
        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }

        /* Status badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Button styling */
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #6610f2);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #0056b3, #520dc2);
            transform: translateY(-2px);
        }

        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(45deg, #138496, #1aa179);
            color: white;
            transform: translateY(-2px);
        }

        /* Modal styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(45deg, #007bff, #6610f2);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            font-weight: 600;
        }

        /* Form styling */
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }

        /* Alert styling */
        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        /* Payment form specific styling */
        .payment-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        /* Animation for status changes */
        @keyframes statusChange {
            0% { transform: scale(0.95); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .status-badge {
            animation: statusChange 0.3s ease-out forwards;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .appointment-container {
                padding: 1rem;
            }

            .table {
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
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
                <a href="catalog.php" class="nav-item ">Catalog</a>
                <a href="cart.php" class="nav-item ">Shopping Cart</a>
                <a href="History.php" class="nav-item ">Purchase History</a>
                <a href="bookinghistory.php" class="nav-item active">Appointment</a>
                <a href="userprofile.php" class="nav-item">My Profile</a>
                <a href="login.php" class="nav-item">Logout</a>
            </nav>
        </aside>

    <div class="appointment-container">
        <?php if ($payment_success): ?>
            <div class="alert alert-success">
                Payment processed successfully! Your appointment has been confirmed.
            </div>
        <?php endif; ?>

        <?php if ($appointment): ?>
            <!-- Payment Form Section -->
            <div class="payment-form">
                <h2 class="mb-4">Complete Payment</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Appointment Details</h5>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['product_name']); ?></p>
                        <p><strong>Vendor:</strong> <?php echo htmlspecialchars($appointment['vendor_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                        <p><strong>Total Amount:</strong> RM<?php echo number_format($appointment['price'], 2); ?></p>
                    </div>
                </div>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" id="card_number" name="card_number" class="form-control" 
                               required maxlength="16" placeholder="1234 5678 9012 3456">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="expiry" class="form-label">Expiry Date</label>
                            <input type="text" id="expiry" name="expiry" class="form-control" 
                                   required placeholder="MM/YY">
                        </div>
                        <div class="col-md-6">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" id="cvv" name="cvv" class="form-control" 
                                   required maxlength="4" placeholder="123">
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Complete Payment</button>
                        <a href="appointments.php" class="btn btn-outline-secondary">Back to Appointments</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Appointments History Section -->
            <h2 class="mb-4">My Appointments</h2>
            
            <?php if (empty($appointments)): ?>
                <div class="alert alert-info">You have no appointments yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Service</th>
                                <th>Vendor</th>
                                <th>Date & Time</th>
                                <th>Amount</th>
                                <th>Status</th>

                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $apt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apt['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['vendor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                                    <td>RM<?php echo number_format($apt['price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($apt['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($apt['status'])); ?>
                                        </span>
                                    </td>
 

                                       <td>
                                        <?php if (strtolower($apt['status']) !== 'paid'): ?>
                                            <a href="bookingpayment.php?appointment_id=<?php echo $apt['appointment_id']; ?>" 
                                               class="btn btn-primary btn-sm">Pay Now</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#appointmentModal"
                                                data-appointment='<?php echo htmlspecialchars(json_encode($apt)); ?>'>
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Appointment Details Modal -->
            <div class="modal fade" id="appointmentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Appointment Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Modal content will be populated by JavaScript -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="#" id="modalPayButton" class="btn btn-primary" style="display:none;">
                                Proceed to Payment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
               document.addEventListener('DOMContentLoaded', function() {
            // Card number formatting
            document.getElementById('card_number')?.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 16) value = value.slice(0, 16);
                e.target.value = value;
            });

            // Expiry date formatting
            document.getElementById('expiry')?.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            });

            // CVV formatting
            document.getElementById('cvv')?.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 4) value = value.slice(0, 4);
                e.target.value = value;
            });

            // Modal handling
 const appointmentModal = document.getElementById('appointmentModal');
    if (appointmentModal) {
        appointmentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const appointmentData = JSON.parse(button.getAttribute('data-appointment'));
            const modalBody = this.querySelector('.modal-body');
            const payButton = document.getElementById('modalPayButton');
            
            modalBody.innerHTML = `
                <div class="mb-3">
                    <p><strong>Service:</strong> ${appointmentData.product_name}</p>
                    <p><strong>Vendor:</strong> ${appointmentData.vendor_name}</p>
                    <p><strong>Date & Time:</strong> ${appointmentData.appointment_date}</p>
                    <p><strong>Amount:</strong> $${parseFloat(appointmentData.price).toFixed(2)}</p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-${appointmentData.status.toLowerCase()}">
                            ${appointmentData.status}
                        </span>
                    </p>
                    ${appointmentData.notes ? `<p><strong>Notes:</strong> ${appointmentData.notes}</p>` : ''}
                    ${appointmentData.payment_date ? `
                        <div class="mt-3 pt-3 border-top">
                            <h6>Payment Information</h6>
                            <p><strong>Payment Date:</strong> ${appointmentData.payment_date}</p>
                        </div>
                    ` : ''}
                </div>
            `;
            
            // Always show payment button and set its href
             if (appointmentData.status() === 'paid') {
                        payButton.style.display = 'none';
                    } else {
                        payButton.style.display = 'inline-block';
                        payButton.href = `bookingpayment.php?appointment_id=${appointmentData.appointment_id}`;
                    }
                });
            }

            // Form validation
            const form = document.querySelector('form.needs-validation');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            }

            // Trigger success message auto-hide
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.transition = 'opacity 0.5s ease-out';
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.remove(), 500);
                }, 5000);
            }
        });

        // Add print functionality
        function printAppointmentDetails(appointmentId) {
            const printWindow = window.open('', '_blank');
            const appointmentRow = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
            const appointmentData = JSON.parse(appointmentRow.getAttribute('data-appointment'));
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Appointment Details - ${appointmentData.product_name}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .details { margin-bottom: 20px; }
                        .footer { margin-top: 50px; text-align: center; font-size: 0.8em; }
                        .status { 
                            display: inline-block;
                            padding: 5px 10px;
                            border-radius: 4px;
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Appointment Confirmation</h1>
                        <p>Graceful Gatherings</p>
                    </div>
                    <div class="details">
                        <h3>Appointment Details</h3>
                        <p><strong>Service:</strong> ${appointmentData.product_name}</p>
                        <p><strong>Vendor:</strong> ${appointmentData.vendor_name}</p>
                        <p><strong>Date & Time:</strong> ${appointmentData.appointment_date}</p>
                        <p><strong>Amount:</strong> $${parseFloat(appointmentData.price).toFixed(2)}</p>
                        <p><strong>Status:</strong> ${appointmentData.status}</p>
                        ${appointmentData.notes ? `<p><strong>Notes:</strong> ${appointmentData.notes}</p>` : ''}
                        ${appointmentData.status === 'paid' ? `
                            <div style="margin-top: 20px;">
                                <h3>Payment Information</h3>
                                <p><strong>Card Used:</strong> **** **** **** ${appointmentData.card_last_four}</p>
                                <p><strong>Payment Date:</strong> ${new Date(appointmentData.payment_date).toLocaleDateString()}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="footer">
                        <p>Thank you for choosing Graceful Gatherings</p>
                        <p>For any questions, please contact our support team</p>
                        <p>Printed on: ${new Date().toLocaleString()}</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>