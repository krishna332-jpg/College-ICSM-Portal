<?php
session_start();
include("../config/db.php");

/* AUTH */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? "Admin"; 

/* DYNAMIC DATA FETCH - UPDATED TO HIDE DELETED */
$res_int = mysqli_query($conn, "SELECT COUNT(*) as total FROM opportunities WHERE type='internship' AND status != 'deleted'");
$internships = mysqli_fetch_assoc($res_int)['total'] ?? 0;

$res_sch = mysqli_query($conn, "SELECT COUNT(*) as total FROM opportunities WHERE type='scholarship' AND status != 'deleted'");
$scholarships = mysqli_fetch_assoc($res_sch)['total'] ?? 0;

$res_cert = mysqli_query($conn, "SELECT COUNT(*) as total FROM opportunities WHERE type='certification' AND status != 'deleted'");
$certifications = mysqli_fetch_assoc($res_cert)['total'] ?? 0;

/* CALENDAR LOGIC - FIXED DATE ALIGNMENT */
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$today = date('Y-m-d'); 

$prev_month = $month - 1; $prev_year = $year;
$next_month = $month + 1; $next_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
if ($next_month > 12) { $next_month = 1; $next_year++; }

$first_day_ts = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_ts);
$month_name = date('F Y', $first_day_ts);

$first_day_of_week = date('N', $first_day_ts); 

