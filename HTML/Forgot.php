<?php
require_once 'connection.php';
$conn = Database::getInstance()->getConnection();

if (isset($_GET['token'])) {
    $provided_token = $_GET['token'];

    
    $sql = "SELECT email, reset_token, token_expiry FROM users WHERE reset_token IS NOT NULL";
    $result = $conn->query($sql);
    $user_found = false;

    while($row = $result->fetch_assoc()) {
        
        if (new DateTime() > new DateTime($row['token_expiry'])) {
            
            $sql = "UPDATE users SET reset_token=NULL, token_expiry=NULL WHERE email='" . $row['email'] . "'";
            $conn->query($sql);
            continue;
        }

        
        if (password_verify($provided_token, $row['reset_token'])) {
            $email = $row['email'];
            $user_found = true;
            break;
        }
    }

    if ($user_found) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword === $confirmPassword) {
                $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);

                
                $sql = "UPDATE users SET password='$hashed_password', reset_token=NULL, token_expiry=NULL WHERE email='$email'";
                $conn->query($sql);

                
                echo '
                <style>
                    .modal {
                        display: block;
                        position: fixed;
                        z-index: 1;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        overflow: auto;
                        background-color: rgb(0,0,0);
                        background-color: rgba(0,0,0,0.4);
                    }
                    .modal-content {
                        background-color: #fefefe;
                        margin: 15% auto;
                        padding: 20px;
                        border: 1px solid #888;
                        width: 80%;
                        max-width: 300px;
                        text-align: center;
                        border-radius: 10px;
                    }
                    .close {
                        color: #aaa;
                        float: right;
                        font-size: 28px;
                        font-weight: bold;
                    }
                    .close:hover,
                    .close:focus {
                        color: black;
                        text-decoration: none;
                        cursor: pointer;
                    }
                </style>
                <div id="resetModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal()">&times;</span>
                        <p style="font-family: sans-serif;">Password has been reset.</p>
                        <button onclick="redirectToLogin()" style="background-color: #FFD700;">OK</button>
                    </div>
                </div>
                <script>
                    function closeModal() {
                        document.getElementById("resetModal").style.display = "none";
                    }
                    function redirectToLogin() {
                        window.location.href = "Login.php";
                    }
                </script>';
            } else {
                echo "Passwords do not match.";
            }
        } else {
            
            echo '
            <style>
                .reset-container {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100%;
                    background-color: #f2f2f2;
                }
                .reset-box {
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    width: 300px;
                }
                .reset-box h2 {
                    text-align: center;
                    margin-bottom: 20px;
                    font-family: sans-serif;
                }
                .reset-box input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    margin: 10px 0;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    font-family: sans-serif;
                }
                .reset-box button {
                    width: 100%;
                    padding: 10px;
                    background-color: #FFD700;
                    color: black;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-family: sans-serif;

                }
                .password-warning {
                    display: none;
                    color: red;
                    text-align: center;
                    margin-top: 10px;
                }
                .logo {
                    display: block;
                    margin-left: auto;
                    margin-right: auto;
                    width: 100%;
                }
            </style>
            <div class="reset-container">
                <div class="reset-box">
                    <a><img src="../IMAGES/RICH SABINIANS.png" class="logo"></a>
                    <h2>Reset Password</h2>
                    <form method="POST" onsubmit="return validatePasswords()">
                        <input type="password" id="new_password" name="new_password" required placeholder="New Password" onkeyup="checkPasswordMatch()">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password" onkeyup="checkPasswordMatch()">
                        <div class="password-warning" id="password-warning">Passwords do not match!</div>
                        <button type="submit">Reset Password</button>
                    </form>
                </div>
            </div>
            <script>
                function checkPasswordMatch() {
                    var password = document.getElementById("new_password").value;
                    var confirmPassword = document.getElementById("confirm_password").value;
                    var passwordWarning = document.getElementById("password-warning");
                    if (confirmPassword !== "" && password !== confirmPassword) {
                        passwordWarning.style.display = "block";
                    } else {
                        passwordWarning.style.display = "none";
                    }
                }

                function validatePasswords() {
                    var password = document.getElementById("new_password").value;
                    var confirmPassword = document.getElementById("confirm_password").value;
                    if (password !== confirmPassword) {
                        alert("Passwords do not match.");
                        return false;
                    }
                    return true;
                }
            </script>';
        }
    } else {
        echo "Invalid token.";
    }
} else {
    echo "No token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="icon" type="image/x-icon" href="../IMAGES/FAV.png">
</head>

<body>
</body>
</html>