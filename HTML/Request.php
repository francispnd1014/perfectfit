<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

require_once 'connection.php';
$conn = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'];

    if ($action === 'update_status') {
        $status = $_POST['status'];
        $reason = $_POST['reason'] ?? null;

        $conn->begin_transaction();
        try {
            if ($status === 'declined') {
                $sql = "UPDATE rent SET request = ?, reason = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $status, $reason, $id);
                $stmt->execute();
            } elseif ($status === 'payment') {
                // Accept the selected order
                $accept_sql = "UPDATE rent SET request = ? WHERE id = ?";
                $accept_stmt = $conn->prepare($accept_sql);
                $accept_stmt->bind_param("si", $status, $id);
                $accept_stmt->execute();

                // Update product status to rented (1) and increment tally
                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 1, p.tally = p.tally + 1
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } elseif ($status === 'returned') {
                // Update rent status
                $sql = "UPDATE rent SET request = ?, returned_date = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();

                // Update product status back to available (0)
                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 0
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } elseif ($status === 'received') {
                // Update rent status to received and set reservation to 0
                $sql = "UPDATE rent SET request = ?, reservation = 0 WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();

                // Update product status to rented (1) and increment tally
                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 1, p.tally = p.tally + 1
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } else {
                // Default update for other statuses
                $sql = "UPDATE rent SET request = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update_product') {
        $conn->begin_transaction();
        try {
            // Assuming update_product functionality is defined here
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Fetch rent requests for all tabs
$sql_all = "SELECT r.*, u.fname, u.sname, p.name as gown_name, p.img 
            FROM rent r 
            JOIN users u ON r.email = u.email 
            JOIN product p ON r.gownname_rented = p.name 
            ORDER BY r.id DESC";
$result_all = $conn->query($sql_all);

// Fetch rent requests for the pending tab, grouped by gown name and ordered by rent date descending
$sql_pending = "SELECT r.*, u.fname, u.sname, p.name as gown_name, p.img 
                FROM rent r 
                JOIN users u ON r.email = u.email 
                JOIN product p ON r.gownname_rented = p.name 
                WHERE r.request = 'pending' AND r.reservation = FALSE
                ORDER BY p.name, r.date_rented DESC";
$result_pending = $conn->query($sql_pending);

// Fetch reservation requests
$sql_reservations = "SELECT r.*, u.fname, u.sname, p.name as gown_name, p.img 
                     FROM rent r 
                     JOIN users u ON r.email = u.email 
                     JOIN product p ON r.gownname_rented = p.name 
                     WHERE r.reservation = TRUE
                     ORDER BY r.id DESC";
$result_reservations = $conn->query($sql_reservations);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PERFECT FIT</title>
    <link rel="stylesheet" href="../CSS/Request.css">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
</head>

<body>
    <div class="banner">
        <div class="navbar">
            <img src="../IMAGES/RICH SABINIANS.png" class="logo">
            <ul>
                <li><a href="../HTML/Dashboard.php">Home</a></li>
                <li><a href="../HTML/CRUD.php">Add</a></li>
                <li><a href="../HTML/Request.php">Request</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn" onclick="toggleDropdown(event)">Admin</a>
                    <div id="myDropdown" class="dropdown-content">
                        <a href="../HTML/Logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>

        <div class="container">
            <div class="tabs">
                <button class="tab-btn active" data-tab="pending">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="tab-btn" data-tab="reservations">
                    <i class="fas fa-calendar-check"></i> Reservation
                </button>
                <button class="tab-btn" data-tab="payment">
                    <i class="fas fa-money-bill-wave"></i> Payment
                </button>
                <button class="tab-btn" data-tab="accepted">
                    <i class="fas fa-check-circle"></i> Accepted
                </button>
                <button class="tab-btn" data-tab="received">
                    <i class="fas fa-box-check"></i> Received
                </button>
                <button class="tab-btn" data-tab="returned">
                    <i class="fas fa-undo"></i> Returned
                </button>
            </div>

            <div class="request-list">
                <div class="tab-content active" id="pending-tab">
                    <?php
                    $currentGownName = null;
                    while ($row = $result_pending->fetch_assoc()):
                        if ($currentGownName !== $row['gown_name']) {
                            if ($currentGownName !== null) {
                                echo '</div>'; // Close previous gown group
                            }
                            $currentGownName = $row['gown_name'];
                            echo '<div class="gown-group">';
                            echo '<h2>' . htmlspecialchars($currentGownName) . '</h2>';
                        }
                    ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-<?php echo strtolower($row['request']); ?>">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="accept-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'payment')">
                                            <i class="fas fa-check"></i> Accept Order
                                        </button>
                                        <button class="decline-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'declined')">
                                            <i class="fas fa-times"></i> Decline Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    endwhile;
                    if ($currentGownName !== null) {
                        echo '</div>'; // Close last gown group
                    }
                    ?>
                </div>

                <div class="tab-content" id="reservations-tab">
                    <?php
                    while ($row = $result_reservations->fetch_assoc()):
                    ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-<?php echo strtolower($row['request']); ?>">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="accept-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'received')">
                                            <i class="fas fa-check"></i> Received
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    endwhile;
                    ?>
                </div>
            </div>

            <div class="tab-content" id="payment-tab">
                <?php
                $currentGownName = null;
                $result_all->data_seek(0);
                while ($row = $result_all->fetch_assoc()):
                    if ($row['request'] == 'payment'):
                        if ($currentGownName !== $row['gown_name']) {
                            if ($currentGownName !== null) {
                                echo '</div>'; // Close previous gown group
                            }
                            $currentGownName = $row['gown_name'];
                            echo '<div class="gown-group">';
                            echo '<h2>' . htmlspecialchars($currentGownName) . '</h2>';
                        }
                ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-payment">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="accept-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'accepted')">
                                            <i class="fas fa-check"></i> Confirm Payment
                                        </button>
                                        <button class="decline-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'declined')">
                                            <i class="fas fa-times"></i> Decline Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    endif;
                endwhile;
                if ($currentGownName !== null) {
                    echo '</div>'; // Close last gown group
                }
                ?>
            </div>

            <div class="tab-content" id="accepted-tab">
                <?php
                $currentGownName = null;
                $result_all->data_seek(0);
                while ($row = $result_all->fetch_assoc()):
                    if ($row['request'] == 'accepted' && !$row['reservation']): // Exclude reservation gowns
                        if ($currentGownName !== $row['gown_name']) {
                            if ($currentGownName !== null) {
                                echo '</div>'; // Close previous gown group
                            }
                            $currentGownName = $row['gown_name'];
                            echo '<div class="gown-group">';
                            echo '<h2>' . htmlspecialchars($currentGownName) . '</h2>';
                        }
                ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-<?php echo strtolower($row['request']); ?>">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="receive-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'received')">
                                            <i class="fas fa-box-check"></i> Order Received
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    endif;
                endwhile;
                if ($currentGownName !== null) {
                    echo '</div>'; // Close last gown group
                }
                ?>
            </div>

            <div class="tab-content" id="received-tab">
                <?php
                $result_all->data_seek(0);
                while ($row = $result_all->fetch_assoc()):
                    if ($row['request'] == 'received'):
                ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-received">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="receive-btn" onclick="updateStatus(<?php echo $row['id']; ?>, 'returned')">
                                            <i class="fas fa-undo"></i> Return Gown
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    endif;
                endwhile;
                ?>
            </div>

            <div class="tab-content" id="returned-tab">
                <?php
                $result_all->data_seek(0);
                while ($row = $result_all->fetch_assoc()):
                    if ($row['request'] == 'returned'):
                ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="order-id">Order #<?php echo $row['id']; ?></div>
                                <div class="status-badge status-returned">
                                    <?php echo ucfirst($row['request']); ?>
                                </div>
                            </div>
                            <div class="request-body">
                                <div class="product-image">
                                    <?php
                                    $images = @unserialize($row['img']);
                                    if ($images === false) {
                                        $images = [$row['img']];
                                    }
                                    if (!empty($images)) {
                                        echo '<img src="uploaded_img/' . htmlspecialchars($images[0]) . '" alt="Gown Image">';
                                    }
                                    ?>
                                </div>

                                <div class="request-details">
                                    <div class="customer-section">
                                        <h3><i class="fas fa-user"></i> Customer Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['fname'] . ' ' . $row['sname']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                            <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($row['cellnumber']); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-section">
                                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                                        <div class="details-grid">
                                            <p><strong>Gown:</strong> <?php echo htmlspecialchars($row['gown_name']); ?></p>
                                            <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($row['service'])); ?></p>
                                            <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($row['date_rented'])); ?></p>
                                            <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($row['duedate'])); ?></p>
                                            <p><strong>Returned Date:</strong> <?php echo date('F d, Y', strtotime($row['returned_date'])); ?></p>
                                            <p class="total-price"><strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    endif;
                endwhile;
                ?>
            </div>
            <!-- Confirmation Modal -->
            <div id="confirmModal" class="modal">
                <div class="modal-content">
                    <h3>Confirm Action</h3>
                    <p id="confirmMessage"></p>
                    <div class="modal-buttons">
                        <button id="confirmYes" class="modal-btn">Yes</button>
                        <button id="confirmNo" class="modal-btn">No</button>
                    </div>
                </div>
            </div>
            <!-- Decline Reason Modal -->
            <div id="declineModal" class="modal">
                <div class="modal-content">
                    <h3>Decline Request</h3>
                    <p>Please select a reason for declining:</p>
                    <select id="declineReason" class="decline-select">
                        <option value="">Select a reason...</option>
                        <option value="This gown has already been reserved by a prior user.">This gown has already been reserved by a prior user.</option>
                        <option value="The requested location is outside our service area and cannot be accommodated at this time.">The requested location is outside our service area and cannot be accommodated at this time.</option>
                        <option value="Other">Other</option>
                    </select>
                    <textarea id="otherReason" class="decline-textarea" style="display:none" placeholder="Please specify the reason..."></textarea>
                    <div class="modal-buttons">
                        <button id="submitDecline" class="modal-btn1">Submit</button>
                        <button id="cancelDecline" class="modal-btn2">Cancel</button>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const tabs = document.querySelectorAll('.tab-btn');

                    tabs.forEach(tab => {
                        tab.addEventListener('click', () => {
                            // Remove active class from all tabs
                            tabs.forEach(t => t.classList.remove('active'));

                            // Add active class to clicked tab
                            tab.classList.add('active');

                            // Hide all tab content
                            document.querySelectorAll('.tab-content').forEach(content => {
                                content.classList.remove('active');
                            });

                            // Show selected tab content
                            const targetTab = tab.getAttribute('data-tab');
                            document.getElementById(targetTab + '-tab').classList.add('active');
                        });
                    });
                });

                function toggleDropdown(event) {
                    event.stopPropagation();
                    var dropdown = event.currentTarget.parentElement;
                    dropdown.classList.toggle("show");
                }

                window.onclick = function(event) {
                    var dropdowns = document.getElementsByClassName("dropdown");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }

                function updateStatus(id, status) {
                    if (status === 'declined') {
                        // Show decline reason modal
                        const declineModal = document.getElementById('declineModal');
                        const reasonSelect = document.getElementById('declineReason');
                        const otherReason = document.getElementById('otherReason');
                        const submitBtn = document.getElementById('submitDecline');
                        const cancelBtn = document.getElementById('cancelDecline');

                        declineModal.style.display = 'block';

                        reasonSelect.onchange = function() {
                            otherReason.style.display = this.value === 'Other' ? 'block' : 'none';
                        };

                        submitBtn.onclick = function() {
                            const reason = reasonSelect.value === 'Other' ? otherReason.value : reasonSelect.value;
                            if (!reason) {
                                alert('Please select or specify a reason');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('action', 'update_status');
                            formData.append('id', id);
                            formData.append('status', status);
                            formData.append('reason', reason);

                            fetch(window.location.href, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Error updating status');
                                    }
                                });
                            declineModal.style.display = 'none';
                        };

                        cancelBtn.onclick = function() {
                            declineModal.style.display = 'none';
                        };

                        window.onclick = function(event) {
                            if (event.target == declineModal) {
                                declineModal.style.display = 'none';
                            }
                        };
                    } else {
                        // Original confirmation modal for other statuses
                        const modal = document.getElementById('confirmModal');
                        const message = document.getElementById('confirmMessage');
                        const yesBtn = document.getElementById('confirmYes');
                        const noBtn = document.getElementById('confirmNo');

                        message.textContent = 'Are you sure you want to ' + (status === 'payment' ? 'accept' : status) + ' this request?';
                        modal.style.display = 'block';

                        yesBtn.onclick = function() {
                            const formData = new FormData();
                            formData.append('action', 'update_status');
                            formData.append('id', id);
                            formData.append('status', status);

                            fetch(window.location.href, {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert('Error updating status');
                                    }
                                });
                            modal.style.display = 'none';
                        };

                        noBtn.onclick = function() {
                            modal.style.display = 'none';
                        };
                    }
                }
            </script>
</body>

</html>