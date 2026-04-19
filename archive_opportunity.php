<?php
include("../config/db.php");

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // We don't DELETE, we just ARCHIVE.
    // This makes it invisible to Admin/Lists, but keeps it for Student Tracking.
    $query = "UPDATE opportunities SET is_archived = 1 WHERE id = $id";
    
    if(mysqli_query($conn, $query)) {
        header("Location: reports.php?msg=archived");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>