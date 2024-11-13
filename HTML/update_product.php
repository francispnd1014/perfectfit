<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "perfectfit");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update status and tally
    $sql = "UPDATE product SET status = 1, tally = tally + 1 WHERE id = 24"; // Adjust the WHERE clause as needed

    if ($conn->query($sql) === TRUE) {
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $conn->close();
}
?>