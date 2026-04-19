<?php
session_start();
include("../config/db.php");

// 1. Security Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Variables
$student_name = $_SESSION['student_name'] ?? "Student";
$reg_no = $_SESSION['register_no'] ?? "24BCAXXXX";
$student_id = $_SESSION['user_id'];

// 3. Database Fetch - Sorted to put Expired (is_expired = 1) at the end
$current_date = date('Y-m-d');
$query = "SELECT *, 
          CASE WHEN deadline < '$current_date' THEN 1 ELSE 0 END AS is_expired 
          FROM opportunities 
          WHERE type = 'Internship' AND status = 'active' 
          ORDER BY is_expired ASC, deadline ASC, id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corporate Internships | ICSM Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --bg: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); min-height: 100vh; }

        /* --- NAVBAR --- */
        .navbar {
            background: white; padding: 12px 60px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        .nav-left { display: flex; align-items: center; gap: 25px; }
        .logo { font-weight: 900; font-size: 22px; color: var(--dark); text-decoration: none; letter-spacing: -1px; }
        .logo span { color: var(--primary); }
        .home-link { color: #64748b; font-size: 20px; transition: 0.3s; background: none; border: none; cursor: pointer; }
        .home-link:hover { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 15px; }
        .user-pill { 
            display: flex; align-items: center; gap: 12px; 
            background: #f1f5f9; padding: 5px 15px 5px 6px; 
            border-radius: 50px; border: 1px solid #e2e8f0;
        }
        .avatar {
            width: 35px; height: 35px; background: var(--primary); color: white; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px;
        }
        .u-info { line-height: 1.2; }
        .u-name { display: block; font-size: 13px; font-weight: 700; }
        .u-reg { font-size: 10px; color: #64748b; font-weight: 600; }
        .logout-btn { color: #f87171; font-size: 18px; margin-left: 10px; transition: 0.3s; background: none; border: none; cursor: pointer; }
        .logout-btn:hover { color: #ef4444; transform: scale(1.1); }

        /* --- HERO --- */
        .hero-container {
            max-width: 1250px; margin: 40px auto;
            background: white; border-radius: 35px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 50px; position: relative; overflow: visible;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .hero-text { flex: 1; z-index: 2; }
        .hero-text h1 { font-size: 50px; font-weight: 900; letter-spacing: -2px; line-height: 1; margin-bottom: 20px; }
        .hero-text p { color: #64748b; font-size: 18px; margin-bottom: 30px; max-width: 450px; }
        .search-wrapper {
            background: #f1f5f9; padding: 12px 20px; border-radius: 15px;
            display: flex; align-items: center; gap: 12px;
            width: 100%; max-width: 400px; border: 1px solid #e2e8f0;
        }
        .search-wrapper input { background: transparent; border: none; outline: none; width: 100%; font-size: 15px; font-weight: 500; }
        .hero-img-box {
            width: 480px; height: 320px;
            background: url('../images/inin.jpg') no-repeat center center;
            background-size: cover; border-radius: 25px;
            box-shadow: -20px 20px 60px rgba(0,0,0,0.1);
            transform: perspective(1000px) rotateY(-10deg) rotateX(5deg);
        }

        /* --- APPLIED STATUS BADGE --- */
        .applied-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #ecfdf5; color: #059669;
            padding: 4px 10px; border-radius: 8px;
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            margin-bottom: 12px; border: 1px solid rgba(16, 185, 129, 0.2);
            width: fit-content;
        }
        .pulse-dot { width: 6px; height: 6px; background: #10b981; border-radius: 50%; position: relative; }
        .pulse-dot::after {
            content: ''; position: absolute; width: 100%; height: 100%;
            background: #10b981; border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(3); opacity: 0; } }

        /* --- EXPIRED EFFECT (Grey Tint) --- */
        .card.expired { filter: grayscale(1); opacity: 0.7; pointer-events: none; }
        .expired-label { color: #ef4444 !important; font-weight: 800; }

        /* --- GRID & CARDS --- */
        .container { max-width: 1250px; margin: 0 auto 100px; padding: 0 20px; }
        .opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        .card { background: white; border-radius: 25px; overflow: hidden; border: 1px solid rgba(0,0,0,0.03); display: flex; flex-direction: column; transition: 0.3s; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .card-banner { height: 180px; width: 100%; background-size: cover; background-position: center; }
        .card-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .org-tag { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--primary); background: #f5f3ff; padding: 5px 12px; border-radius: 8px; width: fit-content; margin-bottom: 12px; }
        .card-title { font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--dark); }
        .card-desc { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .card-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .deadline { font-size: 13px; font-weight: 700; color: #10b981; }
        .deadline span { display: block; color: #94a3b8; font-size: 10px; text-transform: uppercase; }
        .btn-view { background: var(--dark); color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; transition: 0.3s; }
        .btn-view:hover { background: var(--primary); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <button onclick="confirmRedirect('student_dashboard.php', 'Return to Home?')" class="home-link"><i class="fa-solid fa-house-chimney"></i></button>
        <a href="student_dashboard.php" class="logo">ICSM<span>PORTAL</span></a>
    </div>
    <div class="nav-right">
        <div class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr($student_name, 0, 1)); ?></div>
            <div class="u-info">
                <span class="u-name"><?php echo $student_name; ?></span>
                <span class="u-reg"><?php echo $reg_no; ?></span>
            </div>
        </div>
        <button onclick="confirmRedirect('../auth/logout.php', 'Logout?')" class="logout-btn"><i class="fa-solid fa-power-off"></i></button>
    </div>
</nav>

<section class="hero-container">
    <div class="hero-text">
        <h1>Corporate<br>Internships</h1>
        <p>Explore industry opportunities and gain real-world experience.</p>
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="internSearch" placeholder="Search by role or company..." onkeyup="filterInternships()">
        </div>
    </div>
    <div class="hero-img-box"></div>
</section>

<div class="container">
    <div class="opp-grid" id="internGrid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): 
                $op_id = $row['id'];
                $deadline_date = $row['deadline'];
                
                // Logic: It only expires IF today is strictly AFTER the deadline date.
                $is_expired = (strtotime($deadline_date) < strtotime($current_date));

                // Check application status
                $check_sql = "SELECT id FROM applications WHERE (student_id = '$reg_no' OR student_id = '$student_id') AND opportunity_id = '$op_id'";
                $check_app = mysqli_query($conn, $check_sql);
                $has_applied = mysqli_num_rows($check_app) > 0;
            ?>
                <div class="card <?php echo $is_expired ? 'expired' : ''; ?>">
                    <div class="card-banner" style="background-image: url('../<?php echo $row['image']; ?>'), url('../images/default.jpg');"></div>
                    <div class="card-body">
                        <span class="org-tag"><?php echo $row['organization']; ?></span>
                        <h2 class="card-title"><?php echo $row['title']; ?></h2>
                        
                        <?php if($has_applied): ?>
                            <div class="applied-badge"><span class="pulse-dot"></span> Applied</div>
                        <?php endif; ?>

                        <p class="card-desc">Advanced technical internship program.</p>
                        
                        <div class="card-footer">
                            <div class="deadline">
                                <span>Deadline</span>
                                <b class="<?php echo $is_expired ? 'expired-label' : ''; ?>">
                                    <?php echo date('d M Y', strtotime($deadline_date)); ?>
                                    <?php echo $is_expired ? ' (Closed)' : ''; ?>
                                </b>
                            </div>
                            <a href="view_opportunity.php?id=<?php echo $row['id']; ?>" class="btn-view">
                                <?php 
                                    if($is_expired) echo "Closed";
                                    else echo $has_applied ? "View Status" : "View Details"; 
                                ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: span 3; text-align: center; padding: 50px; color: #94a3b8;">
                <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                No internships found.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmRedirect(url, title) {
    Swal.fire({
        title: title,
        text: "Are you sure you want to proceed?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Proceed'
    }).then((result) => { if (result.isConfirmed) { window.location.href = url; } });
}

function filterInternships() {
    let input = document.getElementById('internSearch').value.toLowerCase();
    let cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        let title = card.querySelector('.card-title').innerText.toLowerCase();
        let org = card.querySelector('.org-tag').innerText.toLowerCase();
        card.style.display = (title.includes(input) || org.includes(input)) ? "flex" : "none";
    });
}
</script>

</body>
</html>