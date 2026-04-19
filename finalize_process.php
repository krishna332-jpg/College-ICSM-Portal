<?php
session_start();
include("../config/db.php");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['opp_id'])) {
    $opp_id = intval($_POST['opp_id']);

    // 1. Mark reports as finalized
    $update_report = "UPDATE application_reports SET finalized = 1 WHERE opportunity_id = $opp_id";
    
    // 2. Sync application statuses
    $sync_applications = "UPDATE applications a
                          JOIN application_reports r ON a.id = r.application_id
                          SET a.status = r.review_status
                          WHERE a.opportunity_id = $opp_id";

    // 3. CRITICAL: Update the Opportunity itself so the Reports page can see it's done
    $update_opp_status = "UPDATE opportunities SET status = 'finalized' WHERE id = $opp_id";

    if(mysqli_query($conn, $update_report) && mysqli_query($conn, $sync_applications) && mysqli_query($conn, $update_opp_status)) {
        header("Location: reports.php?status=success");
        exit();
    } else {
        die("Error: " . mysqli_error($conn));
    }
}
?>