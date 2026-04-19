<?php
session_start();
include("../config/db.php");

/* AUTH CHECK */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['name'] ?? "Admin"; 

// Get the type (internship, scholarship, certification) from the URL
$type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'internship';

// Fetch active records that are NOT already deleted/archived
$query = "SELECT * FROM opportunities WHERE type = '$type' AND status != 'deleted' AND is_archived = 0 ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remove <?php echo ucfirst($type); ?>s | Admin Portal</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; --danger-hover: #dc2626; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); }

        /* Navigation */
        .navbar { background: white; padding: 12px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 900; font-size: 22px; text-decoration: none; color: var(--dark); }
        .logo span { color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .user-pill { display: flex; align-items: center; gap: 12px; background: #f1f5f9; padding: 6px 16px 6px 8px; border-radius: 50px; }
        .avatar { width: 35px; height: 35px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; }

        /* Header & Search */
        .page-header { text-align: center; margin: 40px 0; }
        .page-header h1 { font-size: 32px; font-weight: 900; margin-bottom: 15px; }
        .search-wrapper { background: white; padding: 12px 25px; border-radius: 15px; display: flex; align-items: center; gap: 15px; max-width: 500px; margin: 20px auto; border: 1px solid #e2e8f0; transition: 0.3s; }
        .search-wrapper input { border: none; outline: none; width: 100%; font-size: 14px; }

        /* Grid */
        .container { max-width: 1250px; margin: auto; padding: 20px; }
        .opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
        .card { background: white; border-radius: 24px; overflow: hidden; transition: 0.3s; position: relative; border: 1px solid #eef2ff; }
        .card:hover { transform: translateY(-6px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); border-color: var(--danger); }
        .card-banner { height: 160px; background-size: cover; background-position: center; position: relative; }
        .type-badge { position: absolute; top: 15px; left: 15px; background: rgba(255,255,255,0.95); padding: 5px 12px; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase; color: var(--danger); }
        .card-body { padding: 25px; }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: var(--dark); height: 50px; overflow: hidden; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px; }

        .btn-delete { background: var(--danger); color: white; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-delete:hover { background: var(--danger-hover); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }

        /* CUSTOM MODAL STYLES */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
            display: none; justify-content: center; align-items: center; z-index: 2000;
        }
        .modal-box {
            background: white; padding: 35px; border-radius: 28px;
            width: 90%; max-width: 420px; text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalPop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .modal-icon {
            width: 70px; height: 70px; background: #fee2e2; color: #ef4444;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 20px auto;
        }
        .modal-box h3 { font-size: 22px; font-weight: 900; color: #1e293b; margin-bottom: 10px; }
        .modal-box p { color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.6; }
        .modal-btns { display: flex; gap: 12px; }
        .btn-m { flex: 1; padding: 14px; border-radius: 14px; font-weight: 700; cursor: pointer; border: none; font-size: 14px; transition: 0.2s; text-decoration: none; }
        .btn-m-cancel { background: #f1f5f9; color: #475569; }
        .btn-m-confirm { background: var(--danger); color: white; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="admin_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    <div class="user-pill">
        <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
        <div style="font-size:13px; font-weight:800;"><?= $admin_name ?></div>
    </div>
</nav>

<div class="page-header">
    <h1>Remove <?php echo ucfirst($type); ?>s</h1>
    <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
        <input type="text" id="searchBox" placeholder="Search title or organization..." onkeyup="filterCards()">
    </div>
</div>

<div class="container">
    <div class="opp-grid" id="cardGrid">
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="card">
                <div class="card-banner" style="background-image:url('../<?= $row['image'] ?>')">
                    <div class="type-badge"><?= $type ?></div>
                </div>
                <div class="card-body">
                    <div class="org-tag" style="font-size:11px; font-weight:800; color:#64748b;"><?= htmlspecialchars($row['organization']) ?></div>
                    <div class="card-title"><?= htmlspecialchars($row['title']) ?></div>
                    <div class="card-footer">
                        <div class="info-box">
                            <span style="font-size:10px; color:#94a3b8; font-weight:700;">POSTED</span><br>
                            <span style="font-size:13px; font-weight:700; color:#475569;"><?= date('d M Y', strtotime($row['created_at'])) ?></span>
                        </div>
                        <button onclick="openModal('<?= $row['id'] ?>', '<?= addslashes($row['title']) ?>')" class="btn-delete">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-trash-can"></i></div>
        <h3>Confirm Removal</h3>
        <p>Are you sure you want to remove <br><strong id="itemTitle" style="color:#0f172a;"></strong>?</p>
        <div class="modal-btns">
            <button class="btn-m btn-m-cancel" onclick="closeModal()">Keep It</button>
            <a href="#" id="confirmDelete" class="btn-m btn-m-confirm">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
function filterCards() {
    let input = document.getElementById('searchBox').value.toLowerCase();
    let cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        let title = card.querySelector('.card-title').innerText.toLowerCase();
        card.style.display = title.includes(input) ? "block" : "none";
    });
}

function openModal(id, title) {
    document.getElementById('itemTitle').innerText = title;
    document.getElementById('confirmDelete').href = "delete_item.php?id=" + id;
    document.getElementById('deleteModal').style.display = "flex";
}

function closeModal() {
    document.getElementById('deleteModal').style.display = "none";
}

window.onclick = function(e) { if(e.target == document.getElementById('deleteModal')) closeModal(); }
</script>
</body>
</html>