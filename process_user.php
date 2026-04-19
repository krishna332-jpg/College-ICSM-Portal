<?php
session_start();
include("../config/db.php");

$action = $_POST['action'] ?? '';
$role   = $_POST['role'] ?? '';

if($action == "add"){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = $_POST['dob'];

    /* CHECK EMAIL DUPLICATE (both admin & student) */
    $checkEmailAdmin = mysqli_query($conn, "SELECT id FROM admins WHERE email='$email'");
    $checkEmailStudent = mysqli_query($conn, "SELECT id FROM students WHERE email='$email'");

    if(mysqli_num_rows($checkEmailAdmin) > 0 || mysqli_num_rows($checkEmailStudent) > 0){
        echo "<script>
            alert('Email already exists!');
            window.history.back();
        </script>";
        exit();
    }

    if($role == "admin"){

        $sql = "INSERT INTO admins (name, email, password, dob)
                VALUES ('$name', '$email', '$password', '$dob')";

    } else {

        $register_no = mysqli_real_escape_string($conn, $_POST['register_no']);

        /* CHECK REGISTER NUMBER DUPLICATE */
        $checkReg = mysqli_query($conn, "SELECT id FROM students WHERE register_no='$register_no'");
        if(mysqli_num_rows($checkReg) > 0){
            echo "<script>
                alert('Register Number already exists!');
                window.history.back();
            </script>";
            exit();
        }

        $sql = "INSERT INTO students (name, email, password, register_no, dob)
                VALUES ('$name', '$email', '$password', '$register_no', '$dob')";
    }

    if(mysqli_query($conn, $sql)){
        echo "<script>
            alert('User Added Successfully!');
            window.location.href='admin_dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Something went wrong!');
            window.history.back();
        </script>";
    }
}
if($action == "delete"){

    $email = trim($_POST['email']); // remove extra spaces
    $email = mysqli_real_escape_string($conn, $email);

    if($role == "admin"){
        $check = mysqli_query($conn, "SELECT id FROM admins WHERE email='$email'");
        $sql = "DELETE FROM admins WHERE email='$email'";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM students WHERE email='$email'");
        $sql = "DELETE FROM students WHERE email='$email'";
    }

    // If user not found
    if(mysqli_num_rows($check) == 0){
        echo "<script>
            alert('User not found!');
            window.history.back();
        </script>";
        exit();
    }

    mysqli_query($conn, $sql);

    // Check if actually deleted
    if(mysqli_affected_rows($conn) > 0){
        echo "<script>
            alert('User Deleted Successfully!');
            window.location.href='admin_dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Delete failed!');
            window.history.back();
        </script>";
    }
}


?>