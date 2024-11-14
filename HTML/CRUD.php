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

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = [];

if (isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $product_rent = $_POST['product_rent'];
    $product_size = isset($_POST['product_size']) ? implode(',', $_POST['product_size']) : '';
    $product_color = $_POST['product_color'];
    $new_color = $_POST['new_color'];
    $product_theme = $_POST['product_theme'];
    $product_analysis = isset($_POST['product_analysis']) ? implode(',', $_POST['product_analysis']) : '';
    $product_tone = isset($_POST['product_tone']) ? implode(',', $_POST['product_tone']) : '';
    $product_images = $_FILES['product_images'];

    if (empty($product_name) || empty($product_rent) || empty($product_size) || (empty($product_color) && empty($new_color)) || empty($product_theme) || empty($product_analysis) || empty($product_tone) || empty($product_images['name'][0])) {
        $message[] = 'Please fill out all fields.';
    } else {
        $color_to_use = !empty($new_color) ? $new_color : $product_color;
        $image_paths = [];

        for ($i = 0; $i < count($product_images['name']); $i++) {
            $product_image = $product_images['name'][$i];
            $product_image_tmp_name = $product_images['tmp_name'][$i];
            $product_image_folder = 'uploaded_img/' . $product_image;

            // Use a safe file name and escape the string for SQL
            $safe_file_name = $conn->real_escape_string($product_image);

            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($product_image_folder, PATHINFO_EXTENSION));

            // Check if image file is a valid image type
            $check = getimagesize($product_image_tmp_name);
            if ($check === false) {
                $uploadOk = 0;
                $message[] = 'File is not an image: ' . $product_image;
            }

            // Check file size (5MB maximum)
            if ($product_images["size"][$i] > 5000000) {
                $uploadOk = 0;
                $message[] = 'Sorry, your file is too large: ' . $product_image;
            }

            // Allow only certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $uploadOk = 0;
                $message[] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed: ' . $product_image;
            }

            // Check if upload is OK and move the file
            if ($uploadOk == 1) {
                if (move_uploaded_file($product_image_tmp_name, $product_image_folder)) {
                    $image_paths[] = $safe_file_name;
                } else {
                    $message[] = 'Could not upload image: ' . $product_image;
                }
            }
        }

        if (count($image_paths) == count($product_images['name'])) {
            $image_paths_serialized = serialize($image_paths);
            $insert = "INSERT INTO product (name, price, size, color, theme, analysis, tone, img, tally) VALUES ('$product_name', '$product_rent', '$product_size', '$color_to_use', '$product_theme', '$product_analysis', '$product_tone', '$image_paths_serialized', 0)";
            $upload = mysqli_query($conn, $insert);
            if ($upload) {
                $message[] = 'New product added successfully.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $message[] = 'Could not add the product.';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM product WHERE id = $id");
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query to get distinct colors from the database
$color_query = "SELECT DISTINCT color FROM product";
$color_result = mysqli_query($conn, $color_query);

$select = mysqli_query($conn, "SELECT * FROM product");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="font/css/all.min.css">
    <link rel="stylesheet" href="../CSS/CRUD.css">
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

        <!-- The Modal -->
        <div id="myModal" class="modal">

            <!-- Modal content -->
            <div class="modal-content">
                <div class="container">
                    <div class="admin-product-form-container">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                            <h3>Add Product</h3>
                            <input type="text" placeholder="Product name" name="product_name" class="box">
                            <input type="text" placeholder="Product rent" name="product_rent" class="box">
                            <div class="option">
                                <button type="button" class="btn-option" onclick="showSelectColor()">Select Color</button>
                                <button type="button" class="btn-option" onclick="showNewColor()">Add New Color</button>
                            </div>
                            <div id="select_color_div">
                                <select name="product_color" class="box1">
                                    <option value="" disabled selected>Select Product Color</option>
                                    <?php
                                    if (mysqli_num_rows($color_result) > 0) {
                                        while ($row = mysqli_fetch_assoc($color_result)) {
                                            echo '<option value="' . $row['color'] . '">' . $row['color'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div id="new_color_div" style="display: none;">
                                <input type="text" placeholder="New color" name="new_color" class="box">
                            </div>
                            <select name="product_theme" class="box1">
                                <option value="" disabled selected>Select Product Theme</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Prom">Prom</option>
                                <option value="Formal">Formal</option>
                                <option value="Debut">Debut</option>
                            </select>
                            <h4>Select Size</h4>
                            <div class="checkbox-group">
                                <label class="tones"><input type="checkbox" name="product_size[]" value="Extra Small"> Extra Small</label>
                                <label class="tones"><input type="checkbox" name="product_size[]" value="Small"> Small</label>
                                <label class="tones"><input type="checkbox" name="product_size[]" value="Medium"> Medium</label>
                                <label class="tones"><input type="checkbox" name="product_size[]" value="Large"> Large</label>
                            </div>
                            <h4>Select Product Analysis</h4>
                            <div class="checkbox-group">
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Pale"> Pale</label>
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Fair"> Fair</label>
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Medium"> Medium</label>
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Olive"> Olive</label>
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Naturally Brown"> Naturally Brown</label>
                                <label class="tones"><input type="checkbox" name="product_analysis[]" value="Dark Brown"> Dark Brown</label>
                            </div>
                            <h4>Select Undertone</h4>
                            <div class="checkbox-group">
                                <label class="tones"><input type="checkbox" name="product_tone[]" value="Cool"> Cool</label>
                                <label class="tones"><input type="checkbox" name="product_tone[]" value="Neutral"> Neutral</label>
                                <label class="tones"><input type="checkbox" name="product_tone[]" value="Warm"> Warm</label>
                            </div>
                            <input type="file" accept="image/png, image/jpeg, image/jpg" name="product_images[]" class="box2" multiple>
                            <input type="submit" class="btn-add" name="add_product" value="Add Product">
                            <button type="button" class="btn-cnl" id="btnCancel">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="content">
            <div class="product-display">
                <button class="btn-add-product" id="btnAddProduct">Add New Product</button>

                <?php while ($row = mysqli_fetch_assoc($select)) { ?>
                    <div class="card">
                        <div class="image">
                            <?php
                            $images = @unserialize($row['img']);
                            if ($images === false && $row['img'] !== 'b:0;') {
                                $images = [$row['img']]; // Treat as a single image if unserializing fails
                            }
                            // Display only the first image
                            if (!empty($images)) {
                                $image = $images[0];
                                echo '<img src="uploaded_img/' . $image . '" alt="">';
                            }
                            ?>
                        </div>
                        <div class="caption">
                            <p class="product_name ellipsis"><?php echo $row['name']; ?></p>
                            <p class="category">Rent price</p>
                            <p class="price"><b>₱ <?php echo number_format($row['price'], 2); ?></b></p>
                        </div>
                        <a href="Edit.php?id=<?php echo $row['id']; ?>"><button class="edit">Edit</button></a>
                        <a href="CRUD.php?delete=<?php echo $row['id']; ?>"><button class="delete"> <i class="fas fa-trash"></i> Delete </button></a>
                    </div>
                <?php } ?>
            </div>
        </main>
    </div>
    <div id="deleteModal" class="modal">
        <div class="modal-content1">
            <p>Are you sure you want to delete this item?</p>
            <button id="confirmDelete" class="delete1">Confirm</button>
            <button id="cancelDelete" class="delete2">Cancel</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById("deleteModal");
            var span = document.getElementsByClassName("close")[0];
            var confirmDeleteBtn = document.getElementById("confirmDelete");
            var cancelDeleteBtn = document.getElementById("cancelDelete");
            var deleteLinks = document.querySelectorAll('a[href*="delete="]');

            deleteLinks.forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var deleteUrl = this.href;
                    modal.style.display = "block";

                    confirmDeleteBtn.onclick = function() {
                        window.location.href = deleteUrl;
                    }

                    cancelDeleteBtn.onclick = function() {
                        modal.style.display = "none";
                    }

                    span.onclick = function() {
                        modal.style.display = "none";
                    }

                    window.onclick = function(event) {
                        if (event.target == modal) {
                            modal.style.display = "none";
                        }
                    }
                });
            });
        });

        function toggleDropdown() {
            var dropdown = document.getElementById("myDropdown");
            dropdown.classList.toggle("show");
        }

        function toggleColorInput() {
            var colorOption = document.getElementById("color_option").value;
            var selectColorDiv = document.getElementById("select_color_div");
            var newColorDiv = document.getElementById("new_color_div");

            if (colorOption === "select") {
                selectColorDiv.style.display = "block";
                newColorDiv.style.display = "none";
            } else if (colorOption === "new") {
                selectColorDiv.style.display = "none";
                newColorDiv.style.display = "block";
            }
        }

        var product_id = document.getElementsByClassName("add");
        for (var i = 0; i < product_id.length; i++) {
            product_id[i].addEventListener("click", function(event) {
                var target = event.target;
                var id = target.getAttribute("data-id");
                var xml = new XMLHttpRequest();
                xml.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        var data = JSON.parse(this.responseText);
                        target.innerHTML = data.in_cart;
                        document.getElementById("badge").innerHTML = data.num_cart + 1;
                    }
                };

                xml.open("GET", "connection.php?id=" + id, true);
                xml.send();
            });
        }
        // Get the modal
        var modal = document.getElementById("myModal");

        // Get the button that opens the modal
        var btn = document.getElementById("btnAddProduct");

        // Get the cancel button that closes the modal
        var cancelBtn = document.getElementById("btnCancel");

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on the cancel button, close the modal
        cancelBtn.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function showSelectColor() {
            document.getElementById("select_color_div").style.display = "block";
            document.getElementById("new_color_div").style.display = "none";
        }

        function showNewColor() {
            document.getElementById("select_color_div").style.display = "none";
            document.getElementById("new_color_div").style.display = "block";
        }
    </script>
</body>

</html>

<?php $conn->close(); ?>