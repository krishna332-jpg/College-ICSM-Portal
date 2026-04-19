<?php
session_start();
include("../config/db.php");

// Fresh Start Logic
if (isset($_GET['new']) || (isset($_GET['type']) && isset($_SESSION['temp_opp']) && $_GET['type'] !== $_SESSION['temp_opp']['type'])) {
    unset($_SESSION['temp_opp']);
}

$saved = $_SESSION['temp_opp'] ?? [];
$type_label = $_GET['type'] ?? $saved['type'] ?? 'internship';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['temp_opp'] = $_POST;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = "temp_" . time() . "." . $file_ext;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], "../images/" . $file_name)) {
            $_SESSION['temp_opp']['image_path'] = "images/" . $file_name;
        }
    }

    header("Location: apply_form_builder.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create <?php echo ucfirst($type_label); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
:root { --primary: #4f46e5; --dark: #0f172a; --glass: rgba(255, 255, 255, 0.98); }

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: url('/college_icsm_portal/images/loginpage.jpg') no-repeat center center fixed;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 850px;
    background: var(--glass);
    border-radius: 35px;
    padding: 45px;
    box-shadow: 0 50px 100px rgba(0,0,0,0.25);
    position: relative;
}

.top-right-nav {
    position: absolute;
    top: 30px;
    right: 40px;
}

h1 {
    font-size: 30px;
    font-weight: 800;
    color: var(--dark);
    margin-bottom: 35px;
    letter-spacing: -1px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

input, textarea {
    width: 100%;
    padding: 15px 20px;
    background: #f8fafc;
    border: 2px solid #f1f5f9;
    border-radius: 16px;
    font-size: 14px;
    outline: none;
    transition: 0.3s;
}

input:focus, textarea:focus {
    border-color: var(--primary);
    background: #fff;
}

.btn-submit {
    padding: 16px 40px;
    background: var(--dark);
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
    transition: 0.3s;
}

.btn-submit:hover {
    background: var(--primary);
    transform: translateY(-2px);
}
</style>
</head>

<body>

<div class="container">

    <div class="top-right-nav">
        <a href="../auth/logout.php" style="text-decoration:none; color:#ef4444; font-size:20px;">⏻</a>
    </div>

    <h1>Create <?php echo ucfirst($type_label); ?></h1>

    <form action="" method="POST" enctype="multipart/form-data">

        <input type="hidden" name="type" value="<?php echo $type_label; ?>">

        <div class="form-grid">

            <div style="grid-column: span 2;">
                <span class="label">Opportunity Title</span>
                <input type="text" name="title" value="<?php echo $saved['title'] ?? ''; ?>" required>
            </div>

            <div>
                <span class="label">Organization</span>
                <input type="text" name="organization" value="<?php echo $saved['organization'] ?? ''; ?>" required>
            </div>

            <div>
                <span class="label">Deadline</span>
                <input type="date" name="deadline"
                value="<?php echo !empty($saved['deadline']) 
                    ? date('Y-m-d', strtotime($saved['deadline'])) 
                    : ''; ?>"
                min="<?php echo date('Y-m-d'); ?>"
                required>
            </div>

            <div style="grid-column: span 2;">
                <span class="label">Description</span>
                <textarea name="description" rows="3" required><?php echo $saved['description'] ?? ''; ?></textarea>
            </div>

            <div style="grid-column: span 2;">
                <span class="label">Eligibility</span>
                <textarea name="eligibility" rows="2"><?php echo $saved['eligibility'] ?? ''; ?></textarea>
            </div>

            <div style="grid-column: span 2;">
                <span class="label">Banner Asset</span>
                <input type="file" name="image">
            </div>

        </div>

        <button type="submit" class="btn-submit">Continue to Builder →</button>

    </form>

</div>

</body>
</html>