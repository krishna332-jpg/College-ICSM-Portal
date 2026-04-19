<?php
session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Get the form fields (JSON) from the builder
    // We use a fallback empty array if nothing is sent to prevent SQL errors
    $fields = isset($_POST['final_fields']) ? mysqli_real_escape_string($conn, $_POST['final_fields']) : '[]';
    
    // 2. Check if we are updating an existing record or creating a new one
    $update_id = isset($_POST['update_id']) ? mysqli_real_escape_string($conn, $_POST['update_id']) : null;

    if ($update_id) {
        /* ✅ UPDATE LOGIC 
           Updates only the form fields for an existing opportunity.
        */
        $sql = "UPDATE opportunities SET form_fields = '$fields' WHERE id = '$update_id'";
        
        if (mysqli_query($conn, $sql)) {
            echo "success";
        } else {
            echo "Update Error: " . mysqli_error($conn);
        }

    } else {
        /* ✅ INSERT LOGIC (New Entry)
           Standard creation logic using the session data from add_form.php
        */
        if (!isset($_SESSION['temp_opp'])) {
            exit("Error: No session data found. Please restart the application process.");
        }

        $data = $_SESSION['temp_opp'];
        
        $title = mysqli_real_escape_string($conn, $data['title']);
        $type = mysqli_real_escape_string($conn, $data['type']);
        $org = mysqli_real_escape_string($conn, $data['organization']);
        $desc = mysqli_real_escape_string($conn, $data['description']);
        $elig = mysqli_real_escape_string($conn, $data['eligibility']);
        $deadline = mysqli_real_escape_string($conn, $data['deadline']);
        
        // Handle image key carefully: check if 'image_path' or 'image' exists in session
        $img_raw = $data['image_path'] ?? $data['image'] ?? '';
        $img = mysqli_real_escape_string($conn, $img_raw);

        $sql = "INSERT INTO opportunities (title, type, organization, description, eligibility, deadline, image, form_fields, status) 
                VALUES ('$title', '$type', '$org', '$desc', '$elig', '$deadline', '$img', '$fields', 'active')";

        if (mysqli_query($conn, $sql)) {
            // Success! Clear the session so it's fresh for the next one
            unset($_SESSION['temp_opp']); 
            echo "success";
        } else {
            echo "Insert Error: " . mysqli_error($conn);
        }
    }
} else {
    echo "Invalid request method.";
}
?>