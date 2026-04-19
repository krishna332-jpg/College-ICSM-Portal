<?php
session_start();
include(__DIR__ . "/../config/db.php");

if(!isset($_SESSION['form_data'])){
    die("No form data found. Please fill the form first.");
}

$data = $_SESSION['form_data'];

/* ESCAPE DATA */
$title = mysqli_real_escape_string($conn, $data['title']);
$organization = mysqli_real_escape_string($conn, $data['organization']);
$description = mysqli_real_escape_string($conn, $data['description']);
$eligibility = mysqli_real_escape_string($conn, $data['eligibility']);
$deadline = $data['deadline'];
$type = mysqli_real_escape_string($conn, $data['type']);

/* RETRIEVE IMAGE PATH FROM SESSION */
$image = $_SESSION['final_image_path'] ?? "";

/* INSERT INTO DATABASE */
$sql = "INSERT INTO opportunities 
(title, organization, description, eligibility, deadline, image, type)
VALUES 
('$title','$organization','$description','$eligibility','$deadline','$image','$type')";

if(mysqli_query($conn, $sql)){
    $last_id = mysqli_insert_id($conn);

    /* CLEAR SESSIONS AFTER SUCCESSFUL INSERT */
    unset($_SESSION['form_data']);
    unset($_SESSION['final_image_path']);

    /* REDIRECT TO THE BUILDER WITH THE NEW ID */
    header("Location: apply_form_builder.php?id=".$last_id);
    exit();
} else {
    die("Database Error: " . mysqli_error($conn));
}
?>