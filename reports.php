<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? "Admin User";

// Fetch opportunities that have at least one reviewed application in application_reports
// ADDED: o.is_archived = 0 to filter out archived items
$query = "
SELECT o.*,
       COUNT(ar.id) AS total_apps,
       MAX(ar.finalized) AS is_final
FROM opportunities o
JOIN application_reports ar ON ar.opportunity_id = o.id
WHERE ar.review_status IS NOT NULL
AND o.is_archived = 0
GROUP BY o.id
ORDER BY is_final ASC, o.deadline DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics & Reports | Admin</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root { --primary: #6366f1; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; }
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
body { background: var(--bg); color: var(--dark); }

.navbar { background: white; padding: 12px 60px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 15px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
.logo { font-weight: 900; font-size: 22px; text-decoration: none; color: var(--dark); }
.logo span { color: var(--primary); }
.nav-right { display: flex; align-items: center; gap: 15px; }
.user-pill { display: flex; align-items: center; gap: 12px; background: #f1f5f9; padding: 6px 16px 6px 8px; border-radius: 50px; }
.avatar { width: 35px; height: 35px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; }

.page-header { text-align: center; margin: 40px 0; }
.page-header h1 { font-size: 32px; font-weight: 900; }
.search-wrapper { background: white; padding: 12px 25px; border-radius: 15px; display: flex; align-items: center; gap: 15px; max-width: 500px; margin: 20px auto; border: 1px solid #e2e8f0; }
.search-wrapper input { border: none; outline: none; width: 100%; }

.container { max-width: 1250px; margin: auto; padding: 20px; }
.opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }

.card { background: white; border-radius: 24px; overflow: hidden; transition: 0.3s; position: relative; }
.card:hover { transform: translateY(-6px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

.card-banner { height: 180px; background-size: cover; background-position: center; position: relative; }
.card.finalized .card-banner { filter: grayscale(1) brightness(0.6); } /* greyed banner */
.card.finalized { opacity: 0.9; }

.finalized-overlay-text {
    position: absolute; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%);
    color: #ff0000; /* bright red text */
    font-weight: 900; 
    font-size: 14px; 
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.85); /* light background for contrast */
    padding: 5px 15px; 
    border-radius: 8px;
    letter-spacing: 1px; 
    white-space: nowrap; 
    pointer-events: none;
    z-index: 10;
}

.badge-count { position: absolute; top: 15px; right: 15px; background: white; padding: 5px 12px; border-radius: 10px; font-weight: 800; font-size: 12px; z-index: 5; }

.card-body { padding: 25px; }
.org-tag { font-size: 10px; font-weight: 800; color: var(--primary); margin-bottom: 10px; }
.card-title { font-size: 18px; font-weight: 800; margin-bottom: 15px; }
.card-footer { display: flex; justify-content: space-between; align-items: center; }
.btn-view { background: var(--dark); color: white; padding: 10px 18px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; }
.btn-view:hover { background: var(--primary); }

/* Added simple style for the trash icon */
.btn-archive { color: var(--danger); font-size: 16px; margin-left: 10px; transition: 0.2s; }
.btn-archive:hover { transform: scale(1.1); }
</style>
</head>

<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="admin_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    </div>
    <div class="nav-right">
        <div class="user-pill">
            <div class="avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div>
                <div class="u-name"><?= $admin_name ?></div>
                <div class="u-role">Administrator</div>
            </div>
        </div>
        <a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i></a>
    </div>
</nav>

<div class="page-header">
    <h1>Analytics & Reports</h1>
    <div class="search-wrapper">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchBox" placeholder="Search reports..." onkeyup="filterCards()">
    </div>
</div>

<div class="container">
    <div class="opp-grid" id="cardGrid">
        <?php while($row = mysqli_fetch_assoc($result)):
            $is_final = ($row['is_final'] == 1);
        ?>
        <div class="card <?= $is_final ? 'finalized' : '' ?>">
            <div class="card-banner" style="background-image:url('../<?= $row['image'] ?>')">
                <?php if($is_final): ?>
                    <div class="finalized-overlay-text">REPORT FINALIZED</div>
                <?php endif; ?>
                <div class="badge-count"><i class="fa-solid fa-users"></i> <?= $row['total_apps'] ?> Apps</div>
            </div>
            <div class="card-body">
                <div class="org-tag"><?= $row['organization'] ?></div>
                <div class="card-title"><?= $row['title'] ?></div>
                <div class="card-footer">
                    <div>
                        <div style="font-size:10px;color:#888;">Deadline</div>
                        <div><?= date('d M Y', strtotime($row['deadline'])) ?></div>
                    </div>
                    
                    <div style="display: flex; align-items: center;">
                        <a href="archive_opportunity.php?id=<?= $row['id'] ?>" 
                           class="btn-archive" 
                           onclick="return confirm('Archive this report? It will be hidden from this list but saved for student tracking.')"
                           title="Archive">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                        <a href="manage_reports.php?id=<?= $row['id'] ?>" class="btn-view" style="margin-left: 15px;">
                            <?= $is_final ? "View Report" : "Process" ?>
                        </a>
                    </div>
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
        let org = card.querySelector('.org-tag').innerText.toLowerCase();
        card.style.display = (title.includes(input) || org.includes(input)) ? "block" : "none";
    });
}
</script>

</body>
</html></body>
</html>