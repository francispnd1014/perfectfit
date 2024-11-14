<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed");
}

// Assuming $email is defined and sanitized
$email = $_SESSION['email']; // or however you get the email

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

// Get the gown ID from the query parameter
$gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch gown details from the database
$query = "SELECT * FROM product WHERE id = $gown_id";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $gown = $result->fetch_assoc();
    $gown_image = $gown['img']; // Assuming 'img' is the column name for the gown image URL
    $gown_rent = $gown['price']; // Assuming 'price' is the column name for the gown price
    $gown_name = $gown['name']; // Assuming 'name' is the column name for the gown name
    $gown_status = $gown['status']; // Assuming 'status' is the column name for the gown status
} else {
    echo "Gown not found.";
    exit();
}

// Add this function after the database connection setup
function getNextAvailableDate($conn, $gown_name)
{
    // Check if the gown is reserved
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
            // If the gown is reserved, set the next available date to current date + 4 days
            $date = new DateTime();
            $date->modify('+4 days');
            return $date->format('Y-m-d');
        } else {
            // If the gown is not reserved, proceed with the usual logic
            $duedate = new DateTime($row['duedate']);
            $duedate->modify('+1 week'); // Add 1 week grace period
            return $duedate->format('Y-m-d');
        }
    }

    // If no existing rentals or reservations, return current date + 4 days
    $date = new DateTime();
    $date->modify('+4 days');
    return $date->format('Y-m-d');
}

// Check if the gown is already favorited
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
    // Check if the email already exists in the favorite table
    $stmt = $conn->prepare("SELECT gown_name FROM favorite WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($existing_gown_names);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        // Email exists, check if the gown is already favorited
        $gown_names_array = explode(', ', $existing_gown_names);
        if (in_array($gown_name, $gown_names_array)) {
            // Gown is already favorited, unfavorite it
            $gown_names_array = array_diff($gown_names_array, [$gown_name]);
            $updated_gown_names = implode(', ', $gown_names_array);
            $stmt->close();
            $stmt = $conn->prepare("UPDATE favorite SET gown_name = ? WHERE email = ?");
            $stmt->bind_param("ss", $updated_gown_names, $email);
            $stmt->execute();
            $isFavorited = false;
        } else {
            // Gown is not favorited, add it to favorites
            $gown_names_array[] = $gown_name;
            $updated_gown_names = implode(', ', $gown_names_array);
            $stmt->close();
            $stmt = $conn->prepare("UPDATE favorite SET gown_name = ? WHERE email = ?");
            $stmt->bind_param("ss", $updated_gown_names, $email);
            $stmt->execute();
            $isFavorited = true;
        }
    } else {
        // Email does not exist, add new entry
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO favorite (email, gown_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $gown_name);
        $stmt->execute();
        $isFavorited = true;
    }
    $stmt->close();
    // Reload the page to reflect the changes
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rent_gown'])) {
    $deliveryDate = $_POST['date_rented'];
    $returnDate = $_POST['duedate'];
    $cellnumber = $_POST['cellnumber'];
    $deliveryAddress = $_POST['address'];
    $service = $_POST['service'];
    $total = floatval(str_replace(',', '', $_POST['total_price'])); // Remove commas and convert to float
    $fullName = $_SESSION['fullname'];
    $email = $_SESSION['email'];
    $gownName = $gown_name;

    // Insert rental details into the rent table with request status 'pending'
    $stmt = $conn->prepare("INSERT INTO rent (email, gownname_rented, date_rented, cellnumber, duedate, address, service, total, request) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssssssd", $email, $gownName, $deliveryDate, $cellnumber, $returnDate, $deliveryAddress, $service, $total);
    $stmt->execute();
    $stmt->close();

    // Show the success modal
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('rentModal').style.display = 'none';
            document.getElementById('successModal').style.display = 'block';
        });
    </script>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reserve_gown'])) {
    $deliveryDate = $_POST['date_rented'];
    $returnDate = $_POST['duedate'];
    $deliveryAddress = $_POST['address'];
    $cellnumber = $_POST['cellnumber'];
    $service = $_POST['service'];
    $total = floatval(str_replace(',', '', $_POST['total_price'])); // Remove commas and convert to float
    $fullName = $_SESSION['fullname'];
    $email = $_SESSION['email'];
    $gownName = $gown_name;

    // Insert reservation details
    $stmt = $conn->prepare("INSERT INTO rent (email, gownname_rented, date_rented, duedate, cellnumber, address, service, total, request, reservation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'accepted', TRUE)");
    $stmt->bind_param("sssssssd", $email, $gownName, $deliveryDate, $returnDate, $cellnumber, $deliveryAddress, $service, $total);
    $stmt->execute();
    $stmt->close();

    // Show success modal
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
    $gown_tally = $gown['tally']; // Fetch the tally value
} else {
    echo "Gown not found.";
    exit();
}

