<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

/* FETCH DATA FOR INTERNSHIPS */
$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM applications WHERE opportunity_id = o.id) as total_apps,
        (SELECT COUNT(*) FROM applications WHERE opportunity_id = o.id AND status = 'pending') as pending_apps
        FROM opportunities o 
        WHERE o.type='internship' 
        ORDER BY o.deadline ASC, o.id DESC";
$result = mysqli_query($conn, $sql);

$active_rows = [];
$expired_rows = [];
$today = date('Y-m-d');

while($row = mysqli_fetch_assoc($result)){
    if($row['deadline'] < $today){ 
        $expired_rows[] = $row; 
    } else { 
        $active_rows[] = $row; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internship Management | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --accent: #4f46e5; --text-dark: #0f172a; --text-light: #64748b; --glass: rgba(255, 255, 255, 0.95); --danger: #e11d48; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background: url('/college_icsm_portal/images/loginpage.jpg') no-repeat center center fixed; 
            background-size: cover; 
            padding: 40px; 
            min-height: 100vh; 
        }

        .container { 
            max-width: 1250px; 
            margin: 0 auto; 
            background: var(--glass); 
            backdrop-filter: blur(20px); 
            border-radius: 28px; 
            padding: 50px; 
            box-shadow: 0 40px 100px -20px rgba(0,0,0,0.3); 
            border: 1px solid rgba(255, 255, 255, 0.4); 
        }

        .header-area { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 50px; 
        }

        .page-info h1 { 
            font-size: 32px; 
            font-weight: 800; 
            color: var(--text-dark); 
            letter-spacing: -1px; 
        }

        .search-wrapper input { 
            width: 380px; 
            height: 52px; 
            padding: 0 20px; 
            border-radius: 16px; 
            border: 1px solid rgba(0,0,0,0.05); 
            background: #ffffff; 
            outline: none; 
            transition: 0.3s; 
            font-weight: 500;
        }

        .search-wrapper input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

        table { width: 100%; border-collapse: separate; border-spacing: 0 15px; }
        th { padding: 0 25px 10px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-light); text-transform: uppercase; letter-spacing: 1.5px; }

        .item-card { background: #ffffff; transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1); cursor: pointer; }
        .item-card:hover { transform: scale(1.01); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }

        td { padding: 20px; vertical-align: middle; }
        td:first-child { border-radius: 20px 0 0 20px; }
        td:last-child { border-radius: 0 20px 20px 0; }

        .thumbnail { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; border: 1px solid #f1f5f9; }
        .main-title { text-decoration: none; color: var(--text-dark); font-weight: 700; font-size: 16px; }

        .stat-pill { display: inline-flex; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; }
        .pill-blue { background: #eef2ff; color: #4338ca; }
        .pill-orange { background: #fff7ed; color: #c2410c; }
        
        .btn-view { background: var(--text-dark); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-block; }

        /* Expired Logic */
        .expired { background: #f8fafc; opacity: 0.85; }
        .date-expired { color: var(--danger) !important; font-weight: 800 !important; }
        .expired-badge { font-size: 10px; background: var(--danger); color: white; padding: 2px 8px; border-radius: 6px; margin-left: 5px; vertical-align: middle; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <div class="page-info"><h1>Internships</h1></div>
        <div class="search-wrapper">
            <input type="text" id="mainSearch" onkeyup="liveSearch()" placeholder="Filter internships...">
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Banner</th>
                <th>Internship Details</th>
                <th>Total Apps</th>
                <th>New</th>
                <th>Deadline</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $all_rows = array_merge($active_rows, $expired_rows);
            foreach($all_rows as $row): 
                $is_exp = ($row['deadline'] < $today);
            ?>
            <tr class="item-card <?= $is_exp ? 'expired' : '' ?>" onclick="window.location='admin_view_opportunity.php?id=<?= $row['id'] ?>'">
                <td>
                    <img src="../<?= !empty($row['image']) ? $row['image'] : 'images/default_placeholder.jpg'; ?>" class="thumbnail">
                </td>
                <td>
                    <span class="main-title"><?= htmlspecialchars($row['title']); ?></span><br>
                    <span style="font-size:13px; color:var(--text-light)"><?= htmlspecialchars($row['organization']); ?></span>
                </td>
                <td><div class="stat-pill pill-blue"><?= $row['total_apps']; ?> Apps</div></td>
                <td><div class="stat-pill pill-orange"><?= $row['pending_apps']; ?> Pending</div></td>
                <td>
                    <span class="<?= $is_exp ? 'date-expired' : '' ?>" style="font-weight:600; font-size:13px;">
                        <?= date('d M, Y', strtotime($row['deadline'])); ?>
                    </span>
                    <?php if($is_exp): ?>
                        <span class="expired-badge">Closed</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <a href="admin_view_opportunity.php?id=<?= $row['id']; ?>" class="btn-view" onclick="event.stopPropagation();">View Interface</a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(empty($all_rows)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 50px; color: var(--text-light);">No internships posted yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function liveSearch() {
    const filter = document.getElementById("mainSearch").value.toLowerCase();
    document.querySelectorAll("tbody tr").forEach(row => { 
        if(!row.innerText.includes("No internships posted")) {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none"; 
        }
    });
}
</script>

</body>
</html>