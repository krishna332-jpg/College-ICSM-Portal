<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

// 1. Get the ID of the item we want to edit
if(!isset($_GET['id'])) {
    die("Error: No ID provided.");
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// 2. Fetch existing data
$query = "SELECT * FROM opportunities WHERE id = '$id' LIMIT 1";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

if(!$data) {
    die("Error: Record not found.");
}

// 3. Handle Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $org = mysqli_real_escape_string($conn, $_POST['organization']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $eligibility = mysqli_real_escape_string($conn, $_POST['eligibility']);
    $deadline = $_POST['deadline'];
    
    // Check if a new image was uploaded
    if(!empty($_FILES["image"]["name"])) {
        $target_dir = "../images/";
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        $db_path = "images/" . $file_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        
        $sql = "UPDATE opportunities SET title='$title', organization='$org', description='$desc', 
                eligibility='$eligibility', deadline='$deadline', image='$db_path' WHERE id='$id'";
    } else {
        // Keep old image if none uploaded
        $sql = "UPDATE opportunities SET title='$title', organization='$org', description='$desc', 
                eligibility='$eligibility', deadline='$deadline' WHERE id='$id'";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: " . $data['type'] . "s.php?status=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit <?php echo ucfirst($data['type']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background: #f1f5f9; padding: 40px; display: flex; justify-content: center; }
        .form-card { background: white; padding: 40px; border-radius: 24px; width: 100%; max-width: 650px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        .badge { background: #eef2ff; color: #4f46e5; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        h1 { margin: 10px 0 30px; font-size: 24px; color: #1e293b; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        input, textarea { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 12px; outline: none; }
        .current-img { width: 100px; height: 60px; border-radius: 8px; object-fit: cover; margin-bottom: 10px; border: 2px solid #e2e8f0; }
        button { width: 100%; padding: 16px; background: #4f46e5; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="form-card">
        <span class="badge">Editing <?php echo $data['type']; ?></span>
        <h1>Modify Details</h1>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($data['title']); ?>" required>
            
            <label>Organization</label>
            <input type="text" name="organization" value="<?php echo htmlspecialchars($data['organization']); ?>" required>

            <label>Eligibility</label>
            <textarea name="eligibility" rows="2"><?php echo htmlspecialchars($data['eligibility']); ?></textarea>

            <label>Description</label>
            <textarea name="description" rows="5"><?php echo htmlspecialchars($data['description']); ?></textarea>

            <label>Deadline</label>
            <input type="date" name="deadline" value="<?php echo $data['deadline']; ?>" required>

            <label>Current Banner</label><br>
            <img src="../<?php echo $data['image']; ?>" class="current-img"><br>
            <label>Upload New Banner (Leave blank to keep current)</label>
            <input type="file" name="image">

            <button type="submit">Update Record</button>
        </form>
    </div>
</body>
</html>