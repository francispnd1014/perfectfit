<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
            if ($status === 'cancelled') {
                $sql = "UPDATE rent SET request = ?, reason = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $status, $reason, $id);
                $stmt->execute();
            } elseif ($status === 'payment') {

                $accept_sql = "UPDATE rent SET request = ? WHERE id = ?";
                $accept_stmt = $conn->prepare($accept_sql);
                $accept_stmt->bind_param("si", $status, $id);
                $accept_stmt->execute();


                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 1, p.tally = p.tally + 1
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } elseif ($status === 'returned') {

                $sql = "UPDATE rent SET request = ?, returned_date = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();


                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 0
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } elseif ($status === 'received') {

                $sql = "UPDATE rent SET request = ?, reservation = 0 WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();


                $sql2 = "UPDATE product p
                         JOIN rent r ON p.name = r.gownname_rented
                         SET p.status = 1, p.tally = p.tally + 1
                         WHERE r.id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
            } else {

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

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}


$sql_all = "SELECT r.*, u.fname, u.sname, p.name as gown_name, p.img 
            FROM rent r 
            JOIN users u ON r.email = u.email 
            JOIN product p ON r.gownname_rented = p.name 
            ORDER BY r.id DESC";
$result_all = $conn->query($sql_all);


$sql_pending = "SELECT r.*, u.fname, u.sname, p.name as gown_name, p.img 
                FROM rent r 
                JOIN users u ON r.email = u.email 
                JOIN product p ON r.gownname_rented = p.name 
                WHERE r.request = 'pending' AND r.reservation = FALSE
                ORDER BY p.name, r.date_rented DESC";
