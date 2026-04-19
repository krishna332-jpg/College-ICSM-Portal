<<?php
session_start();
include("../config/db.php");

/* AUTH CHECK */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['name'] ?? "Admin"; 

// Get the type (internship, scholarship, etc.) from the URL
$type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'internship';

// Fetch active records for this specific type that are NOT archived
$query = "SELECT * FROM opportunities WHERE type = '$type' AND is_archived = 0 ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?php echo ucfirst($type); ?>s | Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); }

        /* Navigation Styles */
        .navbar { background: white; padding: 12px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 900; font-size: 22px; text-decoration: none; color: var(--dark); }
        .logo span { color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .user-pill { display: flex; align-items: center; gap: 12px; background: #f1f5f9; padding: 6px 16px 6px 8px; border-radius: 50px; }
        .avatar { width: 35px; height: 35px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; }

        /* Header & Search */
        .page-header { text-align: center; margin: 40px 0; }
        .page-header h1 { font-size: 32px; font-weight: 900; margin-bottom: 10px; }
        .search-wrapper { background: white; padding: 12px 25px; border-radius: 15px; display: flex; align-items: center; gap: 15px; max-width: 500px; margin: 20px auto; border: 1px solid #e2e8f0; }
        .search-wrapper input { border: none; outline: none; width: 100%; font-size: 14px; }

        .container { max-width: 1250px; margin: auto; padding: 20px; }
        
        /* Card Grid */
        .opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }

        .card { background: white; border-radius: 24px; overflow: hidden; transition: 0.3s; position: relative; border: 1px solid #eef2ff; }
        .card:hover { transform: translateY(-6px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

        .card-banner { height: 160px; background-size: cover; background-position: center; position: relative; }
        .type-badge { position: absolute; top: 15px; left: 15px; background: rgba(255,255,255,0.9); padding: 5px 12px; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase; color: var(--primary); }

        .card-body { padding: 25px; }
        .org-tag { font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: var(--dark); height: 50px; overflow: hidden; }
        
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px;}
        .deadline-box { display: flex; flex-direction: column; }
        .deadline-label { font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
        .deadline-date { font-size: 13px; font-weight: 700; color: #475569; }

        .btn-edit-action { background: var(--dark); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; transition: 0.2s; }
        .btn-edit-action:hover { background: var(--primary); }

        .empty-state { text-align: center; padding: 100px 20px; }
        .empty-state i { font-size: 50px; color: #cbd5e1; margin-bottom: 20px; }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="admin_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    </div>
    <div class="nav-right">
        <div class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            <div>
                <div style="font-size:13px; font-weight:800;"><?= $admin_name ?></div>
                <div style="font-size:11px; color:#64748b;">Administrator</div>
            </div>
        </div>
        <a href="../auth/logout.php" style="color:var(--danger); margin-left:10px;"><i class="fa-solid fa-power-off"></i></a>
    </div>
</nav>

<div class="page-header">
    <h1>Edit <?php echo ucfirst($type); ?>s</h1>
    <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
        <input type="text" id="searchBox" placeholder="Search by title or organization..." onkeyup="filterCards()">
    </div>
</div>

<div class="container">
    <?php if(mysqli_num_rows($result) > 0): ?>
        <div class="opp-grid" id="cardGrid">
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <div class="card-banner" style="background-image:url('../<?= $row['image'] ?>')">
                        <div class="type-badge"><?= $type ?></div>
                    </div>
                    <div class="card-body">
                        <div class="org-tag"><?= htmlspecialchars($row['organization']) ?></div>
                        <div class="card-title"><?= htmlspecialchars($row['title']) ?></div>
                        
                        <div class="card-footer">
                            <div class="deadline-box">
                                <span class="deadline-label">Deadline</span>
                                <span class="deadline-date"><?= date('d M Y', strtotime($row['deadline'])) ?></span>
                            </div>
                            
                            <a href="edit_form.php?id=<?= $row['id'] ?>" class="btn-edit-action">
                                Edit Content
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <h3>No Records Found</h3>
            <p style="color:#64748b;">You haven't added any <?php echo $type; ?>s yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function filterCards() {
    let input = document.getElementById('searchBox').value.toLowerCase();
    let cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        let title = card.querySelector('.card-title').innerText.toLowerCase();
        let org = card.querySelector('.org-tag').innerText.toLowerCase();
        if (title.includes(input) || org.includes(input)) {
            card.style.display = "block";
        } else {
            card.style.display = "none";
        }
    });
}
</script>

</body>
</html>