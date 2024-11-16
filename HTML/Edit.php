<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: Login.php");
    exit();
}

require_once 'connection.php';
$conn = Database::getInstance()->getConnection();

$color_query = "SELECT DISTINCT color FROM product";
$color_result = mysqli_query($conn, $color_query);

$product = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM product WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Product not found'); window.location.href = 'CRUD.php';</script>";
        exit();
    }
}

if (isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_rent = $_POST['product_rent'];
    $product_sizes = isset($_POST['product_size']) ? implode(',', $_POST['product_size']) : '';
    $product_color = !empty($_POST['new_color']) ? $_POST['new_color'] : $_POST['product_color'];
    $product_theme = $_POST['product_theme'];
    $product_analysis = isset($_POST['product_analysis']) ? implode(',', $_POST['product_analysis']) : '';
    $product_tone = isset($_POST['product_tone']) ? implode(',', $_POST['product_tone']) : '';
    $product_images = $_FILES['product_images'];

    if (empty($product_name) || empty($product_rent) || empty($product_sizes) || empty($product_color) || empty($product_theme) || empty($product_analysis) || empty($product_tone)) {
        $message[] = 'Please fill out all fields.';
    } else {
        $existing_images = @unserialize($product['img']);
        if ($existing_images === false && $product['img'] !== 'b:0;') {
            $existing_images = [$product['img']]; // Treat as a single image if unserializing fails
        }

        $new_image_paths = [];
        for ($i = 0; $i < count($product_images['name']); $i++) {
            $product_image = $product_images['name'][$i];
            $product_image_tmp_name = $product_images['tmp_name'][$i];
            $product_image_folder = 'uploaded_img/' . $product_image;

            if (move_uploaded_file($product_image_tmp_name, $product_image_folder)) {
                $new_image_paths[] = $product_image;
            } else {
                $message[] = 'Could not upload image: ' . $product_image;
            }
        }

        $all_images = array_merge($existing_images, $new_image_paths);
        $all_images_serialized = serialize($all_images);

        $update_query = "UPDATE product SET name='$product_name', price='$product_rent', size='$product_sizes', color='$product_color', theme='$product_theme', analysis='$product_analysis', tone='$product_tone', img='$all_images_serialized' WHERE id=$product_id";
        $update = mysqli_query($conn, $update_query);
        if ($update) {
            echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal(); });</script>";
        } else {
            $message[] = 'Could not update the product.';
        }
    }
}
// Ensure product_id is set before use
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : (isset($product['id']) ? $product['id'] : null);

