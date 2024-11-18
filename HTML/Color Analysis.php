<?php
session_start();

// Clear results on page load
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['result']);
}

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

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
} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: error.php");
    exit();
}

// Average RGB calculation
// Average RGB calculation with improved accuracy
function getFaceColor($imagePath) {
    $data = file_get_contents($imagePath);
    $len = strlen($data);
    $totalR = $totalG = $totalB = $totalPixels = 0;

    // Iterate over binary data to sample pixels
    for ($i = 0; $i < $len; $i += 4) {
        $r = ord($data[$i]);
        $g = ord($data[$i + 1]);
        $b = ord($data[$i + 2]);

        // Skip pure white or black pixels
        if (($r > 240 && $g > 240 && $b > 240) || ($r < 10 && $g < 10 && $b < 10)) {
            continue;
        }

        $totalR += $r;
        $totalG += $g;
        $totalB += $b;
        $totalPixels++;
    }

    // Prevent division by zero if all pixels are skipped
    if ($totalPixels === 0) {
        return [255, 255, 255]; // Default to white
    }

    return [
        round($totalR / $totalPixels),
        round($totalG / $totalPixels),
        round($totalB / $totalPixels),
    ];
}

// Improved skin tone analysis
function analyzeSkinTone($avgR, $avgG, $avgB) {
    $brightness = ($avgR + $avgG + $avgB) / 3;

    if ($brightness > 140) {
        return "Very Fair";
    } elseif ($brightness > 200) {
        return "Fair";
    } elseif ($brightness > 170) {
        return "Medium Fair";
    } elseif ($brightness > 130) {
        return "Medium";
    } elseif ($brightness > 110) {
        return "Olive";
    } elseif ($brightness > 80) {
        return "Naturally Brown";
    } else {
        return "Dark Brown";
    }
}

// Handle uploaded image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['skin_image'])) {
    $imagePath = 'uploaded_img/' . basename($_FILES['skin_image']['name']);

    if (!is_dir('uploaded_img')) {
        mkdir('uploaded_img', 0777, true);
    }

    if (move_uploaded_file($_FILES['skin_image']['tmp_name'], $imagePath)) {
        list($avgR, $avgG, $avgB) = getFaceColor($imagePath);
        $skinTone = analyzeSkinTone($avgR, $avgG, $avgB);

        $_SESSION['result'] = [
            'avgR' => $avgR,
            'avgG' => $avgG,
            'avgB' => $avgB,
            'skinTone' => $skinTone,
            'imagePath' => $imagePath
        ];
    } else {
        echo "Failed to upload image.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <link rel="stylesheet" href="../CSS/Analysis.css">
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
                                <a href="logout.php" class="sub-menu-link">
                                    <p>Log Out</p>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
        </div>
        <div class="analysis-container">
            <form method="POST" enctype="multipart/form-data">
                <h1>Upload an Image for Skin Tone Analysis</h1>
                <input class="upload" type="file" name="skin_image" accept="image/*" required>
                <button type="submit">Upload and Analyze</button>
            </form>
            <br>
            <?php
            if (isset($_SESSION['result'])) {
                $result = $_SESSION['result'];
                echo "<h1>Skin Tone Analysis Result</h1>";
                echo "<p>Average Color (RGB): R={$result['avgR']}, G={$result['avgG']}, B={$result['avgB']}</p>";
                echo "<p>Detected Skin Tone: <strong>{$result['skinTone']}</strong></p>";
                echo "<p><img src='{$result['imagePath']}' width='200' alt='Uploaded Image'></p>";
            } else {
                echo "No Results to Display.";
            }
            ?>
        </div>
    </div>
</body>
</html>
