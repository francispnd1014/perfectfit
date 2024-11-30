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


$email = $_SESSION['email'];
$sql = "SELECT contact FROM users WHERE email='$email'";
$result = $conn->query($sql);
$user_data = $result->fetch_assoc();
$contact_number = $user_data['contact'];
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


$gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$query = "SELECT * FROM product WHERE id = $gown_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $gown = $result->fetch_assoc();
    $gown_image = $gown['img'];
    $gown_rent = $gown['price'];
    $gown_name = $gown['name'];
    $gown_status = $gown['status'];
} else {
    echo "Gown not found.";
    exit();
}


function getNextAvailableDate($conn, $gown_name)
{

    $query = "SELECT duedate, reservation FROM rent 
              WHERE gownname_rented = ? AND request = 'accepted'
              ORDER BY duedate DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $gown_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['reservation']) {

            $date = new DateTime();
            $date->modify('+4 days');
            return $date->format('Y-m-d');
        } else {

            $duedate = new DateTime($row['duedate']);
            $duedate->modify('+1 week');
            return $duedate->format('Y-m-d');
        }
    }


    $date = new DateTime();
    $date->modify('+4 days');
    return $date->format('Y-m-d');
}


$isFavorited = false;
$stmt = $conn->prepare("SELECT gown_name FROM favorite WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($existing_gown_names);
$stmt->fetch();

if ($stmt->num_rows > 0) {
    $gown_names_array = explode(', ', $existing_gown_names);
    if (in_array($gown_name, $gown_names_array)) {
        $isFavorited = true;
    }
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['favorite'])) {

    $stmt = $conn->prepare("SELECT gown_name FROM favorite WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($existing_gown_names);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {

        $gown_names_array = explode(', ', $existing_gown_names);
        if (in_array($gown_name, $gown_names_array)) {

            $gown_names_array = array_diff($gown_names_array, [$gown_name]);
            $updated_gown_names = implode(', ', $gown_names_array);
            $stmt->close();
            $stmt = $conn->prepare("UPDATE favorite SET gown_name = ? WHERE email = ?");
            $stmt->bind_param("ss", $updated_gown_names, $email);
            $stmt->execute();
            $isFavorited = false;
        } else {

            $gown_names_array[] = $gown_name;
            $updated_gown_names = implode(', ', $gown_names_array);
            $stmt->close();
            $stmt = $conn->prepare("UPDATE favorite SET gown_name = ? WHERE email = ?");
            $stmt->bind_param("ss", $updated_gown_names, $email);
            $stmt->execute();
            $isFavorited = true;
        }
    } else {

        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO favorite (email, gown_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $gown_name);
        $stmt->execute();
        $isFavorited = true;
    }
    $stmt->close();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
// In the rent gown section, modify the code like this:
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rent_gown']) && isset($_POST['gown_ids'])) {
        $deliveryDate = $_POST['date_rented'];
        $returnDate = $_POST['duedate'];
        $cellnumber = $_POST['cellnumber'];
        $deliveryAddress = $_POST['address'];
        $fullName = $_SESSION['fullname'];
        $service = $_POST['service'];
        $total = floatval(str_replace(',', '', $_POST['total_price']));
        
        // Set batch based on number of gowns
        $batch = (count($_POST['gown_ids']) > 1) ? 1 : 0;
    
        foreach ($_POST['gown_ids'] as $gown_id) {
            $stmt = $conn->prepare("SELECT name FROM product WHERE id = ?");
            $stmt->bind_param("i", $gown_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $gown = $result->fetch_assoc();
    
            // Modified INSERT query to include batch as boolean
            $stmt = $conn->prepare("INSERT INTO rent (email, gownname_rented, date_rented, cellnumber, duedate, address, service, batch, total, request, reservation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0)");
            $stmt->bind_param("sssssssid", $email, $gown['name'], $deliveryDate, $cellnumber, $returnDate, $deliveryAddress, $service, $batch, $total);
            $stmt->execute();
        }
    
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('rentModal').style.display = 'none';
                document.getElementById('successModal').style.display = 'block';
            });
        </script>";
    }

$isMultiRent = isset($_GET['multi']) && $_GET['multi'] === 'true';
$gowns = [];
$total_base_price = 0;