// Log user interaction
function log_user_interaction($conn, $email, $gown_id, $interaction_type)
{
    $stmt = $conn->prepare("INSERT INTO user_interactions (email, gown_id, interaction_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $email, $gown_id, $interaction_type);
    $stmt->execute();
    $stmt->close();
}
$gown_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Log view interaction
if ($gown_id) {
    log_user_interaction($conn, $email, $gown_id, 'view');
}

// Log search interaction
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $select_query .= " AND (name LIKE '%$search%' OR size LIKE '%$search%' OR color LIKE '%$search%' OR theme LIKE '%$search%' OR analysis LIKE '%$search%' OR tone LIKE '%$search%')";
    log_user_interaction($conn, $email, 0, 'search');
}


// Fetch 5 products from the database
$product_query = "SELECT * FROM product LIMIT 6";
$product_result = $conn->query($product_query);
$products = [];
if ($product_result->num_rows > 0) {
    while ($product_row = $product_result->fetch_assoc()) {
        $products[] = $product_row;
    }
}


// Fetch rental status for the gown
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
                                <a href="logout.php" class="sub-menu-link">
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
                                $images = [$gown_image]; // Treat as a single image if unserializing fails
                            }
                            // Display images
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
                                // Fetch rental details if the gown is rented
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
        // Fetch recommended products based on user interactions
        function get_recommended_products($conn, $email)
        {
            // Fetch the most interacted gowns by the user excluding rented gowns
            $user_interactions_query = "
        SELECT gown_id, COUNT(*) as interaction_count
        FROM user_interactions
        WHERE email = ? AND gown_id NOT IN (SELECT id FROM product WHERE status = 1)
        GROUP BY gown_id
        ORDER BY interaction_count DESC
        LIMIT 5
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

            // Fetch the most interacted gowns by other users who have similar interactions excluding rented gowns
            $other_users_interactions_query = "
        SELECT gown_id, COUNT(*) as interaction_count
        FROM user_interactions
        WHERE email != ? AND gown_id IN (SELECT gown_id FROM user_interactions WHERE email = ?) AND gown_id NOT IN (SELECT id FROM product WHERE status = 1)
        GROUP BY gown_id
        ORDER BY interaction_count DESC
        LIMIT 2
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

            // Merge and return unique gown IDs
            return array_unique(array_merge($user_interacted_gowns, $recommended_gowns));
        }

        // Fetch recommended products
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

        // Display recommended products
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
                <label for="date_rented">Date of Delivery:</label>
                <input class="int-delivery" type="date" id="date_rented" name="date_rented" required
    <?php
    $nextAvailable = getNextAvailableDate($conn, $gown_name);
    echo 'min="' . $nextAvailable . '"';
    ?>>
<div class="availability-info">
    <?php if ($gown_status == 1): ?>
        <p class="notice">This gown will be available from <?php echo date('F j, Y', strtotime($nextAvailable)); ?></p>
    <?php endif; ?>
