<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

require_once 'connection.php';
$conn = Database::getInstance()->getConnection();

function cleanupExpiredCodes($conn)
{
    $cleanup_query = "UPDATE users SET verification_code = NULL, verification_expiry = NULL 
                     WHERE verification_expiry < NOW() AND is_verified = 0";
    $conn->query($cleanup_query);
}

$login_error = '';
$register_error = '';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];


    $email = mysqli_real_escape_string($conn, $email);


    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();


        if ($user_data['is_verified'] == 1) {

            if (password_verify($password, $user_data['password'])) {

                $_SESSION['email'] = $user_data['email'];
                $_SESSION['fname'] = $user_data['fname'];
                $user_type = $user_data['type'];

                if ($user_type == 'admin') {
                    header("Location: Dashboard.php");
                } else {
                    header("Location: Home User.php");
                }
                exit();
            } else {

                $login_error = "Invalid email or password.";
            }
        } else {

            $login_error = "Please verify your email before logging in.";
        }
    } else {

        $login_error = "Invalid email or password.";
    }
}

if (isset($_POST['submitr'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $sname = mysqli_real_escape_string($conn, $_POST['sname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $password = mysqli_real_escape_string($conn, $_POST['password1']);
    $cpassword = mysqli_real_escape_string($conn, $_POST['cpassword1']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']); // Add this line
    $default_pfp = 'uploaded_img/DEF.jpg';


    $email_check_query = "SELECT * FROM users WHERE email='$email'";
    $email_check_result = $conn->query($email_check_query);

    if ($email_check_result->num_rows > 0) {
        $register_error = "This email has been used already.";
    } elseif ($password !== $cpassword) {
        $register_error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_code = rand(100000, 999999);
        $hashed_verification_code = password_hash($verification_code, PASSWORD_DEFAULT);
        $expiry_time = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Update the INSERT query to include gender
        $insert_query = "INSERT INTO users (fname, sname, email, contact, password, pfp, verification_code, is_verified, verification_expiry, gender) 
        VALUES ('$fname', '$sname', '$email', '$contact', '$hashed_password', '$default_pfp', '$hashed_verification_code', 0, '$expiry_time', '$gender')";

        if ($conn->query($insert_query) === TRUE) {

            $mail = new PHPMailer(true);

            try {

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'richsabinianpampang@gmail.com';
                $mail->Password = 'nqryqhxsnksaxmvv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;


                $mail->setFrom('your-email@gmail.com', 'PerfectFit');
                $mail->addAddress($email);


                $mail->isHTML(true);
                $mail->Subject = 'Verify your email address';
                $mail->Body = "Your verification code is: <strong>" . $verification_code . "</strong>";

                $mail->send();
                $register_success = "Successfully registered! Please check your email to verify your account.";
                $_SESSION['email'] = $email;
            } catch (Exception $e) {
                $register_error = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $register_error = "Error: " . $insert_query . "<br>" . $conn->error;
        }
    }
}

if (isset($_POST['forgot_submit'])) {
    $forgot_email = $_POST['forgot_email'];
    $forgot_email = mysqli_real_escape_string($conn, $forgot_email);


    $sql = "SELECT * FROM users WHERE email='$forgot_email' AND reset_token IS NULL";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $token = bin2hex(random_bytes(50));
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);


        $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $sql = "UPDATE users SET reset_token='$hashed_token', token_expiry='$expiry_time' WHERE email='$forgot_email'";
        $conn->query($sql);


        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'richsabinianpampang@gmail.com';
            $mail->Password = 'nqryqhxsnksaxmvv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;


            $mail->setFrom('your-email@gmail.com', 'PerfectFit');
            $mail->addAddress($forgot_email);


            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the link to reset your password: <a href='http://app-perfectfit.com/HTML/Forgot.php?token=$token'>Reset Password</a>";

            $mail->send();
            $reset_success = "If an account exists with this email, a password reset link will be sent.";
        } catch (Exception $e) {

            echo "<div class='error-message'>An error occurred. Please try again later.</div>";
        }
    } else {

        $reset_success = "If an account exists with this email, a password reset link will be sent.";
    }
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];


    $sql = "SELECT email FROM users WHERE reset_token='$token'";
    $result = $conn->query($sql);
    $email = $result->fetch_assoc()['email'];

    if ($email) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $newPassword = $_POST['new_password'];
            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);


            $sql = "UPDATE users SET password='$hashed_password', reset_token=NULL WHERE email='$email'";
            $conn->query($sql);

            echo "Password has been reset.";
        } else {

            echo '
            <form method="POST">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="submit">Reset Password</button>
            </form>';
        }
    } else {
    }
} else {
}


