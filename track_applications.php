<?php
session_start();
include("../config/db.php");

/* AUTH CHECK */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "student"){
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['register_no'] ?? $_SESSION['user_id'];

/* FETCH DATA 
   Updated to use created_at from your applications table
*/
$sql = "SELECT 
            a.id,
            a.created_at,
            o.title,
            o.deadline,
            o.type,
            r.review_status,
            r.finalized
        FROM applications a
        JOIN opportunities o ON a.opportunity_id = o.id
        LEFT JOIN application_reports r ON r.application_id = a.id
        WHERE a.student_id = ?
        ORDER BY a.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Tracker | ICSM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #4f46e5; --bg: #f8fafc; --glass: rgba(255, 255, 255, 0.9); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        h2 { font-weight: 800; font-size: 32px; color: #1e293b; }
        h2 span { color: var(--primary); }
        .back-btn { text-decoration: none; color: #64748b; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .back-btn:hover { color: var(--primary); transform: translateX(-5px); }
        
        .card { background: var(--glass); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.5); padding: 20px 35px; border-radius: 24px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        .details h3 { font-size: 18px; color: #1e293b; font-weight: 700; margin-bottom: 4px; }
        .details p { font-size: 13px; color: #64748b; font-weight: 500; }
        .meta-info { display: flex; gap: 20px; margin-top: 5px; }
        
        .status-pill { padding: 10px 20px; border-radius: 14px; font-size: 12px; font-weight: 800; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 150px; justify-content: center; }
        .applied { background: #e0e7ff; color: #4338ca; }
        .review { background: #fef3c7; color: #92400e; }
        .approved { background: #dcfce7; color: #15803d; }
        .rejected { background: #fee2e2; color: #b91c1c; }
        
        .empty-state { text-align: center; padding: 60px; background: var(--glass); border-radius: 24px; color: #64748b; }
        
        @media (max-width: 600px) { .card { flex-direction: column; align-items: flex-start; gap: 15px; } .status-pill { width: 100%; } .meta-info { flex-direction: column; gap: 5px; } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <a href="student_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        <h2>Track <span>Applications</span></h2>
    </div>

    <?php
    if($result && $result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $deadline = $row['deadline'];
            $applied_at = $row['created_at'];
            $today = date('Y-m-d');
            $status = ""; $class = ""; $statusIcon = "";

            /* STATUS LOGIC */
            if($row['finalized'] == 1) {
                if(strtolower($row['review_status']) === "approved"){
                    $status = "Approved"; $class = "approved"; $statusIcon = "fa-circle-check";
                } else {
                    $status = "Rejected"; $class = "rejected"; $statusIcon = "fa-circle-xmark";
                }
            } 
            else if(strtotime($today) > strtotime($deadline)) {
                $status = "In Review"; $class = "review"; $statusIcon = "fa-clock";
            } 
            else {
                $status = "Applied"; $class = "applied"; $statusIcon = "fa-paper-plane";
            }
    ?>

    <div class="card">
        <div class="details">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <div class="meta-info">
                <p><i class="fa-solid fa-calendar-check"></i> Applied: <?php echo date('M d, Y', strtotime($applied_at)); ?></p>
                <p><i class="fa-solid fa-hourglass-end"></i> Deadline: <?php echo date('M d, Y', strtotime($deadline)); ?></p>
            </div>
        </div>

        <div class="status-pill <?php echo $class; ?>">
            <i class="fa-solid <?php echo $statusIcon; ?>"></i>
            <?php echo $status; ?>
        </div>
    </div>

    <?php
        }
    } else {
    ?>
        <div class="empty-state">
            <i class="fa-solid fa-folder-open" style="font-size: 50px; margin-bottom: 20px; opacity: 0.3;"></i>
            <h3>No applications yet</h3>
            <p>Go to the dashboard to find new opportunities!</p>
        </div>
    <?php
    }
    ?>
</div>

</body>
</html>