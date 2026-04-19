 <?php

session_start();

include("../config/db.php");



// 1. SESSION SECURITY

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {

    header("Location: ../auth/login.php");

    exit();

}



$student_name = $_SESSION['student_name'] ?? "Student";

$reg_no = $_SESSION['register_no'] ?? "24BCAXXXX";



// 2. CALENDAR LOGIC - SYNCED WITH ADMIN

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



// FIXED: Calculate day of week for alignment (1 = Mon, 7 = Sun)

$first_day_of_week = date('N', $first_day_ts); 



// 3. DEADLINE FETCHING

$events = []; 

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

    <title>ICSM Portal | Dashboard</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>

        :root { 

            --blue: #1e3a8a; --orange: #9a3412; --yellow: #854d0e; 

            --blue-soft: #bfdbfe; --orange-soft: #fed7aa; --yellow-soft: #fef08a;

            --glass: rgba(255, 255, 255, 0.9); 

        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        

        body { 

            background: url('../images/loginpage.jpg') no-repeat center center fixed; 

            background-size: cover; 

            min-height: 100vh; 

            padding: 20px 40px; 

        }

        

        .top-bar { 

            display: flex; 

            justify-content: space-between; 

            align-items: center; 

            margin: 0 auto 30px auto; 

            max-width: 1300px; 

            position: relative;

            z-index: 100;

        }



        .logo { font-weight: 800; font-size: 24px; color: #0f172a; text-decoration:none; }

        .logo span { color: #3b82f6; }

        

        .profile-pill { 

            display: flex; 

            align-items: center; 

            gap: 12px; 

            background: var(--glass); 

            padding: 6px 18px 6px 6px; 

            border-radius: 50px; 

            backdrop-filter: blur(10px); 

            border: 1px solid rgba(255,255,255,0.5); 

        }



        .avatar { width: 38px; height: 38px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; }

        

        .bento-grid { 

            display: grid; 

            grid-template-columns: repeat(4, 1fr); 

            gap: 20px; 

            max-width: 1300px; 

            margin: 0 auto; 

            position: relative;

            z-index: 10;

        }

        

        .bento-card { 

            background: var(--glass); 

            backdrop-filter: blur(15px); 

            border-radius: 28px; 

            padding: 22px; 

            border: 1px solid rgba(255,255,255,0.4); 

            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); 

            text-decoration: none;

            color: inherit;

            display: block;

        }



        .bento-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }



        .welcome-card { grid-column: span 3; display: flex; align-items: center; padding-left: 40px; height: 140px; }

        .welcome-card h1 { font-size: 44px; font-weight: 800; }



        /* CALENDAR CSS */

        .cal-card { grid-row: span 2; display: flex; flex-direction: column; min-height: 440px; }

        .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }

        .cal-nav a { color: #3b82f6; text-decoration: none; font-weight: 800; }

        

        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }

        .date-box { font-size: 11px; padding: 12px 0; border-radius: 12px; font-weight: 700; text-align: center; position: relative; background: white; border: 1px solid #f1f5f9; cursor: pointer; min-height: 40px; }

        .blank-box { background: transparent; border: none; cursor: default; }

        .today-dot { position: absolute; top: 4px; left: 4px; width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 5px rgba(34, 197, 94, 0.8); }



        /* SYNCED COLORS */

        .bg-orange { background-color: var(--orange-soft) !important; color: var(--orange) !important; border: 2px solid var(--orange) !important; }

        .bg-yellow { background-color: var(--yellow-soft) !important; color: var(--yellow) !important; border: 2px solid var(--yellow) !important; }

        .bg-blue   { background-color: var(--blue-soft) !important; color: var(--blue) !important; border: 2px solid var(--blue) !important; }

        .split-color { background: linear-gradient(135deg, var(--orange-soft) 50%, var(--blue-soft) 50%) !important; border: 2px solid #334155 !important; }



        .op-card { height: 260px; }

        .op-img { width: 100%; height: 130px; border-radius: 18px; margin-bottom: 15px; background-size: cover; background-position: center; }

        

        .action-row { grid-column: span 4; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        

        .action-btn { 

            display: flex !important; 

            align-items: center; 

            gap: 20px; 

            padding: 25px; 

            cursor: pointer !important;

            position: relative;

            z-index: 50;

        }



        .icon-sq { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 20px; }



        #toast {

            visibility: hidden; min-width: 280px; background-color: #1e293b; color: #fff; 

            text-align: center; border-radius: 16px; padding: 18px; position: fixed; 

            z-index: 9999; left: 50%; bottom: 30px; transform: translateX(-50%); 

            font-weight: 600; opacity: 0; transition: 0.3s;

        }

        #toast.show { visibility: visible; opacity: 1; bottom: 40px; }

    </style>

</head>

<body>



<div id="toast">Status Updated!</div>



<div class="top-bar">

    <a href="student_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>

    <div class="profile-pill">

        <div class="avatar"><?php echo strtoupper(substr($student_name, 0, 1)); ?></div>

        <div style="line-height: 1.1;">

            <span style="display:block; font-size:13px; font-weight:800;"><?php echo $student_name; ?></span>

            <span style="font-size:11px; color:#64748b;"><?php echo $reg_no; ?></span>

        </div>

        <a href="../auth/logout.php" class="logout-btn" style="margin-left: 10px; color: #ef4444;"><i class="fa-solid fa-power-off"></i></a>

    </div>

</div>



<div class="bento-grid">

    <div class="bento-card welcome-card">

        <h1>Welcome, <?php echo explode(' ', $student_name)[0]; ?>!</h1>

    </div>



    <div class="bento-card cal-card">

        <div class="cal-nav">

            <a href="?m=<?= $prev_month ?>&y=<?= $prev_year ?>"><i class="fa-solid fa-chevron-left"></i></a>

            <div style="font-weight:800; font-size:14px;"><?= $month_name ?></div>

            <a href="?m=<?= $next_month ?>&y=<?= $next_year ?>"><i class="fa-solid fa-chevron-right"></i></a>

        </div>

        <div class="cal-grid">

            <?php foreach(['M','T','W','T','F','S','S'] as $day_label) echo "<div style='font-size:10px; font-weight:800; color:#94a3b8; text-align:center;'>$day_label</div>"; ?>

            

            <?php 

                // Fill leading blanks for alignment

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



    <a href="student_internship.php" class="bento-card op-card">

        <div class="op-img" style="background-image: url('../images/inin.jpg');"></div>

        <h3 style="font-size:16px; font-weight:800;">Internships</h3>

    </a>

    <a href="student_certification.php" class="bento-card op-card">

        <div class="op-img" style="background-image: url('../images/certi.jpg');"></div>

        <h3 style="font-size:16px; font-weight:800;">Certifications</h3>

    </a>

    <a href="student_scholarship.php" class="bento-card op-card">

        <div class="op-img" style="background-image: url('../images/scholar.jpg');"></div>

        <h3 style="font-size:16px; font-weight:800;">Scholarships</h3>

    </a>



    <div class="action-row">

        <a href="student_mailbox.php" class="bento-card action-btn">

            <div class="icon-sq" style="background:#eff6ff; color:#3b82f6;">

                <i class="fa-solid fa-envelope"></i>

            </div>

            <div>

                <h4 style="font-weight:800;">Mailbox</h4>

                <p style="font-size:11px; color:#10b981;">Check Messages</p>

            </div>

        </a>



        <a href="track_applications.php" class="bento-card action-btn">

            <div class="icon-sq" style="background:#fff7ed; color:#fb923c;">

                <i class="fa-solid fa-location-crosshairs"></i>

            </div>

            <div>

                <h4 style="font-weight:800;">Tracker</h4>

                <p style="font-size:11px; color:#64748b;">View Status</p>

            </div>

        </a>

    </div>

</div>



<script>

    window.onload = function() {

        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('status')) {

            const toast = document.getElementById("toast");

            toast.innerHTML = urlParams.get('status') === 'success' ? "Action Successful!" : "Error occurred";

            toast.classList.add("show");

            setTimeout(() => { toast.classList.remove("show"); }, 3000);

            

            const newUrl = window.location.pathname;

            window.history.replaceState({}, document.title, newUrl);

        }

    }

</script>



</body>

</html>