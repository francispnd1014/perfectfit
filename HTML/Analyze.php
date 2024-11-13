<?php
function getAverageColor($imagePath) {
    $imageInfo = getimagesize($imagePath);
    $mimeType = $imageInfo['mime'];

    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($imagePath);
            break;
        default:
            die("Unsupported image format.");
    }

    if (!$image) {
        die("Failed to load image.");
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $totalR = $totalG = $totalB = 0;
    $totalPixels = 0;

    // Iterate through each pixel
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
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

    // Calculate the average RGB values
    $avgR = round($totalR / $totalPixels);
    $avgG = round($totalG / $totalPixels);
    $avgB = round($totalB / $totalPixels);

    // Free up memory
    imagedestroy($image);

    return [$avgR, $avgG, $avgB];
}

function analyzeSkinTone($avgR, $avgG, $avgB) {
    // Logic to determine skin tone
    if ($avgR > 220 && $avgG > 200 && $avgB > 180) {
        return "Pale";
    } elseif ($avgR > 200 && $avgG > 180 && $avgB > 160) {
        return "Fair";
    } elseif ($avgR > 180 && $avgG > 160 && $avgB > 140) {
        return "Medium";
    } elseif ($avgR > 160 && $avgG > 140 && $avgB > 120) {
        return "Olive";
    } elseif ($avgR > 140 && $avgG > 120 && $avgB > 100) {
        return "Naturally Brown";
    } else {
        return "Dark Brown";
    }
}

// Handle the uploaded image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['skin_image'])) {
    $imagePath = 'uploaded_img/' . basename($_FILES['skin_image']['name']);
    
    // Create the uploads directory if it doesn't exist
    if (!is_dir('uploaded_img')) {
        mkdir('uploaded_img', 0777, true);
    }

    // Move the uploaded file to the uploads directory
    if (move_uploaded_file($_FILES['skin_image']['tmp_name'], $imagePath)) {
        list($avgR, $avgG, $avgB) = getAverageColor($imagePath);
        $skinTone = analyzeSkinTone($avgR, $avgG, $avgB);

        echo "<h1>Skin Tone Analysis Result</h1>";
        echo "<p>Average Color (RGB): R={$avgR}, G={$avgG}, B={$avgB}</p>";
        echo "<p>Detected Skin Tone: <strong>{$skinTone}</strong></p>";
        echo "<p><img src='{$imagePath}' width='200' alt='Uploaded Image'></p>";
    } else {
        echo "Failed to upload image.";
    }
} else {
    echo "Invalid request.";
}
?>