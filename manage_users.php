<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$role = $_GET['role'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        body { font-family: Arial; background:#f1f5f9; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { background:white; padding:30px; border-radius:15px; width:350px; text-align:center; }
        input { width:100%; padding:10px; margin-top:10px; border-radius:8px; border:1px solid #ccc; }
        button { margin-top:15px; padding:12px; width:100%; background:#3b82f6; color:white; border:none; border-radius:10px; cursor:pointer; }
    </style>
</head>
<body>

<div class="box">

<?php if($action == "add"): ?>

    <h2>Add <?= ucfirst($role) ?></h2>

    <form method="POST" action="process_user.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="role" value="<?= $role ?>">

        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <?php if($role == "student"): ?>
            <input type="text" name="register_no" placeholder="Register No" required>
        <?php endif; ?>

        <input type="date" name="dob" required>

        <button type="submit">Add User</button>
    </form>

<?php elseif($action == "remove"): ?>

    <h2>Delete <?= ucfirst($role) ?></h2>

    <form method="POST" action="process_user.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="role" value="<?= $role ?>">

        <input type="email" name="email" placeholder="Enter Email to Delete" required>

        <button type="submit" style="background:red;">Delete User</button>
    </form>

<?php endif; ?>

</div>

</body>
</html>