if (isset($_POST['verify_code'])) {
    cleanupExpiredCodes($conn);
    $entered_code = $_POST['verification_code'];
    $email = $_SESSION['email'];

    $sql = "SELECT * FROM users WHERE email='$email' AND verification_expiry > NOW()";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        if (password_verify($entered_code, $user_data['verification_code'])) {
            $update_sql = "UPDATE users SET 
                          is_verified = 1, 
                          verification_code = NULL,
                          verification_expiry = NULL 
                          WHERE email='$email'";
            $conn->query($update_sql);
            header("Location: Home User.php");
            exit();
        } else {
            $verification_error = "Invalid verification code.";
        }
    } else {
        $verification_error = "Verification code expired or user not found.";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../CSS/LoginSignup.css">
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
    <title>Signup</title>
    <style>
    </style>
</head>

<body>
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form id="register" class="input-group2" action="" method="post" autocomplete="off" onsubmit="return validatePassword()">
                <h1>Sign Up</h1>
                <input type="text" name="fname" class="input-field" placeholder="First Name" required>
                <input type="text" name="sname" class="input-field" placeholder="Surname" required>
                <input type="email" name="email" class="input-field" placeholder="Email" required>
                <input type="text" name="contact" class="input-field" placeholder="Contact" required maxlength="11" pattern="\d{11}" title="Cellphone">
                <div class="gender-container">
                    <div class="radio-group">
                        <input type="radio" id="male" name="gender" value="male" required>
                        <label for="male">Male</label>
                        <input type="radio" id="female" name="gender" value="female" required>
                        <label for="female">Female</label>
                    </div>
                </div>
                <div class="input-field-container">
                    <input type="password" name="password1" id="password1" class="input-field" placeholder="Password" required minlength="8" autocomplete="new-password">
                    <i class="fa fa-eye toggle-password" onclick="togglePasswordVisibility('password1')"></i>
                </div>
                <div class="input-field-container">
                    <input type="password" name="cpassword1" id="cpassword1" class="input-field" placeholder="Confirm Password" onkeyup="checkPasswordMatch()" required minlength="8" autocomplete="new-password">
                    <i class="fa fa-eye toggle-password" onclick="togglePasswordVisibility('cpassword1')"></i>
                </div>
                <div class="password-warning" id="password-warning">Passwords do not match!</div>
                <div class="password-length-warning" id="password-length-warning">Password must be at least 8 characters long!</div>
                <?php if (!empty($register_error)) : ?>
                    <div class="warning-message"><?php echo $register_error; ?></div>
                <?php elseif (!empty($register_success)) : ?>
                    <div class="success-message"><?php echo $register_success; ?></div>
                <?php endif; ?>
                <input type="submit" name="submitr" value="Submit" class="submit-btn">
            </form>
        </div>

        <div class="form-container sign-in">
            <form id="login" class="input-group" action="" method="post" autocomplete="on">
                <a href="../HTML/Index.php"><img src="../IMAGES/RICH SABINIANS.png" class="logo"></a>
                <h1>Sign In</h1>
                <input type="email" name="email" class="input-field" placeholder="Email" required>
                <div class="input-field-container">
                    <input type="password" name="password" id="login_password" class="input-field" placeholder="Password" required>
                    <i class="fa fa-eye toggle-password" onclick="togglePasswordVisibility('login_password')"></i>
                </div>
                <?php if (!empty($login_error)) : ?>
                    <div class="warning-message"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <a href="javascript:void(0);" onclick="openForgotPasswordModal()">forgot password?</a>
                <input type="submit" name="submit" value="Login" class="submit-btn">
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="login1">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to use all of site features</p>
                    <button class="hidden" id="register1">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div id="verificationModal" class="modal" style="display: <?php echo isset($_POST['verify_code']) || !empty($register_success) ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <h2>Verify Your Email</h2>
            <p>Enter the verification code sent to your email:</p>
            <form id="verificationForm" method="post">
                <input type="text" name="verification_code" class="input-field" required placeholder="Verification Code">
                <?php if (!empty($verification_error)) : ?>
                    <div class="warning-message"><?php echo $verification_error; ?></div>
                <?php endif; ?>
                <input type="submit" name="verify_code" value="Verify" class="submit-btn">
            </form>
        </div>
    </div>

    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeForgotPasswordModal()">&times;</span>
            <h2>Forgot Password</h2>
            <form id="forgotPasswordForm" method="post">
                <input type="email" name="forgot_email" class="input-field" required placeholder="Enter your email">
                <input type="submit" name="forgot_submit" value="Submit" class="submit-btn">
            </form>
        </div>
    </div>
    <div id="resetLinkSentModal" class="modal" style="display: <?php echo !empty($reset_success) ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <span class="close" onclick="closeResetLinkSentModal()">&times;</span>
            <h2>Reset Link Sent</h2>
            <p><?php echo $reset_success; ?></p>
            <button class="submit-btn" onclick="closeResetLinkSentModal()">OK</button>
        </div>
    </div>
    <div id="emailUsedModal" class="modal" style="display: <?php echo !empty($register_error) && $register_error == 'This email has been used already.' ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <span class="close" onclick="closeEmailUsedModal()">&times;</span>
            <h2>Email Already Used</h2>
            <p><?php echo $register_error; ?></p>
            <button class="submit-btn" onclick="closeEmailUsedModal()">OK</button>
        </div>
    </div>

    <script>
        function closeResetLinkSentModal() {
            document.getElementById('resetLinkSentModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('resetLinkSentModal');
            if (event.target == modal) {
                closeResetLinkSentModal();
            }
        }

        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register1');
        const loginBtn = document.getElementById('login1');

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });

        function validatePassword() {
            var password = document.getElementById("password1").value;
            var confirmPassword = document.getElementById("cpassword1").value;
            var passwordLengthWarning = document.getElementById("password-length-warning");

            if (password.length < 8) {
                passwordLengthWarning.style.display = "block";
                return false;
            } else {
                passwordLengthWarning.style.display = "none";
            }

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }
            return true;
        }

        function checkPasswordMatch() {
            var password = document.getElementById("password1").value;
            var confirmPassword = document.getElementById("cpassword1").value;
            var passwordWarning = document.getElementById("password-warning");
            if (confirmPassword !== "" && password !== confirmPassword) {
                passwordWarning.style.display = "block";
            } else {
                passwordWarning.style.display = "none";
            }
        }

        function togglePasswordVisibility(id) {
            var field = document.getElementById(id);
            var icon = field.nextElementSibling;
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        function closeVerificationModal() {
            var modal = document.getElementById("verificationModal");
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            var modal = document.getElementById("verificationModal");
            if (event.target == modal) {
                closeVerificationModal();
            }
        }

        function openForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'block';
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('forgotPasswordModal');
            if (event.target == modal) {
                closeForgotPasswordModal();
            }
        }

        function closeEmailUsedModal() {
            document.getElementById('emailUsedModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('emailUsedModal');
            if (event.target == modal) {
                closeEmailUsedModal();
            }
        }
    </script>
</body>

</html>