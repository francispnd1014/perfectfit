<?php
$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data from the database
$usersResult = $conn->query("SELECT COUNT(*) AS count FROM users");
$productsResult = $conn->query("SELECT COUNT(*) AS count FROM product");
$rentedResult = $conn->query("SELECT COUNT(*) AS count FROM rent");

$usersCount = $usersResult->fetch_assoc()['count'];
$productsCount = $productsResult->fetch_assoc()['count'];
$rentedCount = $rentedResult->fetch_assoc()['count'];

// Fetch theme data from the products table
$themesResult = $conn->query("SELECT theme, COUNT(*) AS count FROM product GROUP BY theme");
$themes = [];
$themeCounts = [];
while ($row = $themesResult->fetch_assoc()) {
    $themes[] = $row['theme'];
    $themeCounts[] = $row['count'];
}

// Fetch user who rented the most
$topUserResult = $conn->query("SELECT email, COUNT(*) AS count FROM rent GROUP BY email ORDER BY count DESC LIMIT 1");
$topUser = $topUserResult->fetch_assoc();

// Fetch top 3 most popular gowns based on tally
$topGownsResult = $conn->query("SELECT name, img, tally FROM product ORDER BY tally DESC LIMIT 3");
$topGowns = [];
while ($row = $topGownsResult->fetch_assoc()) {
    $topGowns[] = $row;
}

// Fetch pending request gowns
$pendingRequestsResult = $conn->query("SELECT COUNT(*) AS count FROM rent WHERE request = 'pending'");
$pendingRequestsCount = $pendingRequestsResult->fetch_assoc()['count'];

// Fetch reserved gowns
$reservedGownsResult = $conn->query("SELECT COUNT(*) AS count FROM rent WHERE request = 'reserved'");
$reservedGownsCount = $reservedGownsResult->fetch_assoc()['count'];

// Fetch total revenue
$revenueResult = $conn->query("SELECT SUM(total) AS revenue FROM rent WHERE request = 'accepted'");
$totalRevenue = $revenueResult->fetch_assoc()['revenue'];

$reservedGownsResult = $conn->query("SELECT COUNT(*) AS count FROM rent WHERE reservation = 1");
$reservedGownsCount = $reservedGownsResult->fetch_assoc()['count'];

$activeRentalsResult = $conn->query("SELECT COUNT(*) AS count FROM rent WHERE request = 'received'");
$activeRentalsCount = $activeRentalsResult->fetch_assoc()['count'];

// Fetch monthly revenue for line chart
$monthlyRevenueResult = $conn->query("SELECT DATE_FORMAT(date_rented, '%Y-%m') AS month, SUM(total) AS revenue FROM rent WHERE request = 'accepted' GROUP BY month ORDER BY month");
$months = [];
$monthlyRevenues = [];
while ($row = $monthlyRevenueResult->fetch_assoc()) {
    $months[] = $row['month'];
    $monthlyRevenues[] = $row['revenue'];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PERFECT FIT</title>
    <link rel="stylesheet" href="../CSS/Dashboard.css">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <script src="https://kit.fontawesome.com/a4c2475e10.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
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
    </div>
    <div class="main-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Users</span>
                    <span class="stat-icon pink"><i class="mdi mdi-account-multiple"></i></span>
                </div>
                <div class="stat-value"><?php echo $usersCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Products Available</span>
                    <span class="stat-icon blue"><i class="mdi mdi-hanger"></i></span>
                </div>
                <div class="stat-value"><?php echo $productsCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Revenue</span>
                    <span class="stat-icon green"><i class="mdi mdi-cash-multiple"></i></span>
                </div>
                <div class="stat-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Gowns Out/Received</span>
                    <span class="stat-icon purple"><i class="mdi mdi-dress"></i></span>
                </div>
                <div class="stat-value"><?php echo $activeRentalsCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending Requests</span>
                    <span class="stat-icon orange"><i class="mdi mdi-timer-sand"></i></span>
                </div>
                <div class="stat-value"><?php echo $pendingRequestsCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Reserved Gowns</span>
                    <span class="stat-icon teal"><i class="mdi mdi-bookmark"></i></span>
                </div>
                <div class="stat-value"><?php echo $reservedGownsCount; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Top Customer</span>
                    <span class="stat-icon red"><i class="mdi mdi-account-star"></i></span>
                </div>
                <div class="stat-value" style="font-size: 16px;">
                    <?php
                    if ($topUser) {
                        echo htmlspecialchars($topUser['email']);
                    } else {
                        echo "No top customer found";
                    }
                    ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Active Orders</span>
                    <span class="stat-icon indigo"><i class="mdi mdi-calendar-check"></i></span>
                </div>
                <div class="stat-value"><?php echo $rentedCount; ?></div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3 class="chart-title">Monthly Revenue Trend</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">Gown Categories</h3>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="top-products">
            <h3 class="chart-title">Top Performing Products</h3>
            <div class="products-grid">
                <?php foreach ($topGowns as $index => $gown): ?>
                    <div class="product-card">
                        <?php
                        $images = unserialize($gown['img']);
                        $firstImage = is_array($images) ? $images[0] : $gown['img'];
                        $imagePath = "uploaded_img/" . htmlspecialchars($firstImage);
                        if (file_exists($imagePath)) {
                            echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($gown['name']) . '">';
                        } else {
                            echo '<img src="path/to/default/image.jpg" alt="Image not found">';
                        }
                        ?>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($gown['name']); ?></div>
                            <div class="product-rank">Top #<?php echo $index + 1; ?> Most Rented</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown(event) {
            event.stopPropagation();
            var dropdown = event.currentTarget.parentElement;
            dropdown.classList.toggle("show");
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            var dropdowns = document.getElementsByClassName("dropdown");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
        // Revenue Chart
        const revenueChart = new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Revenue',
                    data: <?php echo json_encode($monthlyRevenues); ?>,
                    borderColor: '#ee4d2d',
                    backgroundColor: 'rgba(238, 77, 45, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Category Chart
        const categoryChart = new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($themes); ?>,
                datasets: [{
                    data: <?php echo json_encode($themeCounts); ?>,
                    backgroundColor: [
                        '#ee4d2d', '#2d9cdb', '#27ae60', '#9b51e0',
                        '#f2994a', '#00c4b4', '#eb5757', '#4c6ef5'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>