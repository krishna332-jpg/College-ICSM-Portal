<?php
session_start();
require_once("../config/db.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// AUTH CHECK
if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// BASIC DATA
$student_id = $_SESSION['register_no'] ?? $_POST['register_no'] ?? '';
$op_id = intval($_POST['op_id'] ?? 0);

// FOLDER
$upload_dir = "../uploads/";
if(!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// STORE ALL DATA HERE
$form_data = [];

/* =========================
   1. HANDLE NORMAL INPUTS
========================= */
foreach($_POST as $key => $value) {

    // skip unwanted
    if(in_array($key, ['op_id'])) continue;

    if(!empty($value)) {
        $form_data[$key] = trim($value);
    }
}

/* =========================
   2. HANDLE FILES
========================= */
foreach($_FILES as $key => $file) {

    if(isset($file['name']) && $file['name'] != '') {

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . "_" . rand(1000,9999) . "." . $ext;

        $target = $upload_dir . $filename;

        if(move_uploaded_file($file['tmp_name'], $target)) {
            $form_data[$key] = $filename; // SAVE FILE NAME
        }
    }
}

/* =========================
   3. SAVE JSON
========================= */
$json_data = json_encode($form_data, JSON_UNESCAPED_UNICODE);

/* =========================
   4. INSERT INTO DB
========================= */
$sql = "INSERT INTO applications (student_id, opportunity_id, form_data, status)
        VALUES ('$student_id', $op_id, '$json_data', 'Pending')";

if(mysqli_query($conn, $sql)) {
    echo "success";
} else {
    echo "DB Error: " . mysqli_error($conn);
}
?>