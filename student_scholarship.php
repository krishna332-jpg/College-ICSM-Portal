<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_name = $_SESSION['student_name'] ?? "Student";
$reg_no = $_SESSION['register_no'] ?? "24BCAXXXX";

// Database Fetch - Specifically for Scholarships
$query = "SELECT * FROM opportunities WHERE type = 'Scholarship' AND status = 'active' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Scholarships | ICSM Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #f59e0b; /* Amber/Gold for Scholarship theme */
            --dark: #0f172a; 
            --bg: #e2e8f0; 
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); min-height: 100vh; }

        /* --- NAVBAR --- */
        .navbar {
            background: white;
            padding: 12px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .nav-left { display: flex; align-items: center; gap: 25px; }
        .logo { font-weight: 900; font-size: 22px; color: var(--dark); text-decoration: none; letter-spacing: -1px; }
        .logo span { color: var(--primary); }
        .home-link { color: #64748b; font-size: 20px; transition: 0.3s; }
        .home-link:hover { color: var(--primary); }

        .nav-right { display: flex; align-items: center; gap: 15px; }
        
        .user-pill { 
            display: flex; align-items: center; gap: 12px; 
            background: #fdfaf3; padding: 5px 15px 5px 6px; 
            border-radius: 50px; border: 1px solid #fde68a;
        }

        .avatar {
            width: 35px; height: 35px; background: var(--primary); color: white; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px;
        }

        .u-info { line-height: 1.2; }
        .u-name { display: block; font-size: 13px; font-weight: 700; }
        .u-reg { font-size: 10px; color: #64748b; font-weight: 600; }

        .logout-btn { color: #f87171; font-size: 18px; margin-left: 10px; transition: 0.3s; }
        .logout-btn:hover { color: #ef4444; transform: scale(1.1); }

        /* --- HERO: 3D Image Effect --- */
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
            background: #f8fafc; padding: 12px 20px; border-radius: 15px;
            display: flex; align-items: center; gap: 12px;
            width: 100%; max-width: 400px; border: 1px solid #e2e8f0;
        }
        .search-wrapper input { background: transparent; border: none; outline: none; width: 100%; font-size: 15px; font-weight: 500; }

        /* Path updated to scholar.jpg */
        .hero-img-box {
            width: 480px; height: 320px;
            background: url('../images/scholar.jpg') no-repeat center center;
            background-size: cover; border-radius: 25px;
            box-shadow: -20px 20px 60px rgba(0,0,0,0.1);
            transform: perspective(1000px) rotateY(-10deg) rotateX(4deg);
            transition: 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }
        .hero-container:hover .hero-img-box {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1.05);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        /* --- GRID & CARDS --- */
        .container { max-width: 1250px; margin: 0 auto 100px; padding: 0 20px; }
        .opp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }

        .card {
            background: white; border-radius: 25px; overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03); display: flex; flex-direction: column; transition: 0.3s;
        }
        .card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }

        .card-banner { height: 180px; width: 100%; background-size: cover; background-position: center; }

        .card-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .org-tag { 
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            color: var(--primary); background: #fef3c7; padding: 5px 12px;
            border-radius: 8px; width: fit-content; margin-bottom: 12px;
        }
        .card-title { font-size: 19px; font-weight: 800; margin-bottom: 8px; color: var(--dark); }

        .card-footer {
            margin-top: auto; padding-top: 20px; border-top: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }

        .deadline { font-size: 13px; font-weight: 700; color: #b45309; }
        .deadline span { display: block; color: #94a3b8; font-size: 10px; text-transform: uppercase; }

        .btn-view {
            background: var(--dark); color: white; padding: 10px 20px;
            border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px;
        }
        .btn-view:hover { background: var(--primary); }

    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="student_dashboard.php" class="home-link"><i class="fa-solid fa-house-chimney"></i></a>
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
        <a href="../auth/logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i></a>
    </div>
</nav>

<section class="hero-container">
    <div class="hero-text">
      <h1>Scholarships & <br>Financial Aid</h1>
<p>Discover funding opportunities designed to support your academic journey and future goals.</p>
        
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="schSearch" placeholder="Search by name or provider..." onkeyup="filterScholarships()">
        </div>
    </div>
    <div class="hero-img-box"></div>
</section>

<div class="container">
    <div class="opp-grid" id="schGrid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <div class="card-banner" style="background-image: url('../<?php echo $row['image']; ?>'), url('../images/default_scholar.jpg');"></div>
                    <div class="card-body">
                        <span class="org-tag"><?php echo $row['organization']; ?></span>
                        <h2 class="card-title"><?php echo $row['title']; ?></h2>
                        
                        <div class="card-footer">
                            <div class="deadline">
                                <span>Closing Date</span>
                                <?php echo date('d M Y', strtotime($row['deadline'])); ?>
                            </div>
                            <a href="view_opportunity.php?id=<?php echo $row['id']; ?>" class="btn-view">Apply Now</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: span 3; text-align: center; padding: 50px; color: #94a3b8;">
                <i class="fa-solid fa-hand-holding-dollar" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                No scholarships available at the moment.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterScholarships() {
    let input = document.getElementById('schSearch').value.toLowerCase();
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