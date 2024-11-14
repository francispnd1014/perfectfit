<?php
session_start();

// Check if email is set in session
if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";

// Create connection using try-catch
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get email from session and prepare statement
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT fname, sname, pfp FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

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

    $gowns_query = "SELECT *, img FROM product WHERE status != 1 ORDER BY RAND() LIMIT 12";
    $gowns_result = $conn->query($gowns_query);

} catch (Exception $e) {
    // Log error and redirect to error page
    error_log($e->getMessage());
    header("Location: error.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <link rel="stylesheet" href="../CSS/Home.css">
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
        <!-- Replace the banner div with this new structure -->
        <div class="slideshow-wrapper">
            <div class="slideshow-container">
                <div class="titleheader overlay">
                    <div style="display: flex; position: relative; justify-content: center; align-items: center; width: 100%;">
                        <img class="arrowright" src="https://cdn.animaapp.com/projects/662e3db47c76cfe9d48b5b59/releases/662e3ddfc9610b16f5d28f56/img/arrow-1.svg" alt="Arrow 1">
                        <div style="position: relative; display: flex; justify-content: center; align-items: center;">
                            <span class="perfectfit">PERFECT FIT</span>
                        </div>
                        <img class="arrowleft" src="https://cdn.animaapp.com/projects/662e3db47c76cfe9d48b5b59/releases/662e3ddfc9610b16f5d28f56/img/arrow-2.svg" alt="Arrow 2">
                    </div>
                    <h1 class="gown">RICH SABINIAN</h1>
                    <p class="intro">PerfectFit: Gown Rental believes every occasion deserves a showstopping outfit. Explore our stunning collection of gowns and find the perfect style to express your unique personality and make your special event unforgettable.</p>
                </div>

                <!-- Slideshow Images -->
                <div class="mySlides fade">
                    <img src="../IMAGES/slide1.jpg">
                </div>
                <div class="mySlides fade">
                    <img src="../IMAGES/slide2.jpg">
                </div>
                <div class="mySlides fade">
                    <img src="../IMAGES/slide3.jpg">
                </div>

                <a class="prev" onclick="plusSlides(-1)">❮</a>
                <a class="next" onclick="plusSlides(1)">❯</a>

                <div class="dots-container">
                    <span class="dot" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                    <span class="dot" onclick="currentSlide(3)"></span>
                </div>
            </div>
        </div>
        <br>
        <br>

        <div class="product-display">
            <?php
            if ($gowns_result->num_rows > 0) {
                while ($gown = $gowns_result->fetch_assoc()) {
                    // Fetch rental details if the gown is rented
                    $rental_details = null;
                    if ($gown['status'] == 1) {
                        $rental_query = "SELECT date_rented, duedate FROM rent WHERE gownname_rented = ?";
                        $stmt = $conn->prepare($rental_query);
                        $stmt->bind_param("s", $gown['name']);
                        $stmt->execute();
                        $rental_result = $stmt->get_result();
                        if ($rental_result->num_rows > 0) {
                            $rental_details = $rental_result->fetch_assoc();
                        }
                        $stmt->close();
                    }
            ?>
                    <a href="Preview.php?id=<?php echo $gown['id']; ?>" class="card-link">
                        <div class="card">
                            <?php if ($gown['status'] == 1) { ?>
                                <div class="rented-overlay">
                                    <?php if ($rental_details) { ?>
                                        <div class="rental-details small-font">
                                            <p>Date Rented:</p>
                                            <p><?php echo htmlspecialchars($rental_details['date_rented']); ?></p>
                                            <p>Date of Return:</p>
                                            <p><?php echo htmlspecialchars($rental_details['duedate']); ?></p>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="image">
                                <?php
                                $images = @unserialize($gown['img']);
                                if ($images === false && $gown['img'] !== 'b:0;') {
                                    $images = [$gown['img']]; // Single image fallback
                                }
                                if (!empty($images)) {
                                    $image = $images[0];
                                    echo '<img src="uploaded_img/' . htmlspecialchars($image) . '" alt="" style="width: 200px; height: 250px;">';
                                }
                                ?>
                            </div>
                            <div class="caption">
                                <p class="product_name ellipsis"><?php echo htmlspecialchars($gown['name']); ?></p>
                                <?php if ($gown['tally'] == 0) { ?>
                                    <p class="tally_status">Brandnew</p>
                                <?php } else { ?>
                                    <p class="tally_status">Used</p>
                                <?php } ?>
                                <p class="price"><b>Rent: ₱<?php echo number_format($gown['price'], 2); ?></b></p>
                            </div>
                        </div>
                    </a>
            <?php
                }
            } else {
                echo '<p>No featured gowns available at the moment.</p>';
            }
            ?>
        </div>
    </div>
    <br>
    <br>
    <div class="footer">
        <div class="footernav">
            <div class="col">
                <img src="../IMAGES/RICH SABINIANS.png" class="flogo">
                <p>PerfectFit makes gown rental effortless! Browse our stunning collection and find your dream dress from the comfort of your own home with our user-friendly website.</p>
            </div>
            <div class="col">
                <h3>LOCATION</h3>
                <p><a href="https://www.google.com/maps/place/Rich+Sabinian+dress+shop/@15.1455783,120.5756874,17z/data=!3m1!4b1!4m6!3m5!1s0x3396f3376d4710c9:0xe98a262fadbfa4f9!8m2!3d15.1455783!4d120.5782623!16s%2Fg%2F11g0wgfhjj?entry=ttu&g_ep=EgoyMDI0MDkzMC4wIKXMDSoASAFQAw%3D%3D"><i class="fa-solid fa-location-dot"></i> #18 Purok 1, Angeles, Pampanga</a></p>
            </div>
            <div class="col">
                <h3>CONTACT US</h3>
                <p><i class="fa-solid fa-phone-volume"></i> 0916 460 5072</p>
                <p><a href="https://www.facebook.com/ritche.sabinian"><i class="fa-brands fa-facebook"></i> facebook.com/ritche.sabinian</a></p>
                <p><i class="fa-solid fa-envelope"></i> richsabinianpampang@gmail.com</p>
            </div>
        </div>
    </div>
    <script>
        let slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");

            if (n > slides.length) {
                slideIndex = 1
            }
            if (n < 1) {
                slideIndex = slides.length
            }

            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active-dot", "");
            }

            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active-dot";
        }

        // Auto advance slides
        setInterval(() => {
            plusSlides(1);
        }, 5000);

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
    </script>
</body>

</html>