<?php
session_start();
include("../config/db.php");

// Auth Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    die("Error: Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture ID from the hidden input in edit_form.php
    $id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;
    
    if(!$id) {
        die("Error: No ID provided.");
    }

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $org = mysqli_real_escape_string($conn, $_POST['organization']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $eligibility = mysqli_real_escape_string($conn, $_POST['eligibility']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    
    // Image Handling
    $image_update_sql = "";
    if(!empty($_FILES["image"]["name"])) {
        $target_dir = "../images/"; // Ensure this folder exists
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        $db_path = "images/" . $file_name;
        
        if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_update_sql = ", image='$db_path'";
        }
    }

    // Update Query
    $sql = "UPDATE opportunities SET 
                title='$title', 
                organization='$org', 
                description='$desc', 
                eligibility='$eligibility', 
                deadline='$deadline' 
                $image_update_sql 
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        // This 'success' string is what your JavaScript is looking for to show the modal
        echo "success";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid Request.";
}
?>