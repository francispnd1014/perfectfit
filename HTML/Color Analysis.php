<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Clear the result on page load
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['result']);
}

// Handle the uploaded image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['skin_image'])) {
    $uploadDir = 'uploaded_img';
    $imagePath = $uploadDir . '/' . basename($_FILES['skin_image']['name']);

    // Check directory permissions
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory.");
            die("Failed to create upload directory.");
        }
    }

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['skin_image']['type'], $allowedTypes)) {
        error_log("Invalid file type: " . $_FILES['skin_image']['type']);
        die("Invalid file type. Please upload an image file.");
    }

    if (!move_uploaded_file($_FILES['skin_image']['tmp_name'], $imagePath)) {
        error_log("Failed to move uploaded file.");
        die("Failed to upload image. Please check file permissions.");
    }

    // Function to get the average color of the central part of the image
    function getFaceColor($imagePath)
    {
        $imageData = file_get_contents($imagePath);
        $image = imagecreatefromstring($imageData);

        if (!$image) {
            error_log("Failed to load image from string.");
            die("Failed to load image.");
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Define a central area for approximation (e.g., 20% of the image center)
        $centerX = $width * 0.4;
        $centerY = $height * 0.4;
        $sampleWidth = $width * 0.2;
        $sampleHeight = $height * 0.2;

        $totalR = $totalG = $totalB = 0;
        $totalPixels = 0;

        for ($y = $centerY; $y < $centerY + $sampleHeight; $y++) {
            for ($x = $centerX; $x < $centerX + $sampleWidth; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $totalR += $r;
                $totalG += $g;
                $totalB += $b;
                $totalPixels++;
            }
        }

        imagedestroy($image);

        return [
            round($totalR / $totalPixels),
            round($totalG / $totalPixels),
            round($totalB / $totalPixels)
        ];
    }

    function analyzeSkinTone($avgR, $avgG, $avgB)
    {
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

    list($avgR, $avgG, $avgB) = getFaceColor($imagePath);
    $skinTone = analyzeSkinTone($avgR, $avgG, $avgB);

    $_SESSION['result'] = [
        'avgR' => $avgR,
        'avgG' => $avgG,
        'avgB' => $avgB,
        'skinTone' => $skinTone,
        'imagePath' => $imagePath
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Skin Tone Analysis</title>
</head>
<body>
    <h1>Upload an Image for Skin Tone Analysis</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="skin_image" accept="image/*" required>
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
</body>
</html>