</div>

                <label for="duedate">Date of Return:</label>
                <input class="int-delivery" type="date" id="duedate" name="duedate" required>

                <label for="address">Delivery Address:</label>
                <input class="int-delivery" type="text" id="address" name="address" required>

                <label for="address">Cellphone Number:</label>
                <input class="int-delivery" type="text" id="cellnumber" name="cellnumber" required>

                <label for="service">Service:</label>
                <select class="int-delivery" id="service" name="service" required onchange="updateServiceFee()">
                    <option value="delivery">Delivery</option>
                    <option value="pickup">Pickup</option>
                </select>
                <div class="price-details">
                    <p>Rent Price: <span class="price">₱<?php echo number_format($gown_rent, 2, '.', ','); ?></span></p>
                    <p>Service Fee: <span class="price" id="service-fee">₱<?php echo number_format($service_fee, 2, '.', ','); ?></span></p>
                    <p>Additional Fee (Deposit): <span class="price">₱<?php echo number_format(2000, 2, '.', ','); ?></span></p>
                    <p>Total (COD): <span class="price" id="total-price">₱<?php echo number_format($total_price, 2, '.', ','); ?></span></p>
                </div>
                <input type="hidden" id="total-price-hidden" name="total_price" value="<?php echo number_format($total_price, 2, '.', ','); ?>">
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
                <input class="int-delivery" type="text" id="cellnumber" name="cellnumber" required>

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
                    <span class="details-label">Gown Name:</span>
                    <span class="details-value"><?php echo isset($gownName) ? htmlspecialchars($gownName) : ''; ?></span>
                </div>
                <div class="details-container">
                    <span class="details-label">Date of Delivery:</span>
                    <span class="details-value">
                        <?php
                        if (isset($deliveryDate)) {
                            $date = new DateTime($deliveryDate);
                            echo $date->format('F j, Y'); // Format as "Month day, Year"
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
                            echo $date->format('F j, Y'); // Format as "Month day, Year"
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

        <script>
document.addEventListener('DOMContentLoaded', function() {
    var reservationModal = document.getElementById("reservationModal");
    var reserveBtn = document.querySelector(".reserve-btn");
    var spanCloseReservation = document.getElementsByClassName("close-reservation")[0];

    reserveBtn.onclick = function() {
        reservationModal.style.display = "block";

        // Set minimum date to 2 months from today
        var today = new Date();
        today.setMonth(today.getMonth() + 2);
        today.setDate(today.getDate() + 1);
        var minDate = today.toISOString().split('T')[0];
        document.getElementById('reservation_date').min = minDate;
        document.getElementById('reservation_return').min = minDate; // Set min date for return date
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

    // Add validation for reservation dates
    document.getElementById('reservation_date').addEventListener('change', function() {
        var selectedDate = new Date(this.value);
        var minDate = new Date();
        minDate.setMonth(minDate.getMonth() + 2);

        if (selectedDate < minDate) {
            alert('Reservation must be at least 2 months from today');
            this.value = '';
        }
    });

    // Call updateReservationFee on page load to set the initial values
    updateReservationFee();

    // Add event listener to update the service fee when the service option changes
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

            function updateServiceFee() {
                var serviceSelect = document.getElementById("service");
                var serviceFeeElement = document.getElementById("service-fee");
                var totalPriceElement = document.getElementById("total-price");
                var totalPriceHidden = document.getElementById("total-price-hidden");
                var gownRent = <?php echo $gown_rent; ?>;
                var serviceFee = serviceSelect.value === "pickup" ? 0 : 200;
                var deposit = 2000;

                serviceFeeElement.textContent = serviceFee === 0 ? '' : '₱' + serviceFee.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                var totalPrice = (gownRent + serviceFee + deposit).toFixed(2);
                totalPriceElement.textContent = '₱' + totalPrice.replace(/\d(?=(\d{3})+\.)/g, '$&,');
                totalPriceHidden.value = totalPrice; // Store the value without commas
            }

            // Call updateServiceFee on page load to set the initial values
            document.addEventListener('DOMContentLoaded', updateServiceFee);

            function toggleDropdown() {
                var dropdown = document.getElementById("myDropdown");
                dropdown.classList.toggle("show");
            }

            // Get the modals
            var rentModal = document.getElementById("rentModal");
            var successModal = document.getElementById("successModal");

            // Get the button that opens the rent modal
            var rentBtn = document.querySelector(".rent-btn");

            // Get the <span> elements that close the modals
            var spanCloseRent = document.getElementsByClassName("close")[0];
            var spanCloseSuccess = document.getElementsByClassName("close")[1];

            // When the user clicks the button, open the rent modal 
            if (rentBtn) {
                rentBtn.onclick = function() {
                    rentModal.style.display = "block";
                }
            }

            // When the user clicks on <span> (x), close the rent modal
            spanCloseRent.onclick = function() {
                rentModal.style.display = "none";
            }

            // When the user clicks on <span> (x), close the success modal
            spanCloseSuccess.onclick = function() {
                successModal.style.display = "none";
            }

            // When the user clicks anywhere outside of the modals, close them
            window.onclick = function(event) {
                if (event.target == rentModal) {
                    rentModal.style.display = "none";
                }
                if (event.target == successModal) {
                    successModal.style.display = "none";
                }
            }

            // Handle go back button click
            document.getElementById("goBackButton").onclick = function() {
                window.location.href = 'Shop User.php';
            }

            // Image slider functionality
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

            // Close search bar, filter form box, and dropdown menu when clicking outside
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