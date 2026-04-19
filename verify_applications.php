<?php
session_start();
include("../config/db.php");

// Auth Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin User";

/** * IMPORTANT: The ORDER BY logic here puts expired deadlines first.
 */
$query = "SELECT o.*, 
          (SELECT COUNT(id) FROM applications WHERE opportunity_id = o.id) as total_apps 
          FROM opportunities o
          WHERE o.status = 'active'
          ORDER BY (o.deadline < CURDATE()) DESC, o.deadline ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Applications | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); }

        /* --- NAVBAR --- */
        .navbar { 
            background: white; 
            padding: 12px 60px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); 
            position: sticky; top: 0; z-index: 100; 
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .logo { font-weight: 900; font-size: 22px; text-decoration: none; color: var(--dark); letter-spacing: -1px; }
        .logo span { color: var(--primary); }
        .home-icon { color: #64748b; font-size: 18px; transition: 0.3s; }
        .home-icon:hover { color: var(--primary); }

        /* Updated User Pill to match your image */
        .nav-right { display: flex; align-items: center; gap: 15px; }
        
        .user-pill { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            background: #f1f5f9; 
            padding: 6px 16px 6px 8px; 
            border-radius: 50px; 
            border: 1px solid #e2e8f0;
        }

        .avatar {
            width: 35px; height: 35px; 
            background: var(--primary); 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-weight: 800; 
            font-size: 14px;
        }

        .u-info { line-height: 1.2; }
        .u-name { display: block; font-size: 13px; font-weight: 700; color: var(--dark); }
        .u-role { font-size: 10px; color: #64748b; font-weight: 600; }

        .logout-btn { 
            color: #f87171; 
            font-size: 18px; 
            transition: 0.3s; 
            text-decoration: none;
            padding: 8px;
        }
        .logout-btn:hover { color: #ef4444; transform: scale(1.1); }

        /* --- Page Content --- */
        .page-header { text-align: center; margin: 40px 0; }
        .page-header h1 { font-size: 32px; font-weight: 900; letter-spacing: -1px; }

        .search-wrapper { 
            background: white; padding: 12px 25px; border-radius: 15px; 
            display: flex; align-items: center; gap: 15px; 
            width: 100%; max-width: 500px; margin: 25px auto; 
            border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
        }
        .search-wrapper input { border: none; outline: none; width: 100%; font-size: 15px; font-weight: 500; }

        .container { max-width: 1250px; margin: 0 auto 50px; padding: 0 20px; }
        .opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
        
        /* --- Cards --- */
        .card { background: white; border-radius: 24px; overflow: hidden; border: 1px solid #f1f5f9; transition: 0.3s; position: relative; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        
        .card-banner { height: 180px; background-size: cover; background-position: center; position: relative; }
        .badge-count { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.9); padding: 5px 12px; border-radius: 10px; font-weight: 800; font-size: 12px; backdrop-filter: blur(4px); }
        
        .overdue-label { position: absolute; top: 15px; left: 15px; background: var(--danger); color: white; padding: 5px 12px; border-radius: 10px; font-weight: 800; font-size: 11px; text-transform: uppercase; }

        .card-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .org-tag { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--primary); background: #f5f3ff; padding: 4px 10px; border-radius: 6px; width: fit-content; margin-bottom: 10px; }
        .card-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; color: var(--dark); line-height: 1.3; }

        .card-footer { margin-top: auto; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .deadline-box span { display: block; font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
        .date-text { font-size: 13px; font-weight: 800; }
        .date-text.expired { color: var(--danger); }
        .date-text.active { color: #10b981; }

        .btn-view { background: var(--dark); color: white; padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; transition: 0.2s; }
        .btn-view:hover { background: var(--primary); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="admin_dashboard.php" class="home-icon"><i class="fa-solid fa-house-chimney"></i></a>
        <a href="admin_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    </div>

    <div class="nav-right">
        <div class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
            <div class="u-info">
                <span class="u-name"><?= $admin_name ?></span>
                <span class="u-role">Administrator</span>
            </div>
        </div>
        <a href="../auth/logout.php" class="logout-btn" title="Logout">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>
</nav>

<div class="page-header">
    <h1>Verify Applications</h1>
    <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchBox" placeholder="Search by opportunity name..." onkeyup="filterCards()">
    </div>
</div>

<div class="container">
    <div class="opp-grid" id="cardGrid">
        <?php while($row = mysqli_fetch_assoc($result)): 
            $is_expired = strtotime($row['deadline']) < strtotime(date('Y-m-d'));
        ?>
            <div class="card">
                <?php if($is_expired): ?>
                    <div class="overdue-label"><i class="fa-solid fa-clock"></i> Action Required</div>
                <?php endif; ?>

                <div class="card-banner" style="background-image: url('../<?= $row['image'] ?>'), url('../images/default_opp.jpg');">
                    <div class="badge-count"><i class="fa-solid fa-users"></i> <?= $row['total_apps'] ?> Apps</div>
                </div>

                <div class="card-body">
                    <span class="org-tag"><?= htmlspecialchars($row['organization']) ?></span>
                    <h2 class="card-title"><?= htmlspecialchars($row['title']) ?></h2>

                    <div class="card-footer">
                        <div class="deadline-box">
                            <span>Deadline</span>
                            <div class="date-text <?= $is_expired ? 'expired' : 'active' ?>">
                                <?= date('d M Y', strtotime($row['deadline'])) ?>
                            </div>
                        </div>
                        <a href="view_applicants.php?id=<?= $row['id'] ?>" class="btn-view">View List</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function filterCards() {
    let input = document.getElementById('searchBox').value.toLowerCase();
    let cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        let title = card.querySelector('.card-title').innerText.toLowerCase();
        card.style.display = title.includes(input) ? "flex" : "none";
    });
}
</script>

</body>
</html>