if ($isMultiRent) {
    $gown_ids = explode(',', $_GET['id']);
    $placeholders = str_repeat('?,', count($gown_ids) - 1) . '?';
    $query = "SELECT * FROM product WHERE id IN ($placeholders)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('i', count($gown_ids)), ...$gown_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $gowns[] = $row;
    }
    $total_base_price = array_sum(array_column($gowns, 'price'));
} else {
    $gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $query = "SELECT * FROM product WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $gown_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $gown = $result->fetch_assoc();
        $gowns[] = $gown;
        $total_base_price = $gown['price'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reserve_gown'])) {
    $deliveryDate = $_POST['date_rented'];
    $returnDate = $_POST['duedate'];
    $deliveryAddress = $_POST['address'];
    $cellnumber = $_POST['cellnumber'];
    $service = $_POST['service'];
    $total = floatval(str_replace(',', '', $_POST['total_price']));
    $fullName = $_SESSION['fullname'];
    $email = $_SESSION['email'];
    $gownName = $gown_name;


    $stmt = $conn->prepare("INSERT INTO rent (email, gownname_rented, date_rented, duedate, cellnumber, address, service, total, request, reservation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'accepted', TRUE)");
    $stmt->bind_param("sssssssd", $email, $gownName, $deliveryDate, $returnDate, $cellnumber, $deliveryAddress, $service, $total);
    $stmt->execute();
    $stmt->close();


    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('reservationModal').style.display = 'none';
            document.getElementById('successModal').style.display = 'block';
        });
    </script>";
}
$query = "SELECT theme, size, analysis, tone, img, price, name, tally FROM product WHERE id = $gown_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $gown = $result->fetch_assoc();
    $gown_theme = $gown['theme'];
    $gown_size = $gown['size'];
    $gown_analysis = $gown['analysis'];
    $gown_tone = $gown['tone'];
    $gown_image = $gown['img'];
    $gown_rent = $gown['price'];
    $gown_name = $gown['name'];
    $gown_tally = $gown['tally'];
} else {
    echo "Gown not found.";
    exit();
}


function log_user_interaction($conn, $email, $gown_id, $interaction_type)
{
    $stmt = $conn->prepare("INSERT INTO user_interactions (email, gown_id, interaction_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $email, $gown_id, $interaction_type);
    $stmt->execute();
    $stmt->close();
}
$gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($gown_id) {
    log_user_interaction($conn, $email, $gown_id, 'view');
}


if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $select_query .= " AND (name LIKE '%$search%' OR size LIKE '%$search%' OR color LIKE '%$search%' OR theme LIKE '%$search%' OR analysis LIKE '%$search%' OR tone LIKE '%$search%')";
    log_user_interaction($conn, $email, 0, 'search');
}



$product_query = "SELECT * FROM product LIMIT 6";
$product_result = $conn->query($product_query);
$products = [];
if ($product_result->num_rows > 0) {
    while ($product_row = $product_result->fetch_assoc()) {
        $products[] = $product_row;
    }
}



