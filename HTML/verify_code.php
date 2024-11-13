<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "g8gbV0noL$3&fA6x-GAMER";
$dbname = "perfectfit";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$response = ['success' => false, 'message' => ''];

if (isset($_POST['code'])) {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $email = $_SESSION['email']; // Get the email from the session

    $sql = "SELECT * FROM users WHERE email='$email' AND verification_code='$code'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Update the user's is_verified status
        $update_sql = "UPDATE users SET is_verified=1 WHERE email='$email'";
        if ($conn->query($update_sql) === TRUE) {
            $response['success'] = true;
            $response['redirect'] = 'Home User.php'; // Adjust as needed
        }
    } else {
        $response['message'] = "Invalid verification code.";
    }
}

$conn->close();
echo json_encode($response);
?>
