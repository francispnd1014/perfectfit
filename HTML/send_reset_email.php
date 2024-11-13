<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Generate a unique token
    $token = bin2hex(random_bytes(50));

    // Save the token and email in the database (pseudo code)
    // saveToken($email, $token);

    // Create the reset link
    $resetLink = "http://yourdomain.com/Forgot.php?token=" . $token;

    // Send the email
    $subject = "Password Reset Request";
    $message = "Click the following link to reset your password: " . $resetLink;
    $headers = "From: no-reply@yourdomain.com";

    if (mail($email, $subject, $message, $headers)) {
        echo "Email sent successfully.";
    } else {
        echo "Failed to send email.";
    }
}
?>