$events = []; 
// UPDATED TO HIDE DELETED FROM CALENDAR
$sql = "SELECT deadline, title, type FROM opportunities WHERE status = 'active'";
$res = $conn->query($sql);
if($res) {
    while($row = $res->fetch_assoc()) {
        $d = $row['deadline'];
        $type = strtolower((string)($row['type'] ?? ''));        
        $color = ($type == 'internship') ? 'orange' : (($type == 'certification') ? 'yellow' : 'blue');
        $events[$d]['colors'][] = $color;
        $events[$d]['titles'][] = $row['title'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ICSM Admin | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --blue: #1e3a8a; --orange: #9a3412; --yellow: #854d0e; 
            --blue-soft: #bfdbfe; --orange-soft: #fed7aa; --yellow-soft: #fef08a;
            --glass: rgba(255, 255, 255, 0.95); 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: url('../images/loginpage.jpg') no-repeat center center fixed; background-size: cover; min-height: 100vh; padding: 20px 40px; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; max-width: 1300px; margin: 0 auto 30px auto; }
        .logo { font-weight: 800; font-size: 24px; color: #0f172a; text-decoration:none; }
        .logo span { color: #3b82f6; }
        .profile-pill { display: flex; align-items: center; gap: 12px; background: var(--glass); padding: 6px 18px 6px 6px; border-radius: 50px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); }
        .avatar { width: 38px; height: 38px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; }

        .bento-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; max-width: 1300px; margin: 0 auto; }
        .bento-card { background: var(--glass); backdrop-filter: blur(15px); border-radius: 28px; padding: 22px; border: 1px solid rgba(255,255,255,0.4); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .bento-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

        .welcome-card { grid-column: span 3; display: flex; align-items: center; padding-left: 40px; height: 140px; }
        .welcome-card h1 { font-size: 40px; font-weight: 800; }
        .manage-card { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; cursor: pointer; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }

        .left-column { grid-column: 1; grid-row: 2 / span 2; display: flex; flex-direction: column; gap: 20px; }
        .cal-card { flex: 1; padding: 18px; }
        .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .cal-nav a { color: #3b82f6; text-decoration: none; font-weight: 800; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        
        .date-box { font-size: 11px; padding: 10px 0; border-radius: 12px; font-weight: 700; text-align: center; background: white; border: 1px solid #f1f5f9; position: relative; cursor: pointer; min-height: 38px; }
        .blank-box { background: transparent; border: none; cursor: default; }
        .today-dot { position: absolute; top: 4px; left: 4px; width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 5px rgba(34, 197, 94, 0.8); }

        .user-access-card { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; cursor: pointer; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; height: 150px; }

        .op-card { text-decoration: none; color: inherit; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }
        .op-img { width: 100%; height: 95px; border-radius: 18px; margin-bottom: 10px; background-size: cover; background-position: center; }
        .big-num { font-size: 32px; font-weight: 800; color: #1e293b; }
        .action-icon { font-size: 35px; margin-bottom: 10px; }

        .bg-orange { background-color: var(--orange-soft) !important; color: var(--orange) !important; border: 2px solid var(--orange) !important; }
        .bg-yellow { background-color: var(--yellow-soft) !important; color: var(--yellow) !important; border: 2px solid var(--yellow) !important; }
        .bg-blue   { background-color: var(--blue-soft) !important; color: var(--blue) !important; border: 2px solid var(--blue) !important; }
        .split-color { background: linear-gradient(135deg, var(--orange-soft) 50%, var(--blue-soft) 50%) !important; border: 2px solid #334155 !important; }

        .popup { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index: 1000; backdrop-filter: blur(8px); }
        .popup-box { background:white; padding:30px; border-radius:28px; width:350px; text-align:center; }
        .options button { width:100%; margin-top:12px; padding:14px; border:none; border-radius:14px; background:#3b82f6; color:white; cursor:pointer; font-weight: 700; }
        
        .unread-badge { position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; font-size: 10px; padding: 4px 10px; border-radius: 20px; font-weight: 800; border: 2px solid white; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="admin_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    <div class="profile-pill">
        <div class="avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div>
            <span style="display:block; font-size:13px; font-weight:800;"><?php echo $admin_name; ?></span>
            <span style="font-size:11px; color:#64748b;">Administrator</span>
        </div>
        <a href="../auth/logout.php" style="color:#ef4444; margin-left:10px;"><i class="fa-solid fa-power-off"></i></a>
    </div>
</div>

<div class="bento-grid">
    <div class="bento-card welcome-card">
        <h1>Welcome, <?php echo $admin_name; ?>!</h1>
    </div>

    <div class="bento-card manage-card" onclick="openManage()">
        <i class="fa-solid fa-gears" style="font-size: 30px; margin-bottom: 10px;"></i>
        <h3>Manage Portal</h3>
    </div>

    <div class="left-column">
        <div class="bento-card cal-card">
            <div class="cal-nav">
                <a href="?m=<?= $prev_month ?>&y=<?= $prev_year ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <div style="font-weight:800; font-size:14px;"><?= $month_name ?></div>
                <a href="?m=<?= $next_month ?>&y=<?= $next_year ?>"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
            <div class="cal-grid">
                <?php foreach(['M','T','W','T','F','S','S'] as $day_label) echo "<div style='font-size:10px; font-weight:800; color:#94a3b8; text-align:center;'>$day_label</div>"; ?>
                
                <?php 
                    for($x = 1; $x < $first_day_of_week; $x++) {
                        echo "<div class='date-box blank-box'></div>";
                    }

                    for($i=1; $i<=$days_in_month; $i++) {
                        $cDate = sprintf("%04d-%02d-%02d", $year, $month, $i);
                        $class = ""; $title_attr = "";
                        
                        if(isset($events[$cDate])) {
                            $uCols = array_unique($events[$cDate]['colors']);
                            $class = (count($uCols) > 1) ? "split-color" : "bg-" . $uCols[0];
                            $title_attr = "Deadlines: " . implode(", ", $events[$cDate]['titles']);
                        }

                        $isToday = ($cDate == $today) ? "<div class='today-dot'></div>" : "";
                        echo "<div class='date-box $class' title='$title_attr'>$isToday $i</div>";
                    }
                ?>
            </div>
        </div>

        <div class="bento-card user-access-card" onclick="openUserManage()">
            <i class="fa-solid fa-user-gear" style="font-size: 30px; margin-bottom: 8px;"></i>
            <h4 style="font-weight:800;">User Access</h4>
        </div>
    </div>

    <a href="internships.php" class="bento-card op-card">
        <div class="op-img" style="background-image: url('../images/inin.jpg');"></div>
        <p style="font-size:12px; font-weight:700; color:#64748b;">Internships</p>
        <div class="big-num"><?= $internships ?></div>
    </a>

    <a href="certifications.php" class="bento-card op-card">
        <div class="op-img" style="background-image: url('../images/certi.jpg');"></div>
        <p style="font-size:12px; font-weight:700; color:#64748b;">Certifications</p>
        <div class="big-num"><?= $certifications ?></div>
    </a>

    <a href="scholarships.php" class="bento-card op-card">
        <div class="op-img" style="background-image: url('../images/scholar.jpg');"></div>
        <p style="font-size:12px; font-weight:700; color:#64748b;">Scholarships</p>
        <div class="big-num"><?= $scholarships ?></div>
    </a>

    <a href="admin_mailbox.php" class="bento-card op-card">
        <?php 
        $res_unread = mysqli_query($conn, "SELECT COUNT(*) as unread FROM messages WHERE is_read = 0 AND sender_role = 'student'");
        $unread_count = mysqli_fetch_assoc($res_unread)['unread'] ?? 0;
        if($unread_count > 0): ?>
            <span class="unread-badge"><?= $unread_count ?> NEW</span>
        <?php endif; ?>
        <i class="fa-solid fa-comment-dots action-icon" style="color:#4f46e5;"></i>
        <h4 style="font-weight:800;">Messages</h4>
    </a>

    <a href="verify_applications.php" class="bento-card op-card">
        <i class="fa-solid fa-user-check action-icon" style="color:#059669;"></i>
        <h4 style="font-weight:800;">Verify</h4>
    </a>

    <a href="reports.php" class="bento-card op-card">
        <i class="fa-solid fa-chart-pie action-icon" style="color:#7c3aed;"></i>
        <h4 style="font-weight:800;">Report</h4>
    </a>
</div>

<div id="managePopup" class="popup"><div class="popup-box"><h2 id="popupTitle">Manage Portal</h2><div id="mainOptions" class="options"><button onclick="showStep('add')">Add New</button><button onclick="showStep('edit')">Edit Existing</button><button onclick="showStep('delete')">Remove</button><button onclick="closeManage()" style="background:#64748b;">Close</button></div><div id="icsOptions" class="options" style="display:none;"><button onclick="goToPage('internship')">Internship</button><button onclick="goToPage('scholarship')">Scholarship</button><button onclick="goToPage('certification')">Certification</button><button onclick="goBack()" style="background:#64748b;">← Back</button></div></div></div>
<div id="userPopup" class="popup"><div class="popup-box"><h2 id="userPopupTitle">User Access</h2><div id="userMainOptions" class="options"><button onclick="showUserStep('add')">Add User</button><button onclick="showUserStep('remove')">Remove User</button><button onclick="closeUserManage()" style="background:#64748b;">Close</button></div><div id="userTypeOptions" class="options" style="display:none;"><button onclick="goToUserAction('admin')">Administrator</button><button onclick="goToUserAction('student')">Student</button><button onclick="goBackUser()" style="background:#64748b;">← Back</button></div></div></div>

<script>
function openManage(){ document.getElementById("managePopup").style.display="flex"; }
function closeManage(){ document.getElementById("managePopup").style.display="none"; }
function showStep(action){ document.getElementById("mainOptions").style.display="none"; document.getElementById("icsOptions").style.display="block"; window.currentAction = action; }
function goBack(){ document.getElementById("mainOptions").style.display="block"; document.getElementById("icsOptions").style.display="none"; }

// UPDATED REDIRECT LOGIC FOR REMOVE ACTION
function goToPage(type){ 
    let targetPage = "";
    if(window.currentAction === "add") targetPage = "add_form.php";
    else if(window.currentAction === "edit") targetPage = "edit_select.php";
    else targetPage = "delete_select.php"; // This will list items to delete
    
    window.location.href = targetPage + "?type=" + type; 
}

function openUserManage(){ document.getElementById("userPopup").style.display="flex"; }
function closeUserManage(){ document.getElementById("userPopup").style.display="none"; }
function showUserStep(action){ document.getElementById("userMainOptions").style.display="none"; document.getElementById("userTypeOptions").style.display="block"; window.currentUserAction = action; }
function goBackUser(){ document.getElementById("userMainOptions").style.display="block"; document.getElementById("userTypeOptions").style.display="none"; }
function goToUserAction(role){ window.location.href = "manage_users.php?action=" + window.currentUserAction + "&role=" + role; }
</script>
</body>
</html>