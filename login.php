<?php
session_start();
include("../config/db.php");

// --- 1. LOCKOUT LOGIC ---
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $remaining = $_SESSION['lockout_time'] - time();
    $show_lockout = true;
} else {
    $show_lockout = false;
    if (isset($_SESSION['lockout_time']) && time() >= $_SESSION['lockout_time']) {
        unset($_SESSION['lockout_time']);
        $_SESSION['login_attempts'] = 0;
    }
}

// --- 2. REDIRECT LOGGED IN USERS ---
if(isset($_SESSION['user_id']) && !$show_lockout) {
    $loc = ($_SESSION['role'] == "admin") ? "../admin/admin_dashboard.php" : "../student/student_dashboard.php";
    header("Location: $loc"); exit();
}

$error = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// --- 3. LOGIN HANDLING (UPDATED TO FIX DASHBOARD INFO) ---
if(isset($_POST['login']) && !$show_lockout){
    $login_id = mysqli_real_escape_string($conn, trim($_POST['login_id']));
    $password = trim($_POST['password']);

    $s_query = mysqli_query($conn, "SELECT * FROM students WHERE email='$login_id'");
    $a_query = mysqli_query($conn, "SELECT * FROM admins WHERE email='$login_id'");

    if(mysqli_num_rows($s_query) > 0) { 
        $user = mysqli_fetch_assoc($s_query); 
        $role = "student"; 
    } elseif(mysqli_num_rows($a_query) > 0) { 
        $user = mysqli_fetch_assoc($a_query); 
        $role = "admin"; 
    } else { 
        $user = null; 
    }

    if($user && password_verify($password, $user['password'])){
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $role;
        $_SESSION['login_attempts'] = 0;

        // Populate session with user details for the dashboard
        if($role == "student") {
            $_SESSION['student_name'] = $user['name']; 
            $_SESSION['register_no'] = $user['register_no'];
            header("Location: ../student/student_dashboard.php");
        } else {
            $_SESSION['admin_name'] = $user['name'];
            header("Location: ../admin/admin_dashboard.php");
        }
        exit();
    } else {
        $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
        if($_SESSION['login_attempts'] >= 3) {
            $_SESSION['lockout_time'] = time() + 120;
            header("Location: login.php"); exit();
        }
        $attempts_left = 3 - $_SESSION['login_attempts'];
        $error = "Invalid Credentials. $attempts_left attempts left.";
    }
}

// --- 4. FORGOT PASSWORD / RESET LOGIC ---
if(isset($_POST['verify_reset'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dob = date('Y-m-d', strtotime($_POST['dob'])); 
    
    $check_s = mysqli_query($conn, "SELECT * FROM students WHERE email='$email' AND dob='$dob'");
    $check_a = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email' AND dob='$dob'");

    if(mysqli_num_rows($check_s) > 0) { 
        $_SESSION['reset_user'] = ['id' => mysqli_fetch_assoc($check_s)['id'], 'table' => 'students']; 
    } elseif(mysqli_num_rows($check_a) > 0) { 
        $_SESSION['reset_user'] = ['id' => mysqli_fetch_assoc($check_a)['id'], 'table' => 'admins']; 
    } else {
        $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
        if($_SESSION['login_attempts'] >= 3) { $_SESSION['lockout_time'] = time() + 120; header("Location: login.php"); exit(); }
        $error = "Incorrect Email or DOB. Check format (YYYY-MM-DD).";
    }
    if(isset($_SESSION['reset_user'])) { header("Location: login.php?action=new_password"); exit(); }
}

if(isset($_POST['update_password'])){
    $p1 = $_POST['p1']; $p2 = $_POST['p2'];
    if($p1 !== $p2) { $error = "Passwords do not match."; }
    else {
        $hashed = password_hash($p1, PASSWORD_BCRYPT);
        $id = $_SESSION['reset_user']['id'];
        $table = $_SESSION['reset_user']['table'];
        mysqli_query($conn, "UPDATE $table SET password='$hashed' WHERE id=$id");
        unset($_SESSION['reset_user']);
        $_SESSION['login_attempts'] = 0;
        header("Location: login.php?reset=success"); exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ICSM Portal | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            background: url('../images/loginpage.jpg') no-repeat center center fixed;
            background-size: cover; height: 100vh;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        body::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 1; }
        .glass-card {
            position: relative; z-index: 10; width: 400px; padding: 50px 40px;
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 30px;
            text-align: center; color: white;
        }
        h1 { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .sub { font-size: 11px; opacity: 0.8; letter-spacing: 2px; margin-bottom: 30px; text-transform: uppercase; }
        input {
            width: 100%; padding: 15px; margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px; color: white; outline: none;
        }
        input::placeholder { color: rgba(255,255,255,0.6); }
        .btn-login {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: white; color: #0f172a; font-weight: 800; cursor: pointer; transition: 0.3s;
        }
        .btn-login:hover { background: #f1f5f9; transform: scale(1.02); }
        .error { color: #ffb3b3; font-size: 13px; margin-bottom: 15px; background: rgba(255,0,0,0.2); padding: 10px; border-radius: 8px; }
        .timer { font-size: 40px; font-weight: 800; color: #ffb3b3; margin: 20px 0; }
        .link { display: block; margin-top: 20px; color: white; text-decoration: none; font-size: 12px; opacity: 0.7; }
        .link:hover { opacity: 1; }
    </style>
</head>
<body>
<div class="glass-card">
    <h1>Kristu Jayanti</h1>
    <p class="sub">ICSM PORTAL</p>

    <?php if($show_lockout): ?>
        <p>Too many attempts.</p>
        <div class="timer" id="timer">00:00</div>
        <p class="sub">Please try again after the timer ends.</p>
        <script>
            let timeLeft = <?= $remaining ?>;
            const timerDisplay = document.getElementById('timer');
            const countdown = setInterval(() => {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                timerDisplay.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}s`;
                if (timeLeft <= 0) { clearInterval(countdown); window.location.reload(); }
                timeLeft--;
            }, 1000);
        </script>

    <?php elseif($action == 'forgot'): ?>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter Registered Email" required>
            <input type="text" name="dob" placeholder="DOB (YYYY-MM-DD)" onfocus="(this.type='date')" required>
            <button name="verify_reset" class="btn-login">Verify Identity</button>
            <a href="login.php" class="link">Back to Login</a>
        </form>

    <?php elseif($action == 'new_password'): ?>
        <form method="POST">
            <input type="password" name="p1" placeholder="New Password" required>
            <input type="password" name="p2" placeholder="Confirm Password" required>
            <button name="update_password" class="btn-login">Update Password</button>
        </form>

    <?php else: ?>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <?php if(isset($_GET['reset'])) echo "<p style='color:#b3ffcc; margin-bottom:10px;'>Password updated!</p>"; ?>
        <form method="POST">
            <input type="email" name="login_id" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button name="login" class="btn-login">Secure Login</button>
            <a href="login.php?action=forgot" class="link">Forgot Password?</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>