$result_pending = $conn->query($sql_pending);


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
                    <i class="fas fa-money-bill-wave"></i> Accepted
                </button>
                <button class="tab-btn" data-tab="accepted">
                    <i class="fas fa-check-circle"></i> Service
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
    $currentEmail = null;
    $batchGroups = array();
    
    while ($row = $result_pending->fetch_assoc()) {
        $key = $row['email'] . '-' . $row['batch'];
        if (!isset($batchGroups[$key])) {
            $batchGroups[$key] = array();
        }
        $batchGroups[$key][] = $row;
    }

    foreach ($batchGroups as $group):
        $firstRow = $group[0];
        if ($currentEmail !== $firstRow['email']) {
            if ($currentEmail !== null) {
                echo '</div>';
            }
            $currentEmail = $firstRow['email'];
            echo '<div class="gown-group">';
            echo '<h2>' . htmlspecialchars($currentEmail) . '</h2>';
        }
        
        if ($firstRow['batch']):
            echo '<div class="batch-group">';
            echo '<h3>Batch Order (' . htmlspecialchars($firstRow['email']) . ')</h3>';            
            foreach ($group as $row):
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
                                    <p class="total-price">
                                        <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                        <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="batch-action-buttons">
                <button class="accept-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'payment')">
                    <i class="fas fa-check"></i> Accept Batch Order
                </button>
                <button class="decline-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'cancelled')">
                    <i class="fas fa-times"></i> Decline Batch Order
                </button>
            </div>
            </div>
        <?php else: ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="order-id">Order #<?php echo $firstRow['id']; ?></div>
                    <div class="status-badge status-<?php echo strtolower($firstRow['request']); ?>">
                        <?php echo ucfirst($firstRow['request']); ?>
                    </div>
                </div>
                <div class="request-body">
                    <div class="product-image">
                        <?php
                        $images = @unserialize($firstRow['img']);
                        if ($images === false) {
                            $images = [$firstRow['img']];
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
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstRow['fname'] . ' ' . $firstRow['sname']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($firstRow['email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($firstRow['address']); ?></p>
                                <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($firstRow['cellnumber']); ?></p>
                            </div>
                        </div>
                        <div class="order-section">
                            <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                            <div class="details-grid">
                                <p><strong>Gown:</strong> <?php echo htmlspecialchars($firstRow['gown_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($firstRow['service'])); ?></p>
                                <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['date_rented'])); ?></p>
                                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['duedate'])); ?></p>
                                <p class="total-price">
                                    <strong>Total:</strong> ₱<?php echo number_format($firstRow['total'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="accept-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'payment')">
                                <i class="fas fa-check"></i> Accept Order
                            </button>
                            <button class="decline-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Decline Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    endforeach;
    if ($currentEmail !== null) {
        echo '</div>';
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
                                            <p class="total-price">
                                                <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                                <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                            </p>
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
    $currentEmail = null;
    $batchGroups = array();
    
    $result_all->data_seek(0);
    while ($row = $result_all->fetch_assoc()) {
        if ($row['request'] == 'payment') {
            $key = $row['email'] . '-' . $row['batch'];
            if (!isset($batchGroups[$key])) {
                $batchGroups[$key] = array();
            }
            $batchGroups[$key][] = $row;
        }
    }

    foreach ($batchGroups as $group):
        $firstRow = $group[0];
        if ($currentEmail !== $firstRow['email']) {
            if ($currentEmail !== null) {
                echo '</div>';
            }
            $currentEmail = $firstRow['email'];
            echo '<div class="gown-group">';
            echo '<h2>' . htmlspecialchars($currentEmail) . '</h2>';
        }
        
        if ($firstRow['batch']):
            echo '<div class="batch-group">';
            echo '<h3>Batch Order for ' . htmlspecialchars($firstRow['email']) . '</h3>';
            foreach ($group as $row):
    ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="order-id">Order #<?php echo $row['id']; ?></div>
                        <div class="status-badge status-payment">
                            <p>Accepted</p>
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
                                    <p class="total-price">
                                        <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                        <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="batch-action-buttons">
                <button class="accept-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'accepted')">
                    <i class="fas fa-check"></i> Confirm Batch
                </button>
                <button class="decline-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'cancelled')">
                    <i class="fas fa-times"></i> Decline Batch
                </button>
            </div>
            </div>
        <?php else: ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="order-id">Order #<?php echo $firstRow['id']; ?></div>
                    <div class="status-badge status-payment">
                        <p>Accepted</p>
                    </div>
                </div>
                <div class="request-body">
                    <div class="product-image">
                        <?php
                        $images = @unserialize($firstRow['img']);
                        if ($images === false) {
                            $images = [$firstRow['img']];
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
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstRow['fname'] . ' ' . $firstRow['sname']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($firstRow['email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($firstRow['address']); ?></p>
                                <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($firstRow['cellnumber']); ?></p>
                            </div>
                        </div>
                        <div class="order-section">
                            <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                            <div class="details-grid">
                                <p><strong>Gown:</strong> <?php echo htmlspecialchars($firstRow['gown_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($firstRow['service'])); ?></p>
                                <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['date_rented'])); ?></p>
                                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['duedate'])); ?></p>
                                <p class="total-price">
                                    <strong>Total:</strong> ₱<?php echo number_format($firstRow['total'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="accept-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'accepted')">
                                <i class="fas fa-check"></i> Confirm
                            </button>
                            <button class="decline-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Decline
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    endforeach;
    if ($currentEmail !== null) {
        echo '</div>';
    }
    ?>
</div>

<div class="tab-content" id="accepted-tab">
    <?php
    $currentEmail = null;
    $batchGroups = array();
    
    $result_all->data_seek(0);
    while ($row = $result_all->fetch_assoc()) {
        if ($row['request'] == 'accepted' && !$row['reservation']) {
            $key = $row['email'] . '-' . $row['batch'];
            if (!isset($batchGroups[$key])) {
                $batchGroups[$key] = array();
            }
            $batchGroups[$key][] = $row;
        }
    }

    foreach ($batchGroups as $group):
        $firstRow = $group[0];
        if ($currentEmail !== $firstRow['email']) {
            if ($currentEmail !== null) {
                echo '</div>';
            }
            $currentEmail = $firstRow['email'];
            echo '<div class="gown-group">';
            echo '<h2>' . htmlspecialchars($currentEmail) . '</h2>';
        }
        
        if ($firstRow['batch']):
            echo '<div class="batch-group">';
            echo '<h3>Batch Order for ' . htmlspecialchars($firstRow['email']) . '</h3>';
            
            foreach ($group as $row):
    ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="order-id">Order #<?php echo $row['id']; ?></div>
                        <div class="status-badge status-<?php echo strtolower($row['request']); ?>">
                            <p>Service</p>
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
                                    <p class="total-price">
                                        <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                        <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="batch-action-buttons">
                <button class="receive-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'received')">
                    <i class="fas fa-box-check"></i> Batch Order Received
                </button>
            </div>
            </div>
        <?php else: ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="order-id">Order #<?php echo $firstRow['id']; ?></div>
                    <div class="status-badge status-<?php echo strtolower($firstRow['request']); ?>">
                        <p>Service</p>
                    </div>
                </div>
                <div class="request-body">
                    <div class="product-image">
                        <?php
                        $images = @unserialize($firstRow['img']);
                        if ($images === false) {
                            $images = [$firstRow['img']];
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
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstRow['fname'] . ' ' . $firstRow['sname']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($firstRow['email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($firstRow['address']); ?></p>
                                <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($firstRow['cellnumber']); ?></p>
                            </div>
                        </div>
                        <div class="order-section">
                            <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                            <div class="details-grid">
                                <p><strong>Gown:</strong> <?php echo htmlspecialchars($firstRow['gown_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($firstRow['service'])); ?></p>
                                <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['date_rented'])); ?></p>
                                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['duedate'])); ?></p>
                                <p class="total-price">
                                    <strong>Total:</strong> ₱<?php echo number_format($firstRow['total'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="receive-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'received')">
                                <i class="fas fa-box-check"></i> Order Received
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    endforeach;
    if ($currentEmail !== null) {
        echo '</div>';
    }
    ?>
</div>

<div class="tab-content" id="received-tab">
    <?php
    $currentEmail = null;
    $batchGroups = array();
    
    $result_all->data_seek(0);
    while ($row = $result_all->fetch_assoc()) {
        if ($row['request'] == 'received') {
            $key = $row['email'] . '-' . $row['batch'];
            if (!isset($batchGroups[$key])) {
                $batchGroups[$key] = array();
            }
            $batchGroups[$key][] = $row;
        }
    }

    foreach ($batchGroups as $group):
        $firstRow = $group[0];
        if ($currentEmail !== $firstRow['email']) {
            if ($currentEmail !== null) {
                echo '</div>';
            }
            $currentEmail = $firstRow['email'];
            echo '<div class="gown-group">';
            echo '<h2>' . htmlspecialchars($currentEmail) . '</h2>';
        }
        
        if ($firstRow['batch']):
            echo '<div class="batch-group">';
            echo '<h3>Batch Order for ' . htmlspecialchars($firstRow['email']) . '</h3>';
            
            foreach ($group as $row):
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
                                    <p class="total-price">
                                        <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                        <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="batch-action-buttons">
                <button class="receive-btn" onclick="updateStatus([<?php echo implode(',', array_map(function($r) { return $r['id']; }, $group)); ?>], 'returned')">
                    <i class="fas fa-undo"></i> Return Batch Gowns
                </button>
            </div>
            </div>
        <?php else: ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="order-id">Order #<?php echo $firstRow['id']; ?></div>
                    <div class="status-badge status-received">
                        <?php echo ucfirst($firstRow['request']); ?>
                    </div>
                </div>
                <div class="request-body">
                    <div class="product-image">
                        <?php
                        $images = @unserialize($firstRow['img']);
                        if ($images === false) {
                            $images = [$firstRow['img']];
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
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstRow['fname'] . ' ' . $firstRow['sname']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($firstRow['email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($firstRow['address']); ?></p>
                                <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($firstRow['cellnumber']); ?></p>
                            </div>
                        </div>
                        <div class="order-section">
                            <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                            <div class="details-grid">
                                <p><strong>Gown:</strong> <?php echo htmlspecialchars($firstRow['gown_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($firstRow['service'])); ?></p>
                                <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['date_rented'])); ?></p>
                                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['duedate'])); ?></p>
                                <p class="total-price">
                                    <strong>Total:</strong> ₱<?php echo number_format($firstRow['total'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="receive-btn" onclick="updateStatus(<?php echo $firstRow['id']; ?>, 'returned')">
                                <i class="fas fa-undo"></i> Return Gown
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    endforeach;
    if ($currentEmail !== null) {
        echo '</div>';
    }
    ?>
</div>

<div class="tab-content" id="returned-tab">
    <?php
    $currentEmail = null;
    $batchGroups = array();
    
    $result_all->data_seek(0);
    while ($row = $result_all->fetch_assoc()) {
        if ($row['request'] == 'returned') {
            $key = $row['email'] . '-' . $row['batch'];
            if (!isset($batchGroups[$key])) {
                $batchGroups[$key] = array();
            }
            $batchGroups[$key][] = $row;
        }
    }

    foreach ($batchGroups as $group):
        $firstRow = $group[0];
        if ($currentEmail !== $firstRow['email']) {
            if ($currentEmail !== null) {
                echo '</div>';
            }
            $currentEmail = $firstRow['email'];
            echo '<div class="gown-group">';
            echo '<h2>' . htmlspecialchars($currentEmail) . '</h2>';
        }
        
        if ($firstRow['batch']):
            echo '<div class="batch-group">';
            echo '<h3>Batch Order for ' . htmlspecialchars($firstRow['email']) . '</h3>';
            
            foreach ($group as $row):
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
                                    <p class="total-price">
                                        <strong>Total:</strong> ₱<?php echo number_format($row['total'], 2); ?>
                                        <?php echo ($row['batch'] == true) ? ' (Batch)' : ''; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="request-card">
                <div class="request-header">
                    <div class="order-id">Order #<?php echo $firstRow['id']; ?></div>
                    <div class="status-badge status-returned">
                        <?php echo ucfirst($firstRow['request']); ?>
                    </div>
                </div>
                <div class="request-body">
                    <div class="product-image">
                        <?php
                        $images = @unserialize($firstRow['img']);
                        if ($images === false) {
                            $images = [$firstRow['img']];
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
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($firstRow['fname'] . ' ' . $firstRow['sname']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($firstRow['email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($firstRow['address']); ?></p>
                                <p><strong>Cell Number:</strong> <?php echo htmlspecialchars($firstRow['cellnumber']); ?></p>
                            </div>
                        </div>
                        <div class="order-section">
                            <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                            <div class="details-grid">
                                <p><strong>Gown:</strong> <?php echo htmlspecialchars($firstRow['gown_name']); ?></p>
                                <p><strong>Service:</strong> <?php echo htmlspecialchars(ucfirst($firstRow['service'])); ?></p>
                                <p><strong>Rent Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['date_rented'])); ?></p>
                                <p><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['duedate'])); ?></p>
                                <p><strong>Returned Date:</strong> <?php echo date('F d, Y', strtotime($firstRow['returned_date'])); ?></p>
                                <p class="total-price">
                                    <strong>Total:</strong> ₱<?php echo number_format($firstRow['total'], 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    endforeach;
    if ($currentEmail !== null) {
        echo '</div>';
    }
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
                // Replace your existing history handling code with this:
                let currentIndex = 0;
                let histories = [window.location.href];

                window.onpopstate = function(event) {
                    if (event.state) {
                        // Navigate based on direction
                        if (event.state.index < currentIndex) {
                            // Going back
                            window.location.href = event.state.url;
                        } else {
                            // Going forward
                            window.location.href = event.state.url;
                        }
                        currentIndex = event.state.index;
                    }
                }; 

                // Push initial state
                history.replaceState({
                    index: currentIndex,
                    url: window.location.href
                }, '', window.location.href);

                // Handle links with proper history tracking
                document.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Don't handle external links or # links
                        if (this.hostname !== window.location.hostname || this.getAttribute('href') === '#') {
                            return;
                        }

                        e.preventDefault();
                        currentIndex++;
                        const newUrl = this.href;
                        histories.push(newUrl);

                        // Push new state
                        history.pushState({
                            index: currentIndex,
                            url: newUrl
                        }, '', newUrl);

                        // Navigate to new page
                        window.location.href = newUrl;
                    });
                });
                document.addEventListener('DOMContentLoaded', function() {
                    const tabs = document.querySelectorAll('.tab-btn');

                    tabs.forEach(tab => {
                        tab.addEventListener('click', () => {

                            tabs.forEach(t => t.classList.remove('active'));


                            tab.classList.add('active');


                            document.querySelectorAll('.tab-content').forEach(content => {
                                content.classList.remove('active');
                            });


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

    function updateStatus(ids, status) {
    if (!Array.isArray(ids)) {
        ids = [ids];
    }

    if (status === 'cancelled') {
        const declineModal = document.getElementById('declineModal');
        const reasonSelect = document.getElementById('declineReason');
        const otherReason = document.getElementById('otherReason');
        const submitBtn = document.getElementById('submitDecline');

        declineModal.style.display = 'block';

        submitBtn.onclick = function() {
            const reason = reasonSelect.value === 'Other' ? otherReason.value : reasonSelect.value;
            if (!reason) {
                alert('Please select or specify a reason');
                return;
            }

            Promise.all(ids.map(id => {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('id', id);
                formData.append('status', status);
                formData.append('reason', reason);

                return fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            })).then(results => {
                if (results.every(data => data.success)) {
                    location.reload();
                } else {
                    alert('Error updating some orders');
                }
            });

            declineModal.style.display = 'none';
        };
    } else {
        const modal = document.getElementById('confirmModal');
        const message = document.getElementById('confirmMessage');
        const yesBtn = document.getElementById('confirmYes');
        
        message.textContent = 'Are you sure you want to ' + 
            (status === 'payment' ? 'accept' : status) + 
            (ids.length > 1 ? ' these requests?' : ' this request?');
        
        modal.style.display = 'block';

        yesBtn.onclick = function() {
            Promise.all(ids.map(id => {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('id', id);
                formData.append('status', status);

                return fetch(window.location.href, {
                    method: 'POST', 
                    body: formData
                }).then(response => response.json());
            })).then(results => {
                if (results.every(data => data.success)) {
                    location.reload();
                } else {
                    alert('Error updating some orders');
                }
            });

            modal.style.display = 'none';
        };
    }
}
            </script>
</body>

</html>