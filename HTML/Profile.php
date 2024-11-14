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

$email = $_SESSION['email'];
$query = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fname = $row['fname'];
    $sname = $row['sname'];
    $fullname = $row['fname'] . ' ' . $row['sname'];
    $profile_picture = $row['pfp'];
    $_SESSION['profile_picture'] = $profile_picture;
    $contact = $row['contact'];
    $pfp = $row['pfp'];
    $currentPassword = $row['password']; // Assuming the password is stored as plain text
} else {
    header("Location: Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update-details'])) {
        $newFname = $_POST['fname'];
        $newSname = $_POST['sname'];
        $newContact = $_POST['contact'];

        // Update user details first
        $update_details_query = "UPDATE users SET fname='$newFname', sname='$newSname', contact='$newContact' WHERE email='$email'";

        if ($conn->query($update_details_query) === TRUE) {
            $fname = $newFname;
            $sname = $newSname;
            $contact = $newContact;
            $_SESSION['successMessage'] = "Profile updated successfully.";
        } else {
            $_SESSION['errorMessage'] = "Error updating profile details: " . $conn->error;
        }

        // Check if a new profile picture is uploaded
        if (!empty($_FILES['profile-pic']['name'])) {
            // Handle profile picture upload
            $target_dir = "uploaded_img/";
            $file_name = basename($_FILES["profile-pic"]["name"]);
            $target_file = $target_dir . $file_name;

            // Use a safe file name and escape the string for SQL
            $safe_file_name = $conn->real_escape_string($file_name);

            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if image file is a valid image type
            $check = getimagesize($_FILES["profile-pic"]["tmp_name"]);
            if ($check === false) {
                $uploadOk = 0;
                $_SESSION['errorMessage'] = "File is not an image.";
            }

            // Check file size (5MB maximum)
            if ($_FILES["profile-pic"]["size"] > 5000000) {
                $uploadOk = 0;
                $_SESSION['errorMessage'] = "Sorry, your file is too large.";
            }

            // Allow only certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $uploadOk = 0;
                $_SESSION['errorMessage'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            }

            // Check if upload is OK and move the file
            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["profile-pic"]["tmp_name"], $target_file)) {
                    // If file is uploaded, update the profile picture path in the database
                    $update_pfp_query = "UPDATE users SET pfp='$target_file' WHERE email='$email'";

                    if ($conn->query($update_pfp_query) === TRUE) {
                        $pfp = $target_file;  // Update the profile picture path
                        $_SESSION['pfp'] = $target_file;  // Update session variable
                        $_SESSION['successMessage'] = "Profile picture updated successfully.";
                    } else {
                        $_SESSION['errorMessage'] = "Error updating profile picture path: " . $conn->error;
                    }
                } else {
                    $_SESSION['errorMessage'] = "Sorry, there was an error uploading your file.";
                }
            }
        }
    }


    if (isset($_POST['save-pass'])) {
        $currentPasswordInput = $_POST['current-password'];
        $newPassword = $_POST['new-password'];
        $repeatNewPassword = $_POST['repeat-new-password'];

        // Verify the current password using password_verify
        if (password_verify($currentPasswordInput, $currentPassword)) {
            if ($newPassword === $repeatNewPassword) {
                // Hash the new password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update with hashed password
                $update_password_query = "UPDATE users SET password='$hashedNewPassword' WHERE email='$email'";
                if ($conn->query($update_password_query) === TRUE) {
                    $_SESSION['successMessage'] = "Password updated successfully.";
                } else {
                    $_SESSION['errorMessage'] = "Error updating password: " . $conn->error;
                }
            } else {
                $_SESSION['errorMessage'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['errorMessage'] = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../CSS/Profile.css">
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
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-left">
                        <div class="list-group">
                            <a class="list-group-item active" onclick="showTab('general')">General</a>
                            <a class="list-group-item" onclick="showTab('change-password')">Change Password</a>
                        </div>
                    </div>
                    <div class="col-right">
                        <div id="general" class="tab-content active">
                            <div class="media-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" id="edit-details-form" onsubmit="return showConfirmationPopup(event)">
                                    <div class="card-body media align-items-center">
                                        <div class="profile-img-container">
                                            <img src="<?php echo htmlspecialchars($pfp); ?>" alt="Profile Picture" class="profile-img" id="profile-img">
                                            <label class="upload-btn">
                                                Upload Photo
                                                <input type="file" name="profile-pic" class="account-settings-fileinput" onchange="loadFile(event)">
                                            </label>
                                        </div>
                                    </div>
                                    <hr class="divider">
                                    <div class="media-body">
                                        <div class="form-group">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="fname" value="<?php echo htmlspecialchars($fname); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="sname" value="<?php echo htmlspecialchars($sname); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">E-mail</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly disabled>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($contact); ?>">
                                        </div>
                                        <button type="submit" name="update-details" class="save">Save Changes</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                        <div id="change-password" class="tab-content">
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="change-password-form" onsubmit="return showConfirmationPopup(event)">
                                    <div class="form-group">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current-password" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new-password" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Repeat New Password</label>
                                        <input type="password" class="form-control" name="repeat-new-password" required>
                                    </div>
                                    <button type="submit" name="save-pass" class="save">Save Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <p id="popup-message"></p>
            <div id="popup-buttons" style="display: none;">
                <button onclick="confirmAction()" class="Yes">Yes</button>
                <button onclick="cancelAction()" class="No">No</button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['successMessage'])): ?>
        <script>
            document.getElementById('popup-message').innerText = "<?php echo $_SESSION['successMessage']; ?>";
            document.getElementById('popup').style.display = 'block';
            <?php unset($_SESSION['successMessage']); ?>
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['errorMessage'])): ?>
        <script>
            document.getElementById('popup-message').innerText = "<?php echo $_SESSION['errorMessage']; ?>";
            document.getElementById('popup').style.display = 'block';
            <?php unset($_SESSION['errorMessage']); ?>
        </script>
    <?php endif; ?>

    <script>
        function toggleDropdown() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        function showTab(tabName) {
            var i;
            var x = document.getElementsByClassName("tab-content");
            var tabs = document.getElementsByClassName("list-group-item");

            // Remove active class from all tab contents
            for (i = 0; i < x.length; i++) {
                x[i].classList.remove("active");
            }

            // Remove active class from all tabs
            for (i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }

            // Add active class to the clicked tab and corresponding tab content
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }

        var loadFile = function(event) {
            var output = document.getElementById('profile-img');
            output.src = URL.createObjectURL(event.target.files[0]);
        };

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        var formToSubmit = null;

        function showConfirmationPopup(event) {
            event.preventDefault();
            document.getElementById('popup-message').innerText = "Are you sure you want to save changes?";
            document.getElementById('popup').style.display = 'block';
            document.getElementById('popup-buttons').style.display = 'block';
            formToSubmit = event.target;
        }

        function confirmAction() {
            document.getElementById('popup').style.display = 'none';
            if (formToSubmit) {
                // Determine which form is being submitted and add the appropriate hidden input
                if (formToSubmit.id === 'edit-details-form') {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'update-details';
                    formToSubmit.appendChild(hiddenInput);
                } else if (formToSubmit.id === 'change-password-form') {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'save-pass';
                    formToSubmit.appendChild(hiddenInput);
                }

                formToSubmit.submit();
            }
        }

        function cancelAction() {
            document.getElementById('popup').style.display = 'none';
            formToSubmit = null;
        }

        // Close the popup when clicking outside of the popup content
        window.onclick = function(event) {
            var popup = document.getElementById('popup');
            if (event.target == popup) {
                popup.style.display = "none";
            }
        }
        var loadFile = function(event) {
            var output = document.getElementById('profile-img');
            output.src = URL.createObjectURL(event.target.files[0]);
        };
    </script>
</body>

</html>