$rental_status = null;
$stmt = $conn->prepare("SELECT request FROM rent WHERE gownname_rented = ? AND email = ?");
$stmt->bind_param("ss", $gown_name, $email);
$stmt->execute();
$stmt->bind_result($rental_status);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="font/css/all.min.css">
    <link rel="stylesheet" href="../CSS/Preview.css">
    <title><?php echo $gown_name; ?></title>
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
        <div class="content">
            <main>
                <div class="search-filter-bar">
                    <div class="search-container">
                        <div class="search-barA" id="search-barA">
                            <form action="Shop User.php" method="GET">
                                <input type="text" id="search-input" name="search" placeholder="Search for a gown...">
                                <button type="submit" id="search-button">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="left-column">
                        <div class="image-slider">
                            <?php
                            $images = @unserialize($gown_image);
                            if ($images === false && $gown_image !== 'b:0;') {
                                $images = [$gown_image];
                            }

                            if (!empty($images)) {
                                foreach ($images as $index => $image) {
                                    $activeClass = $index === 0 ? 'active' : '';
                                    echo '<img src="uploaded_img/' . htmlspecialchars($image) . '" alt="Gown Image" class="' . $activeClass . '">';
                                }
                            } else {
                                echo '<p>No image available.</p>';
                            }
                            ?>
                            <!-- Next and previous buttons -->
                            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                            <a class="next" onclick="plusSlides(1)">&#10095;</a>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="right-column">
                        <!-- Product Description -->
                        <div class="product-description">
                            <h1><?php echo htmlspecialchars($gown_name); ?></h1>
                            <?php if ($gown_status == 1): ?>
                                <p class="rent_status">Rented</p>
                                <?php

                                $rental_query = "SELECT date_rented, duedate FROM rent WHERE gownname_rented = ? AND request = 'accepted'";
                                $stmt = $conn->prepare($rental_query);
                                $stmt->bind_param("s", $gown_name);
                                $stmt->execute();
                                $rental_result = $stmt->get_result();
                                if ($rental_result->num_rows > 0) {
                                    $rental_details = $rental_result->fetch_assoc();
                                ?>
                                    <div class="rental-details">
                                        <?php
                                        $date_rented = new DateTime($rental_details['date_rented']);
                                        $duedate = new DateTime($rental_details['duedate']);
                                        ?>
                                        <p class="date">Date Rented: <?php echo htmlspecialchars($date_rented->format('F j, Y')); ?></p>
                                        <p class="date">Date of Return: <?php echo htmlspecialchars($duedate->format('F j, Y')); ?></p>
                                    </div>
                                <?php
                                }
                                $stmt->close();
                                ?>
                            <?php else: ?>
                                <?php if ($gown_tally == 0) { ?>
                                    <p class="tally_status">Brandnew</p>
                                <?php } else { ?>
                                    <p class="tally_status">Used</p>
                                <?php } ?>
                            <?php endif; ?>
                            <p class="prices">₱<?php echo number_format($gown_rent, 2); ?></p>
                            <div class="tags-container">
                                <div class="tags">
                                    <span class="tags-label">Theme:</span>
                                    <span class="tags-value"><?php echo htmlspecialchars($gown_theme); ?></span>
                                </div>
                                <div class="tags">
                                    <span class="tags-label">Size:</span>
                                    <span class="tags-value"><?php echo htmlspecialchars(str_replace(',', ' - ', $gown_size)); ?></span>
                                </div>
                                <div class="tags">
                                    <span class="tags-label">Analysis:</span>
                                    <span class="tags-value"><?php echo htmlspecialchars(str_replace(',', ' - ', $gown_analysis)); ?></span>
                                </div>
                                <div class="tags">
                                    <span class="tags-label">Tone:</span>
                                    <span class="tags-value"><?php echo htmlspecialchars(str_replace(',', ' - ', $gown_tone)); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Pricing -->
                        <div class="product-price">
                            <?php if ($rental_status == 'pending'): ?>
                                <button class="cart-btn" disabled>Pending</button>
                            <?php elseif ($rental_status == 'accepted'): ?>
                                <button class="cart-btn" disabled>Rented</button>
                            <?php elseif ($rental_status == 'payment'): ?>
                                <button class="cart-btn" disabled>Rented</button>
                            <?php elseif ($rental_status == 'received'): ?>
                                <button class="cart-btn" disabled>Rented</button>
                            <?php else: ?>
                                <a href="#" class="cart-btn rent-btn">Rent</a>
                                <a href="#" class="cart-btn reserve-btn">Reserve</a>
                            <?php endif; ?>
                            <form method="POST">
                                <button type="submit" name="favorite" class="heart-btn">
                                    <i class="fa fa-heart <?php echo $isFavorited ? '' : 'black'; ?>"></i>
                                </button>
                            </form>
                        </div>
                    </div>
            </main>
        </div>

        <?php

        function get_recommended_products($conn, $email)
        {

            $user_interactions_query = "
        SELECT gown_id, COUNT(*) as interaction_count
        FROM user_interactions
        WHERE email = ? AND gown_id NOT IN (SELECT id FROM product WHERE status = 1)
        GROUP BY gown_id
        ORDER BY interaction_count DESC
        LIMIT 10
    ";
            $stmt = $conn->prepare($user_interactions_query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user_interactions_result = $stmt->get_result();
            $user_interacted_gowns = [];
            while ($row = $user_interactions_result->fetch_assoc()) {
                $user_interacted_gowns[] = $row['gown_id'];
            }
            $stmt->close();


            $other_users_interactions_query = "
        SELECT gown_id, COUNT(*) as interaction_count
        FROM user_interactions
        WHERE email != ? AND gown_id IN (SELECT gown_id FROM user_interactions WHERE email = ?) AND gown_id NOT IN (SELECT id FROM product WHERE status = 1)
        GROUP BY gown_id
        ORDER BY interaction_count DESC
        LIMIT 10
    ";
            $stmt = $conn->prepare($other_users_interactions_query);
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            $other_users_interactions_result = $stmt->get_result();
            $recommended_gowns = [];
            while ($row = $other_users_interactions_result->fetch_assoc()) {
                $recommended_gowns[] = $row['gown_id'];
            }
            $stmt->close();


            $all_recommended_gowns = array_unique(array_merge($user_interacted_gowns, $recommended_gowns));
            shuffle($all_recommended_gowns);


            if (count($all_recommended_gowns) < 5) {
                $popular_gowns_query = "
            SELECT id
            FROM product
            WHERE status != 1
            ORDER BY RAND()
            LIMIT " . (5 - count($all_recommended_gowns)) . "
        ";
                $result = $conn->query($popular_gowns_query);
                while ($row = $result->fetch_assoc()) {
                    $all_recommended_gowns[] = $row['id'];
                }
            }


            return array_slice($all_recommended_gowns, 0, 5);
        }


        $recommended_gown_ids = get_recommended_products($conn, $email);
        $recommended_products = [];
        if (!empty($recommended_gown_ids)) {
            $recommended_gown_ids_str = implode(',', $recommended_gown_ids);
            $recommended_query = "SELECT * FROM product WHERE id IN ($recommended_gown_ids_str)";
            $recommended_result = $conn->query($recommended_query);
            while ($row = $recommended_result->fetch_assoc()) {
                $recommended_products[] = $row;
            }
        }


        if (!empty($recommended_products)) {
            echo '<h2 class="reco">Recommended Products</h2>';
            echo '<div class="product-list">';
            foreach ($recommended_products as $product) {
                echo '<a href="Preview.php?id=' . htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') . '" class="card-link">';
                echo '<div class="card">';
                if ($product['status'] == 1) {
                    echo '<div class="rented-overlay">';
                    $rental_query = "SELECT date_rented, duedate FROM rent WHERE gownname_rented = ? AND request = 'accepted'";
                    $stmt = $conn->prepare($rental_query);
                    $stmt->bind_param("s", $product['name']);
                    $stmt->execute();
                    $rental_result = $stmt->get_result();
                    if ($rental_result->num_rows > 0) {
                        $rental_details = $rental_result->fetch_assoc();
                        echo '<div class="rental-details small-font">';
                        echo '<p>Date Rented: ' . htmlspecialchars($rental_details['date_rented']) . '</p>';
                        echo '<p>Due Date: ' . htmlspecialchars($rental_details['duedate']) . '</p>';
                        echo '</div>';
                    }
                    $stmt->close();
                    echo '</div>';
                }
                echo '<div class="image">';
                $images = @unserialize($product['img']);
                if ($images === false && $product['img'] !== 'b:0;') {
                    $images = [$product['img']];
                }
                if (!empty($images)) {
                    $image = $images[0];
                    echo '<img src="uploaded_img/' . htmlspecialchars($image) . '" alt="" style="width: 200px; height: 250px;">';
                }
                echo '</div>';
                echo '<div class="caption">';
                echo '<p class="product_name ellipsis">' . htmlspecialchars($product['name']) . '</p>';
                if ($product['tally'] == 0) {
                    echo '<p class="tally_status">Brandnew</p>';
                } else {
                    echo '<p class="tally_status">Used</p>';
                }
                echo '<p class="price"><b>Rent: ₱' . number_format($product['price'], 2) . '</b></p>';
                echo '</div>';
                echo '</div>';
                echo '</a>';
            }
            echo '</div>';
        }
        ?>

        <!-- Add this modal structure before the closing </body> tag -->
        <div id="rentModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="modal-columns">
                    <form method="POST" class="rent-form">
                        <?php if ($isMultiRent): ?>
                            <h3>Selected Gowns:</h3>
                            <?php foreach ($gowns as $gown): ?>
                                <div class="gown-item">
                                    <p><?php echo htmlspecialchars($gown['name']); ?> - ₱<?php echo number_format($gown['price'], 2); ?></p>
                                    <input type="hidden" name="gown_ids[]" value="<?php echo $gown['id']; ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <h3><?php echo htmlspecialchars($gowns[0]['name']); ?></h3>
                            <input type="hidden" name="gown_ids[]" value="<?php echo $gowns[0]['id']; ?>">
                        <?php endif; ?>
                        <p class="notice">*Note: You can't cancel a batch rent. (Still experimental a feature this for demo only)</p>

                        <label for="date_rented">Date of Delivery:</label>
                        <input class="int-delivery" type="date" id="date_rented" name="date_rented" required
                            <?php
                            $nextAvailable = getNextAvailableDate($conn, $gown_name);
                            echo 'min="' . $nextAvailable . '"';
                            ?>>

                        <label for="duedate">Date of Return:</label>
                        <input class="int-delivery" type="date" id="duedate" name="duedate" required>

                        <label for="address">Delivery Address:</label>
                        <input class="int-delivery" type="text" id="address" name="address" required>

                        <label for="cellnumber">Cellphone Number:</label>
                        <input class="int-delivery" type="text" id="cellnumber" name="cellnumber"
                            value="<?php echo htmlspecialchars($contact_number); ?>" required maxlength="11" pattern="\d{11}">

                        <label for="service">Service:</label>
                        <select class="int-delivery" id="service" name="service" required onchange="updateServiceFee()">
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Pickup</option>
                        </select>

                        <div class="price-details">
                            <p>Total Rent Price: <span class="price">₱<?php echo number_format($total_base_price, 2); ?></span></p>
                            <p>Service Fee: <span class="price" id="service-fee">₱200.00</span></p>
                            <p>Additional Fee (Deposit): <span class="price">₱<?php echo number_format(2000 * count($gowns), 2); ?></span></p>
                            <p>Total (COD): <span class="price" id="total-price"></span></p>
                        </div>
                        <input type="hidden" id="total-price-hidden" name="total_price">
                        <button class="btn-delivery" type="submit" name="rent_gown">Rent Now</button>
                        <p class="notice">*Note: Online payment is not yet available. Please pay upon delivery.</p>
                    </form>
                </div>
            </div>
        </div>
        <div id="reservationModal" class="modal">
            <div class="modal-content">
                <span class="close-reservation">&times;</span>
                <div class="modal-columns">
                    <form method="POST" class="rent-form">
                        <label for="reservation_date">Date of Reservation:</label>
                        <input class="int-delivery" type="date" id="reservation_date" name="date_rented" required>
                        <div class="availability-info">
                            <p class="notice">Reservation must be at least 2 months from today</p>
                        </div>

                        <label for="reservation_return">Date of Return:</label>
                        <input class="int-delivery" type="date" id="reservation_return" name="duedate" required>

                        <label for="reservation_address">Delivery Address:</label>
                        <input class="int-delivery" type="text" id="reservation_address" name="address" required>

                        <label for="address">Cellphone Number:</label>
                        <input class="int-delivery" type="text" id="cellnumber" name="cellnumber" value="<?php echo htmlspecialchars($contact_number); ?>" required maxlength="11" pattern="\d{11}" required>

                        <label for="reservation_service">Service:</label>
                        <select class="int-delivery" id="reservation_service" name="service" required onchange="updateReservationFee()">
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Pickup</option>
                        </select>

                        <div class="price-details">
                            <p>Rent Price: <span class="price">₱<?php echo number_format($gown_rent, 2, '.', ','); ?></span></p>
                            <p>Service Fee: <span class="price" id="reservation-service-fee">₱200.00</span></p>
                            <p>Additional Fee (Deposit): <span class="price">₱2,000.00</span></p>
                            <p>Total (COD): <span class="price" id="reservation-total-price">₱<?php echo number_format($gown_rent + 200 + 2000, 2, '.', ','); ?></span></p>
                        </div>
                        <input type="hidden" name="reservation" value="true">
                        <input type="hidden" id="reservation-total-price-hidden" name="total_price" value="<?php echo number_format($gown_rent + 200 + 2000, 2, '.', ','); ?>">
                        <button class="btn-delivery" type="submit" name="reserve_gown">Reserve Now</button>
                        <p class="notice">*Note: Online payment is not yet available. Please pay upon delivery.</p>
                    </form>
                </div>
            </div>
        </div>
        <div id="successModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p class="success">Successfully requested!</p>
                <div class="details-container">
                    <span class="details-label">Full Name:</span>
                    <span class="details-value"><?php echo isset($fullName) ? htmlspecialchars($fullName) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Email:</span>
                    <span class="details-value"><?php echo isset($email) ? htmlspecialchars($email) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Cellphone Number:</span>
                    <span class="details-value"><?php echo isset($cellnumber) ? htmlspecialchars($cellnumber) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Date of Delivery:</span>
                    <span class="details-value">
                        <?php
                        if (isset($deliveryDate)) {
                            $date = new DateTime($deliveryDate);
                            echo $date->format('F j, Y');
                        }
                        ?>
                    </span>
                </div>
                <div class="details-container">
                    <span class="details-label">Date of Return:</span>
                    <span class="details-value">
                        <?php
                        if (isset($returnDate)) {
                            $date = new DateTime($returnDate);
                            echo $date->format('F j, Y');
                        }
                        ?>
                    </span>
                </div>
                <div class="details-container">
                    <span class="details-label">Delivery Address:</span>
                    <span class="details-value"><?php echo isset($deliveryAddress) ? htmlspecialchars($deliveryAddress) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Service:</span>
                    <span class="details-value"><?php echo isset($service) ? htmlspecialchars($service) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Total:</span>
                    <span class="details-value"><?php echo isset($total) ? '₱' . number_format($total, 2, '.', ',') : ''; ?></span>
                </div>
                <button class="btn-confirm" id="goBackButton">Go back to shop</button>
            </div>
        </div>
        <div id="confirmModal" class="modal">
    <div class="modal-content">
        <h3>Confirmation</h3>
        <p>Do you want to continue?</p>
        <div class="confirm-buttons">
            <button id="confirmYes" class="btn-delivery">Yes</button>
            <button id="confirmNo" class="btn-delivery">No</button>
        </div>
    </div>
</div>

        <script>
            // Get the confirmation modal
const confirmModal = document.getElementById("confirmModal");
const confirmYes = document.getElementById("confirmYes");
const confirmNo = document.getElementById("confirmNo");
let currentForm = null;

// Modify rent button click handler
if (rentBtn) {
    rentBtn.onclick = function(e) {
        e.preventDefault();
        confirmModal.style.display = "block";
        currentForm = 'rent';
    }
}

// Modify reserve button click handler
if (document.querySelector(".reserve-btn")) {
    document.querySelector(".reserve-btn").onclick = function(e) {
        e.preventDefault();
        confirmModal.style.display = "block";
        currentForm = 'reserve';
    }
}

// Handle confirmation
confirmYes.onclick = function() {
    confirmModal.style.display = "none";
    if (currentForm === 'rent') {
        rentModal.style.display = "block";
    } else if (currentForm === 'reserve') {
        reservationModal.style.display = "block";
        // Set minimum date for reservation
        var today = new Date();
        today.setMonth(today.getMonth() + 2);
        today.setDate(today.getDate() + 1);
        var minDate = today.toISOString().split('T')[0];
        document.getElementById('reservation_date').min = minDate;
        document.getElementById('reservation_return').min = minDate;
    }
}

confirmNo.onclick = function() {
    confirmModal.style.display = "none";
}

// Close confirmation modal when clicking outside
window.onclick = function(event) {
    if (event.target == confirmModal) {
        confirmModal.style.display = "none";
    }
    if (event.target == rentModal) {
        rentModal.style.display = "none";
    }
    if (event.target == successModal) {
        successModal.style.display = "none";
    }
    if (event.target == reservationModal) {
        reservationModal.style.display = "none";
    }
}
            function updateServiceFee() {
                const serviceSelect = document.getElementById("service");
                const serviceFee = serviceSelect.value === "pickup" ? 0 : 200;
                const basePrice = <?php echo $total_base_price; ?>; // This now contains sum of all gown prices
                const numGowns = <?php echo count($gowns); ?>;
                const deposit = 2000 * numGowns; // Deposit per gown

                // Update service fee display
                document.getElementById("service-fee").textContent =
                    '₱' + serviceFee.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

                // Calculate total including all gowns
                const totalPrice = basePrice + serviceFee + deposit;

                // Update total price display
                document.getElementById("total-price").textContent =
                    '₱' + totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                document.getElementById("total-price-hidden").value = totalPrice.toFixed(2);
            }

            // Call updateServiceFee when the page loads and when service type changes
            document.addEventListener('DOMContentLoaded', function() {
                updateServiceFee();

                // Add event listener for service type changes
                document.getElementById("service").addEventListener('change', updateServiceFee);

                // Date validation
                const dateRented = document.getElementById('date_rented');
                const duedate = document.getElementById('duedate');

                dateRented.addEventListener('change', function() {
                    duedate.min = this.value;
                });
            });
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
                var reservationModal = document.getElementById("reservationModal");
                var reserveBtn = document.querySelector(".reserve-btn");
                var spanCloseReservation = document.getElementsByClassName("close-reservation")[0];

                reserveBtn.onclick = function() {
                    reservationModal.style.display = "block";


                    var today = new Date();
                    today.setMonth(today.getMonth() + 2);
                    today.setDate(today.getDate() + 1);
                    var minDate = today.toISOString().split('T')[0];
                    document.getElementById('reservation_date').min = minDate;
                    document.getElementById('reservation_return').min = minDate;
                }

                spanCloseReservation.onclick = function() {
                    reservationModal.style.display = "none";
                }

                function updateReservationFee() {
                    var serviceSelect = document.getElementById("reservation_service");
                    var serviceFee = serviceSelect.value === "pickup" ? 0 : 200;
                    var gownRent = <?php echo $gown_rent; ?>;
                    var deposit = 2000;

                    document.getElementById("reservation-service-fee").textContent =
                        serviceFee === 0 ? '₱0.00' : '₱200.00';

                    var totalPrice = gownRent + serviceFee + deposit;

                    document.getElementById("reservation-total-price").textContent =
                        '₱' + totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                    document.getElementById("reservation-total-price-hidden").value = totalPrice.toFixed(2);
                }


                document.getElementById('reservation_date').addEventListener('change', function() {
                    var selectedDate = new Date(this.value);
                    var minDate = new Date();
                    minDate.setMonth(minDate.getMonth() + 2);

                    if (selectedDate < minDate) {
                        alert('Reservation must be at least 2 months from today');
                        this.value = '';
                    }
                });


                updateReservationFee();


                document.getElementById("reservation_service").addEventListener('change', updateReservationFee);
            });
            document.addEventListener('DOMContentLoaded', function() {
                var dateInput = document.getElementById('date_rented');
                var nextAvailable = '<?php echo $nextAvailable; ?>';

                dateInput.setAttribute('min', nextAvailable);

                dateInput.addEventListener('change', function() {
                    var selectedDate = new Date(this.value);
                    var minDate = new Date(nextAvailable);

                    if (selectedDate < minDate) {
                        alert('Please select a date on or after ' + nextAvailable);
                        this.value = '';
                    }
                });
            });

            function toggleDropdown() {
                var dropdown = document.getElementById("myDropdown");
                dropdown.classList.toggle("show");
            }


            var rentModal = document.getElementById("rentModal");
            var successModal = document.getElementById("successModal");


            var rentBtn = document.querySelector(".rent-btn");


            var spanCloseRent = document.getElementsByClassName("close")[0];
            var spanCloseSuccess = document.getElementsByClassName("close")[1];


            if (rentBtn) {
                rentBtn.onclick = function() {
                    rentModal.style.display = "block";
                }
            }


            spanCloseRent.onclick = function() {
                rentModal.style.display = "none";
            }


            spanCloseSuccess.onclick = function() {
                successModal.style.display = "none";
            }


            window.onclick = function(event) {
                if (event.target == rentModal) {
                    rentModal.style.display = "none";
                }
                if (event.target == successModal) {
                    successModal.style.display = "none";
                }
            }


            document.getElementById("goBackButton").onclick = function() {
                window.location.href = 'Shop User.php';
            }


            let slideIndex = 0;
            showSlides(slideIndex);

            function plusSlides(n) {
                showSlides(slideIndex += n);
            }

            function showSlides(n) {
                let slides = document.querySelectorAll('.image-slider img');
                let prev = document.querySelector('.prev');
                let next = document.querySelector('.next');

                if (slides.length <= 1) {
                    prev.style.display = 'none';
                    next.style.display = 'none';
                } else {
                    prev.style.display = 'block';
                    next.style.display = 'block';
                }

                if (n >= slides.length) {
                    slideIndex = 0;
                }
                if (n < 0) {
                    slideIndex = slides.length - 1;
                }
                slides.forEach((slide, index) => {
                    slide.style.display = (index === slideIndex) ? 'block' : 'none';
                });
            }


            document.addEventListener('click', function(event) {
                const searchBar = document.getElementById('search-barA');
                const dropdown = document.getElementById('myDropdown');


                if (!dropdown.contains(event.target) && event.target.className !== 'dropbtn') {
                    dropdown.classList.remove('show');
                }
            });
        </script>

</body>

</html>