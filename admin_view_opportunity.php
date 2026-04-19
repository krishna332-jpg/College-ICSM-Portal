<?php
session_start();
require_once("../config/db.php");

// Auth Check - Admins Only
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../auth/login.php"); 
    exit(); 
}

$op_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Opportunity Data
$query = "SELECT * FROM opportunities WHERE id = $op_id LIMIT 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if(!$data) { exit("Opportunity not found."); }

// Image Handling
$db_filename = basename((string)($data['image'] ?? '')); 
$opp_image = "../images/" . $db_filename; 
if (empty($db_filename) || !file_exists($opp_image)) { 
    $opp_image = "../images/default_placeholder.jpg"; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin View | <?= htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --glass-border: rgba(255, 255, 255, 0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background: #070b14; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 40px 20px;
        }
        
        .bg-blur { 
            position: fixed; 
            inset: 0; 
            background: url('<?= $opp_image; ?>') center/cover no-repeat; 
            filter: blur(100px) brightness(0.15); 
            z-index: -1; 
        }

        .content-card { 
            width: 100%; 
            max-width: 900px; 
            background: white; 
            border-radius: 40px; 
            overflow: hidden; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        .hero-section { 
            width: 100%; 
            height: 350px; 
            position: relative;
        }

        .hero-img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .admin-label {
            position: absolute;
            top: 25px;
            right: 25px;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            color: #ef4444;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .details-body { 
            padding: 50px; 
        }

        h1 { font-size: 42px; font-weight: 800; color: var(--dark); letter-spacing: -1.5px; line-height: 1.1; }
        .org-name { color: var(--primary); font-weight: 700; font-size: 20px; margin-top: 12px; }

        .section-label { 
            display: block; 
            font-size: 12px; 
            font-weight: 800; 
            color: #94a3b8; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            margin: 40px 0 15px 0; 
            border-top: 1px solid #f1f5f9; 
            padding-top: 30px; 
        }

        .text-content { 
            color: #475569; 
            line-height: 1.8; 
            font-size: 17px; 
        }

        .deadline-badge { 
            display: inline-flex; 
            align-items: center; 
            background: #fff1f2; 
            padding: 12px 24px; 
            border-radius: 15px; 
            color: #e11d48; 
            font-weight: 800; 
            margin-top: 10px;
            border: 1px solid #fecdd3;
        }
        
        .deadline-badge i { margin-right: 10px; }
    </style>
</head>
<body>

<div class="bg-blur"></div>

<div class="content-card">
    <div class="hero-section">
        <img src="<?= $opp_image; ?>" class="hero-img" onerror="this.src='../images/default_placeholder.jpg'">
        <div class="admin-label"><i class="fa-solid fa-user-shield"></i> Admin View</div>
    </div>

    <div class="details-body">
        <h1><?= htmlspecialchars($data['title']); ?></h1>
        <div class="org-name"><?= htmlspecialchars($data['organization']); ?></div>

        <span class="section-label">DESCRIPTION</span>
        <div class="text-content"><?= nl2br(htmlspecialchars($data['description'])); ?></div>

        <?php if(!empty($data['eligibility'])): ?>
            <span class="section-label">Eligibility</span>
            <div class="text-content"><?= nl2br(htmlspecialchars($data['eligibility'])); ?></div>
        <?php endif; ?>

        <span class="section-label">DEADLINE</span>
        <div class="deadline-badge">
            <i class="fa-solid fa-clock"></i>
            <?= !empty($data['deadline']) ? date('l, d M Y | h:i A', strtotime($data['deadline'])) : 'Always Open'; ?>
        </div>
    </div>
</div>

</body>
</html>