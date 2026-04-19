<?php
session_start();
include("../config/db.php");

/* AUTH CHECK */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    exit("Unauthorized Access");
}

if(isset($_GET['id'])){
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // 1. Fetch the type so we know where to redirect (e.g., internship -> delete_select.php?type=internship)
    $res = mysqli_query($conn, "SELECT type FROM opportunities WHERE id = '$id'");
    $row = mysqli_fetch_assoc($res);
    
    if($row) {
        $type = $row['type'];

        // 2. THE SOFT DELETE
        // Sets status to 'deleted' so students don't see it, but records remain in the DB
        $update_query = "UPDATE opportunities 
                         SET status = 'deleted', 
                             is_archived = 1 
                         WHERE id = '$id'";

        if(mysqli_query($conn, $update_query)){
            // Redirect back to the selection page with the original type
            header("Location: delete_select.php?type=$type&msg=removed"); 
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>