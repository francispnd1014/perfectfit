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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $email = $_SESSION['email'];

    $query = "DELETE FROM rent WHERE id='$id' AND email='$email'";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Order cancelled successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error cancelling order: ' . $conn->error]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_declined'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $email = $_SESSION['email'];

    $query = "DELETE FROM rent WHERE id='$id' AND email='$email' AND request='declined'";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Declined gown deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting declined gown: ' . $conn->error]);
    }
    exit();
}

$email = $_SESSION['email'];


$query = "SELECT fname, sname, pfp FROM users WHERE email='$email'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullname = $row['fname'] . ' ' . $row['sname'];
    $profile_picture = $row['pfp'];
    $fname = $row['fname'];
    $_SESSION['fullname'] = $fullname;
    $_SESSION['profile_picture'] = $profile_picture;
} else {
    header("Location: Login.php");
    exit();
}


$fav_query = "SELECT gown_name FROM favorite WHERE email='$email'";
$fav_result = $conn->query($fav_query);

$gown_images = [];
if ($fav_result->num_rows > 0) {
    while ($fav_row = $fav_result->fetch_assoc()) {
        $gown_names = explode(',', $fav_row['gown_name']);
        foreach ($gown_names as $gown_name) {
            $gown_name = trim($gown_name);
            $product_query = "SELECT id, img FROM product WHERE name='$gown_name'";
            $product_result = $conn->query($product_query);
            if ($product_result->num_rows > 0) {
                while ($product_row = $product_result->fetch_assoc()) {
                    $gown_images[] = $product_row;
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    $rent_id = $_POST['rent_id'];
    $sql = "UPDATE rent SET request = 'accepted' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$rent_id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$result]);
    exit;
}

$rent_query = "SELECT id, gownname_rented, request, date_rented, duedate, returned_date, total, address, reason, service FROM rent WHERE email='$email'";
$rent_result = $conn->query($rent_query);

$rented_gown_images = [];
if ($rent_result->num_rows > 0) {
    while ($rent_row = $rent_result->fetch_assoc()) {
        $rented_gown_names = explode(',', $rent_row['gownname_rented']);
        foreach ($rented_gown_names as $rented_gown_name) {
            $rented_gown_name = trim($rented_gown_name);
            $product_query = "SELECT id, img, price FROM product WHERE name='$rented_gown_name'";
            $product_result = $conn->query($product_query);
            if ($product_result->num_rows > 0) {
                while ($product_row = $product_result->fetch_assoc()) {
                    $product_row['request'] = $rent_row['request'];
                    $product_row['service'] = $rent_row['service'];
                    $product_row['date_rented'] = $rent_row['date_rented'];
                    $product_row['duedate'] = $rent_row['duedate'];
                    $product_row['returned_date'] = $rent_row['returned_date'];
                    $product_row['total'] = $rent_row['total'];
                    $product_row['address'] = $rent_row['address'];
                    $product_row['reason'] = $rent_row['reason'];
                    $product_row['rent_id'] = $rent_row['id'];
                    $rented_gown_images[] = $product_row;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($fullname); ?></title>
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <link rel="stylesheet" href="../CSS/Account.css">
    <script src="https://kit.fontawesome.com/a4c2475e10.js"></script>
</head>

<body>
    <div class="banner">
        <div class="navbar">
            <a href="../HTML/Home User.php"><img src="../IMAGES/RICH SABINIANS.png" class="logo">
                <ul>
                    <li><a href="../HTML/Home User.php">Home</a></li>
                    <li><a href="../HTML/Shop User.php">Shop</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropbtn" onclick="toggleDropdown()"> <?php echo htmlspecialchars($fname); ?></a>
                        <div id="myDropdown" class="dropdown-content">
                            <div class="sub-menu">
                                <div class="user-info">
                                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" width="50" height="50">
                                    <h3><?php echo htmlspecialchars($fullname); ?></h3>
                                </div>
                                <a href="../HTML/Account.php" class="sub-menu-link">
                                    <p>Profile</p>
                                </a>
                                <a href="Logout.php" class="sub-menu-link">
                                    <p>Log Out</p>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
        </div>
        <main class="bodies">
            <div class="header-wrapper">
                <header></header>
                <div class="cols-container">
                    <div class="left-col">
                        <img class="pfp" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="<?php echo htmlspecialchars($fullname); ?>" />
                        <h2><?php echo htmlspecialchars($fullname); ?></h2>
                        <p><?php echo htmlspecialchars($email); ?></p>
                        <a href="Profile.php"><button class="edit-btn">Edit Profile</button></a>
                    </div>

                    <div class="right-col">
                        <nav>
                            <ul>
                                <li><a href="#favorite" onclick="showSection('favorite')">Favorite</a></li>
                                <!-- <li><a href="#renting" onclick="showSection('renting')">Renting</a></li> -->
                                <li><a href="#pending" onclick="showSection('pending')">Pending</a></li>
                                <li><a href="#payment" onclick="showSection('payment')">Payment</a></li>
                                <li><a href="#service" onclick="showSection('service')">Service</a></li>
                                <li><a href="#received" onclick="showSection('received')">Received</a></li>
                                <li><a href="#history" onclick="showSection('history')">History</a></li>
                            </ul>
                        </nav>

                        <div id="favorite" class="photos active">
                            <?php foreach ($gown_images as $gown): ?>
                                <a href="Preview.php?id=<?php echo urlencode($gown['id']); ?>" class="card-link">
                                    <div class="card">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($gown['img']);
                                            if ($images === false && $gown['img'] !== 'b:0;') {
                                                $images = [$gown['img']];
                                            }

                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div id="pending" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] == 'pending'): ?>
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }
                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Pending Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <div class="overlay">
                                            Request Pending
                                            <div class="service-type">
                                                Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                            </div>
                                            <div class="service-type">
                                                Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                            </div>
                                            <button class="cancel-order-btn" data-id="<?php echo htmlspecialchars($rented_gown['rent_id']); ?>">Cancel Order</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div id="payment" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] == 'payment'): ?>
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }
                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Payment Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <div class="overlay payment-pending">
                                            Payment Pending
                                            <div class="service-type">
                                                Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                            </div>
                                            <div class="service-type">
                                                Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                            </div>
                                            <button class="pay-order-btn" onclick="confirmPayment(<?php echo htmlspecialchars($rented_gown['rent_id']); ?>)" data-id="<?php echo htmlspecialchars($rented_gown['rent_id']); ?>">Settle Payment</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div id="received" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] == 'received'): ?>
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }
                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Received Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <div class="overlay received">
                                            Received
                                            <div class="service-type">
                                                Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                            </div>
                                            <div class="service-type">
                                                Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div id="service" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] == 'accepted'): ?>
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }
                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Service Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <div class="overlay accepted">
                                            Request Accepted
                                            <div class="service-type">
                                                Service: <?php echo htmlspecialchars(ucfirst($rented_gown['service'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                            </div>
                                            <div class="service-type">
                                                Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div id="renting" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] != 'returned'): ?> <!-- Add this condition -->
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }

                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Rented Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <?php if ($rented_gown['request'] == 'pending'): ?>
                                            <div class="overlay">
                                                Request Pending
                                                <button class="cancel-order-btn" data-id="<?php echo htmlspecialchars($rented_gown['rent_id']); ?>">Cancel Order</button>
                                            </div>
                                        <?php elseif ($rented_gown['request'] == 'accepted'): ?>
                                            <div class="overlay accepted">
                                                Request Accepted
                                                <div class="service-type">
                                                    Service: <?php echo htmlspecialchars(ucfirst($rented_gown['service'])); ?> </div>
                                                <div class="service-type">
                                                    Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                                </div>
                                                <div class="service-type">
                                                    Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                                </div>
                                            </div>
                                        <?php elseif ($rented_gown['request'] == 'payment'): ?>
                                            <div class="overlay payment-pending">
                                                Payment Pending
                                                <div class="service-type">
                                                    Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                                </div>
                                                <div class="service-type">
                                                    Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                                </div>
                                                <button class="pay-order-btn"
                                                    onclick="confirmPayment(<?php echo htmlspecialchars($rented_gown['rent_id']); ?>)"
                                                    data-id="<?php echo htmlspecialchars($rented_gown['rent_id']); ?>">
                                                    Settle Payment
                                                </button>
                                            </div>
                                        <?php elseif ($rented_gown['request'] == 'received'): ?>
                                            <div class="overlay received">
                                                Received
                                                <div class="service-type">
                                                    Deliver: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Return: <?php echo date('F d, Y', strtotime($rented_gown['duedate'])); ?>
                                                </div>
                                                <div class="service-type">
                                                    Address: <?php echo htmlspecialchars($rented_gown['address']); ?>
                                                </div>
                                                <div class="service-type">
                                                    Total: ₱ <?php echo number_format($rented_gown['total'], 2); ?>
                                                </div>
                                            </div>
                                        <?php elseif ($rented_gown['request'] == 'declined'): ?>
                                            <div class="overlay declined">Request Declined
                                                <div class="service-type">
                                                    Reason: <?php echo htmlspecialchars($rented_gown['reason']); ?>
                                                </div>
                                                <button class="cancel-order-btn" data-id="<?php echo htmlspecialchars($rented_gown['rent_id']); ?>">I Understand</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div id="history" class="photos hidden">
                            <?php foreach ($rented_gown_images as $rented_gown): ?>
                                <?php if ($rented_gown['request'] == 'returned'): ?>
                                    <div class="image-container">
                                        <div class="image">
                                            <?php
                                            $images = @unserialize($rented_gown['img']);
                                            if ($images === false && $rented_gown['img'] !== 'b:0;') {
                                                $images = [$rented_gown['img']];
                                            }
                                            if (!empty($images)) {
                                                $image = $images[0];
                                                echo '<img class="grid-item" src="uploaded_img/' . htmlspecialchars($image) . '" alt="Returned Gown Image" />';
                                            }
                                            ?>
                                        </div>
                                        <div class="overlay returned">
                                            <div class="service-type">
                                                Service: <?php echo htmlspecialchars(ucfirst($rented_gown['service'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Rented: <?php echo date('F d, Y', strtotime($rented_gown['date_rented'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Returned: <?php echo date('F d, Y', strtotime($rented_gown['returned_date'])); ?>
                                            </div>
                                            <div class="service-type">
                                                Total: ₱<?php echo number_format($rented_gown['total'], 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <!-- Modal Structure -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Are you sure you want to cancel this order?</p>
            <button id="confirmYes" class="modal-btn">Yes</button>
            <button id="confirmNo" class="modal-btn">No</button>
        </div>
    </div>

    <!-- Add this HTML for the modal structure -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="payment-close">&times;</span>
            <h3>Payment Confirmation</h3>
            <p>Are you sure you want to confirm this payment?</p>
            <div class="modal-buttons">
                <button id="confirmPaymentYes" class="modal-btn">Yes</button>
                <button id="confirmPaymentNo" class="modal-btn">No</button>
            </div>
        </div>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content success">
            <h3>Success!</h3>
            <p>Payment confirmed successfully</p>
            <button id="successOk" class="modal-btn success-btn">OK</button>
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
        class ModalHandler {
            constructor() {
                this.selectedOrderId = null;
                this.initializeModals();
                this.attachEventListeners();
            }

            initializeModals() {
                this.confirmationModal = document.getElementById('confirmationModal');
                this.paymentModal = document.getElementById('paymentModal');
                this.successModal = document.getElementById('successModal');
            }

            attachEventListeners() {

                document.querySelectorAll('.cancel-order-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        this.selectedOrderId = button.getAttribute('data-id');
                        this.showModal(this.confirmationModal);
                    });
                });


                document.querySelectorAll('.pay-order-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.selectedOrderId = button.getAttribute('data-id');
                        this.showModal(this.paymentModal);
                    });
                });


                document.getElementById('confirmYes').addEventListener('click', () => this.handleCancelOrder());
                document.getElementById('confirmPaymentYes').addEventListener('click', () => this.handlePaymentConfirmation());
                document.getElementById('successOk').addEventListener('click', () => this.handleSuccess());


                document.querySelectorAll('.close, .payment-close, #confirmNo, #confirmPaymentNo').forEach(element => {
                    element.addEventListener('click', () => this.hideAllModals());
                });

                window.addEventListener('click', (event) => {
                    if (event.target.classList.contains('modal')) {
                        this.hideAllModals();
                    }
                });
            }

            showModal(modal) {
                this.hideAllModals();
                modal.style.display = 'block';
            }

            hideAllModals() {
                [this.confirmationModal, this.paymentModal, this.successModal].forEach(modal => {
                    if (modal) modal.style.display = 'none';
                });
            }

            async handleCancelOrder() {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            id: this.selectedOrderId,
                            cancel_order: true
                        })
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order.');
                }
            }

            async handlePaymentConfirmation() {
                try {
                    const formData = new FormData();
                    formData.append('action', 'confirm_payment');
                    formData.append('rent_id', this.selectedOrderId);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    const data = await response.json();

                    this.hideAllModals();
                    this.showModal(this.successModal);
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error processing payment confirmation');
                }
            }

            handleSuccess() {
                this.hideAllModals();
                window.location.reload();
            }
        }


        document.addEventListener('DOMContentLoaded', () => {
            const modalHandler = new ModalHandler();
        });

        function confirmPayment(rentId) {
            const modal = document.getElementById('paymentModal');
            const successModal = document.getElementById('successModal');
            const closeBtn = document.getElementsByClassName('payment-close')[0];
            const confirmBtn = document.getElementById('confirmPaymentYes');
            const cancelBtn = document.getElementById('confirmPaymentNo');
            const successOk = document.getElementById('successOk');

            modal.style.display = 'block';

            closeBtn.onclick = function() {
                modal.style.display = 'none';
            }

            cancelBtn.onclick = function() {
                modal.style.display = 'none';
            }

            confirmBtn.onclick = function() {
                const formData = new FormData();
                formData.append('action', 'confirm_payment');
                formData.append('rent_id', rentId);

                fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        modal.style.display = 'none';
                        successModal.style.display = 'block';

                        successOk.onclick = function() {
                            successModal.style.display = 'none';
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modal.style.display = 'none';
                        alert('Error processing payment confirmation');
                    });
            }


            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
                if (event.target == successModal) {
                    successModal.style.display = 'none';
                    window.location.reload();
                }
            }
        }


        document.addEventListener('DOMContentLoaded', () => {

            document.querySelectorAll('.pay-order-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const rentId = button.getAttribute('data-id');
                    confirmPayment(rentId);
                });
            });
        });

        function toggleDropdown() {
            var dropdown = document.getElementById("myDropdown");
            dropdown.classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        function showSection(sectionId) {
            const sections = document.querySelectorAll('.photos');
            const listItems = document.querySelectorAll('nav ul li a');


            sections.forEach(section => {
                section.classList.add('hidden');
                section.classList.remove('active');
                section.classList.remove('fade-in');
                section.classList.add('fade-out');
            });

            listItems.forEach(item => {
                item.classList.remove('bold');
            });


            const selectedSection = document.getElementById(sectionId);
            selectedSection.classList.remove('hidden');
            selectedSection.classList.remove('fade-out');
            selectedSection.classList.add('fade-in');
            selectedSection.classList.add('active');


            const clickedItem = document.querySelector(`nav ul li a[href="#${sectionId}"]`);
            clickedItem.classList.add('bold');
        }


        document.addEventListener('DOMContentLoaded', () => {
            showSection('favorite');
            document.querySelector('nav ul li a[href="#favorite"]').classList.add('bold');
        });

        document.addEventListener('DOMContentLoaded', () => {
            let selectedOrderId = null;

            document.querySelectorAll('.cancel-order-btn').forEach(button => {
                button.addEventListener('click', function() {
                    selectedOrderId = this.getAttribute('data-id');
                    document.getElementById('confirmationModal').style.display = 'block';
                });
            });

            document.querySelectorAll('.okay-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const rentId = this.getAttribute('data-id');
                    fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                id: rentId,
                                delete_declined: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the declined gown.');
                        });
                });
            });
            document.getElementById('confirmYes').addEventListener('click', function() {
                if (selectedOrderId) {
                    fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                id: selectedOrderId,
                                cancel_order: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while cancelling the order.');
                        });
                }
            });

            document.getElementById('confirmNo').addEventListener('click', function() {
                document.getElementById('confirmationModal').style.display = 'none';
            });

            document.querySelector('.close').addEventListener('click', function() {
                document.getElementById('confirmationModal').style.display = 'none';
            });

            window.onclick = function(event) {
                if (event.target == document.getElementById('confirmationModal')) {
                    document.getElementById('confirmationModal').style.display = 'none';
                }
            };
        });
    </script>
</body>

</html>