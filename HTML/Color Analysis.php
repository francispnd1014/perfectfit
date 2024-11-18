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
function getFaceColor($imagePath) {
    $imageInfo = getimagesize($imagePath);
    $mime = $imageInfo['mime'];

    if ($mime !== 'image/jpeg') {
        die("Only JPEG files are supported.");
    }

    // Open file as binary and analyze chunks
    $file = fopen($imagePath, 'rb');
    $data = fread($file, filesize($imagePath));
    fclose($file);

    $width = $imageInfo[0];
    $height = $imageInfo[1];

    // Sampling area in the center (20% of the image dimensions)
    $centerX = $width * 0.4;
    $centerY = $height * 0.4;
    $sampleWidth = $width * 0.2;
    $sampleHeight = $height * 0.2;

    // Initialize totals for RGB values
    $totalR = $totalG = $totalB = 0;
    $totalPixels = 0;

    // Simplified pixel sampling
    for ($y = $centerY; $y < $centerY + $sampleHeight; $y++) {
        for ($x = $centerX; $x < $centerX + $sampleWidth; $x++) {
            $pixelOffset = ($y * $width + $x) * 3; // Calculate pixel position in binary
            $r = ord($data[$pixelOffset]);
            $g = ord($data[$pixelOffset + 1]);
            $b = ord($data[$pixelOffset + 2]);

            $totalR += $r;
            $totalG += $g;
            $totalB += $b;
            $totalPixels++;
        }
    }

    return [
        round($totalR / $totalPixels),
        round($totalG / $totalPixels),
        round($totalB / $totalPixels)
    ];
}

function analyzeSkinTone($avgR, $avgG, $avgB) {
    // Check for extreme light or dark values
    if ($avgR >= 240 && $avgG >= 240 && $avgB >= 240) {
        return "Pale (White)";
    } elseif ($avgR <= 50 && $avgG <= 50 && $avgB <= 50) {
        return "Very Dark Brown";
    }

    // Regular thresholds for skin tones
    if ($avgR >= 220 && $avgG >= 210 && $avgB >= 190) {
        return "Very Fair";
    } elseif ($avgR >= 200 && $avgG >= 180 && $avgB >= 160) {
        return "Fair";
    } elseif ($avgR >= 180 && $avgG >= 160 && $avgB >= 140) {
        return "Medium Fair";
    } elseif ($avgR >= 170 && $avgG >= 140 && $avgB >= 110) {
        return "Medium";
    } elseif ($avgR >= 150 && $avgG >= 120 && $avgB >= 90) {
        return "Olive";
    } elseif ($avgR >= 130 && $avgG >= 100 && $avgB >= 80) {
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
