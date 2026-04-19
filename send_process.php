<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $student_name = $_SESSION['student_name'] ?? "Student";
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // We leave thread_id NULL on the first message. 
    // The Admin side is now programmed to handle NULL thread_ids by using the row ID.
    $sql = "INSERT INTO messages (sender_id, sender_name, recipient_id, subject, message, folder, type, sender_role) 
            VALUES ('$user_id', '$student_name', 1, '$subject', '$message', 'inbox', 'student_query', 'student')";

    if (mysqli_query($conn, $sql)) {
        header("Location: student_mailbox.php?view=sent&status=success");
        exit();
    } else {
        die("Error: " . mysqli_error($conn));
    }
}
?>