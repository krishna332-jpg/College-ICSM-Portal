<?php
include("config/db.php");

// CREATE 100 STUDENTS
for ($i = 1; $i <= 100; $i++) {

    $num = str_pad($i, 2, "0", STR_PAD_LEFT);
    $register_no = "24bcaf" . $num;

    $name = "Student " . $i;
    $email = $register_no . "@kristujayanthi.com";

    $password = password_hash($register_no, PASSWORD_DEFAULT);

    $sql = "INSERT INTO students (name, email, password, register_no)
            VALUES ('$name', '$email', '$password', '$register_no')";

    mysqli_query($conn, $sql);
}


// CREATE 10 ADMINS
$admin_names = ["Arun","Binu","Binoy","Zara","Prakash","Rahul","Anu","Megha","Vishal","Nina"];

foreach ($admin_names as $name) {

    $email = $name . "Admin@kristujayanthi.com";
    $password = password_hash($name . "@123", PASSWORD_DEFAULT);

    $sql = "INSERT INTO admins (name, email, password)
            VALUES ('$name', '$email', '$password')";

    mysqli_query($conn, $sql);
}

echo "Students and Admins created successfully!";
?>