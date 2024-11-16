<?php
require_once 'connection.php';
$conn = Database::getInstance()->getConnection();

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Find the user with the given token
    $sql = "SELECT * FROM users WHERE verification_token='$token'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Set the user as verified
        $update_query = "UPDATE users SET is_verified=TRUE, verification_token=NULL WHERE verification_token='$token'";

        if ($conn->query($update_query) === TRUE) {
            echo "Your email has been verified! You can now log in.";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Invalid token.";
    }
}

$conn->close();
?>