if (isset($_POST['delete_image']) && $product_id) {
    $image_to_delete = $_POST['delete_image'];

    // Get the product's images from the database specific to this product ID
    $query = "SELECT img FROM product WHERE id = $product_id";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);

    $images = @unserialize($product['img']);
    if ($images === false && $product['img'] !== 'b:0;') {
        $images = [$product['img']]; // Handle as a single image if unserializing fails
    }

    // Remove the specified image from the array for this product only
    if (($key = array_search($image_to_delete, $images)) !== false) {
        unset($images[$key]);

        // Serialize the updated array of images
        $updated_images = serialize(array_values($images));

        // Update the product's images in the database
        $update_query = "UPDATE product SET img='$updated_images' WHERE id=$product_id";
        if (mysqli_query($conn, $update_query)) {
            // Optionally, delete the physical file from the server
            $image_path = 'uploaded_img/' . $image_to_delete;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            echo "<script>alert('Image deleted successfully.'); window.location.href = 'CRUD.php';</script>";
        } else {
            echo "<script>alert('Failed to delete the image.');</script>";
        }
    } else {
        echo "<script>alert('Image not found for this product.');</script>";
    }
} elseif (!$product_id) {
    echo "<script>alert('Product ID is missing.');</script>";
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="font/css/all.min.css">
    <link rel="stylesheet" href="../CSS/Edit.css">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <title>Shop Admin</title>
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
                    <a href="#" class="dropbtn" onclick="toggleDropdown()">Admin</a>
                    <div id="myDropdown" class="dropdown-content">
                        <a href="../HTML/Logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div id="edit" class="container">
        <div class="admin-product-form-container">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $product['id']; ?>" method="post" enctype="multipart/form-data">
                <h3>Edit Product</h3>
                <input type="hidden" name="product_id" id="edit_product_id" value="<?php echo $product['id']; ?>">
                <div class="form-columns">
                    <div class="left-column">
                        <input type="text" placeholder="Product name" name="product_name" id="edit_product_name" class="box" value="<?php echo $product['name']; ?>">
                        <input type="text" placeholder="Product rent price" name="product_rent" id="edit_product_rent" class="box" value="<?php echo $product['price']; ?>">
                        <div class="option">
                            <button type="button" class="btn-option" onclick="showSelectColor()">Select Color</button>
                            <button type="button" class="btn-option" onclick="showNewColor()">Add New Color</button>
                        </div>
                        <div id="select_color_div">
                            <select name="product_color" id="edit_product_color" class="box1">
                                <option value="" disabled>Select Product Color</option>
                                <?php
                                if (mysqli_num_rows($color_result) > 0) {
                                    while ($row = mysqli_fetch_assoc($color_result)) {
                                        $selected = ($row['color'] == $product['color']) ? 'selected' : '';
                                        echo '<option value="' . $row['color'] . '" ' . $selected . '>' . $row['color'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div id="new_color_div" style="display: none;">
                            <input type="text" placeholder="New color" name="new_color" id="edit_new_color" class="box">
                        </div>
                    </div>
                    <div class="right-column">
                        <select name="product_theme" id="edit_product_theme" class="box1">
                            <option value="" disabled>Select Product Theme</option>
                            <option value="Wedding" <?php echo ($product['theme'] == 'Wedding') ? 'selected' : ''; ?>>
                                Wedding<?php echo ($product['theme'] == 'Wedding') ? '(Current)' : ''; ?>
                            </option>
                            <option value="Prom" <?php echo ($product['theme'] == 'Prom') ? 'selected' : ''; ?>>
                                Prom<?php echo ($product['theme'] == 'Prom') ? '(Current)' : ''; ?>
                            </option>
                            <option value="Formal" <?php echo ($product['theme'] == 'Formal') ? 'selected' : ''; ?>>
                                Formal<?php echo ($product['theme'] == 'Formal') ? '(Current)' : ''; ?>
                            </option>
                            <option value="Debut" <?php echo ($product['theme'] == 'Debut') ? 'selected' : ''; ?>>
                                Debut<?php echo ($product['theme'] == 'Debut') ? '(Current)' : ''; ?>
                            </option>
                            <!-- Add more options as needed -->
                        </select>
                        <h4>Select Product Size</h4>
                        <div class="checkbox-group">
                            <label class="tones">
                                <input type="checkbox" name="product_size[]" value="Extra Small" <?php echo (strpos($product['size'], 'Extra Small') !== false) ? 'checked' : ''; ?>>
                                Extra Small<?php echo (strpos($product['size'], 'Extra Small') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_size[]" value="Small" <?php echo (strpos($product['size'], 'Small') !== false) ? 'checked' : ''; ?>>
                                Small<?php echo (strpos($product['size'], 'Small') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_size[]" value="Medium" <?php echo (strpos($product['size'], 'Medium') !== false) ? 'checked' : ''; ?>>
                                Medium<?php echo (strpos($product['size'], 'Medium') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_size[]" value="Large" <?php echo (strpos($product['size'], 'Large') !== false) ? 'checked' : ''; ?>>
                                Large<?php echo (strpos($product['size'], 'Large') !== false) ? '(Current)' : ''; ?>
                            </label>
                        </div>
                        <h4>Select Product Analysis</h4>
                        <div class="checkbox-group">
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Pale" <?php echo (strpos($product['analysis'], 'Pale') !== false) ? 'checked' : ''; ?>>
                                Pale<?php echo (strpos($product['analysis'], 'Pale') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Fair" <?php echo (strpos($product['analysis'], 'Fair') !== false) ? 'checked' : ''; ?>>
                                Fair<?php echo (strpos($product['analysis'], 'Fair') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Medium" <?php echo (strpos($product['analysis'], 'Medium') !== false) ? 'checked' : ''; ?>>
                                Medium<?php echo (strpos($product['analysis'], 'Medium') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Olive" <?php echo (strpos($product['analysis'], 'Olive') !== false) ? 'checked' : ''; ?>>
                                Olive<?php echo (strpos($product['analysis'], 'Olive') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Naturally Brown" <?php echo (strpos($product['analysis'], 'Naturally Brown') !== false) ? 'checked' : ''; ?>>
                                Naturally Brown<?php echo (strpos($product['analysis'], 'Naturally Brown') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_analysis[]" value="Dark Brown" <?php echo (strpos($product['analysis'], 'Dark Brown') !== false) ? 'checked' : ''; ?>>
                                Dark Brown<?php echo (strpos($product['analysis'], 'Dark Brown') !== false) ? '(Current)' : ''; ?>
                            </label>
                        </div>
                        <h4>Select Undertone</h4>
                        <div class="checkbox-group">
                            <label class="tones">
                                <input type="checkbox" name="product_tone[]" value="Cool" <?php echo (strpos($product['tone'], 'Cool') !== false) ? 'checked' : ''; ?>>
                                Cool <?php echo (strpos($product['tone'], 'Cool') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_tone[]" value="Neutral" <?php echo (strpos($product['tone'], 'Neutral') !== false) ? 'checked' : ''; ?>>
                                Neutral <?php echo (strpos($product['tone'], 'Neutral') !== false) ? '(Current)' : ''; ?>
                            </label>
                            <label class="tones">
                                <input type="checkbox" name="product_tone[]" value="Warm" <?php echo (strpos($product['tone'], 'Warm') !== false) ? 'checked' : ''; ?>>
                                Warm <?php echo (strpos($product['tone'], 'Warm') !== false) ? '(Current)' : ''; ?>
                            </label>
                        </div>
                        <div class="image">
                            <?php
                            $images = @unserialize($product['img']);
                            if ($images === false && $product['img'] !== 'b:0;') {
                                $images = [$product['img']]; // Treat as a single image if unserializing fails
                            }
                            // Display existing images
                            if (!empty($images)) {
                                foreach ($images as $image) {
                                    echo '<div class="image-container" style="display: inline-block; position: relative; margin: 5px;">';
                                    echo '<img src="uploaded_img/' . htmlspecialchars($image) . '" alt="" style="width: 100px; height: 100px;">';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        <input type="file" accept="image/png, image/jpeg, image/jpg" name="product_images[]" class="box2" multiple>
                    </div>
                </div>
                <input type="submit" class="btn-add" name="edit_product" value="Edit Product">
                <button type="button" class="btn-cnl" id="btnCancelEdit">Cancel</button>
            </form>
        </div>
    </div>

    <!-- The Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Successfully updated</p>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById("myDropdown");
            dropdown.classList.toggle("show");
        }

        function showSelectColor() {
            document.getElementById("select_color_div").style.display = "block";
            document.getElementById("new_color_div").style.display = "none";
        }

        function showNewColor() {
            document.getElementById("select_color_div").style.display = "none";
            document.getElementById("new_color_div").style.display = "block";
        }

        document.getElementById("btnCancelEdit").onclick = function() {
            window.location.href = 'CRUD.php';
        };

        function showModal() {
            var modal = document.getElementById("successModal");
            modal.style.display = "block";
        }

        function closeModal() {
            var modal = document.getElementById("successModal");
            modal.style.display = "none";
            window.location.href = 'CRUD.php';
        }

        // Close the modal when the user clicks anywhere outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("successModal");
            if (event.target == modal) {
                modal.style.display = "none";
                window.location.href = 'CRUD.php';
            }
        };

        function deleteImage(imageName) {
            if (confirm('Are you sure you want to delete this image?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '<?php echo $_SERVER["PHP_SELF"]; ?>?id=<?php echo $product["id"]; ?>';

                const imageInput = document.createElement('input');
                imageInput.type = 'hidden';
                imageInput.name = 'delete_image';
                imageInput.value = imageName;
                form.appendChild(imageInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>