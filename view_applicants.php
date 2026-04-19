<?php
session_start();
include("../config/db.php");

// 1. AUTH CHECK
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php"); exit();
}

$op_id = intval($_GET['id']);
$admin_name = $_SESSION['admin_name'] ?? "Admin";
$current_admin_id = $_SESSION['user_id'];

// Fetch Opportunity Details
$opp_q = mysqli_query($conn, "SELECT title FROM opportunities WHERE id = $op_id");
$opp = mysqli_fetch_assoc($opp_q);

// Check if any split exists for this opportunity
$split_check = mysqli_query($conn, "SELECT id FROM applications WHERE opportunity_id = $op_id AND assigned_to IS NOT NULL LIMIT 1");
$is_split = mysqli_num_rows($split_check) > 0;

// If split, only show applications assigned to the logged-in admin
$assignment_filter = ($is_split) ? " AND a.assigned_to = $current_admin_id" : "";

// Fetch Applicants
$apps_q = mysqli_query($conn, "SELECT a.*, s.name as student_real_name, ad_assign.name as assigned_to_name
                               FROM applications a 
                               JOIN students s ON a.student_id = s.register_no 
                               LEFT JOIN admins ad_assign ON a.assigned_to = ad_assign.id
                               WHERE a.opportunity_id = $op_id $assignment_filter 
                               ORDER BY a.id ASC");

// 2. SPLIT LOGIC
if(isset($_POST['perform_split'])) {
    $selected_admins = $_POST['admin_ids'] ?? [];
    if(!empty($selected_admins)) {
        $all_apps = [];
        $apps_res = mysqli_query($conn, "SELECT id FROM applications WHERE opportunity_id = $op_id");
        while($r = mysqli_fetch_assoc($apps_res)) $all_apps[] = $r['id'];
        
        if(count($all_apps) > 0) {
            $chunks = array_chunk($all_apps, ceil(count($all_apps) / count($selected_admins)));
            foreach($selected_admins as $index => $admin_id) {
                if(isset($chunks[$index])) {
                    $ids = implode(',', $chunks[$index]);
                    mysqli_query($conn, "UPDATE applications SET assigned_to = $admin_id WHERE id IN ($ids)");
                }
            }
        }
    }
    // Updated redirect to ensure browser "back" logic flows to the verification list
    header("Location: verify_opportunities.php"); exit();
}

// 3. UNSPLIT LOGIC
if(isset($_POST['undo_split'])) {
    mysqli_query($conn, "UPDATE applications SET assigned_to = NULL WHERE opportunity_id = $op_id");
    // Updated redirect to ensure browser "back" logic flows to the verification list
    header("Location: verify_opportunities.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applicants | <?= htmlspecialchars($opp['title'] ?? 'List') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; --success: #10b981; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 30px; color: var(--dark); }
        .container { max-width: 1200px; margin: auto; }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 12px 20px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 14px; transition: 0.2s; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .btn-split { background: var(--primary); color: white; }
        .btn-unsplit { background: #fee2e2; color: var(--danger); }

        .split-ui { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; border: 1px dashed var(--primary); display: none; }
        
        .table-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 15px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 800; }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .status-pill { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .review-btn { background: var(--dark); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h1 style="font-weight: 900; letter-spacing: -1px;"><?= htmlspecialchars($opp['title'] ?? 'Applications') ?></h1>
            <p style="color: #64748b; font-size: 14px;"><?= $is_split ? "Showing tasks assigned to you." : "Review and manage student applications for this opportunity." ?></p>
        </div>
        <div class="btn-group">
            <button onclick="toggleSplit()" class="btn btn-split"><i class="fa-solid fa-arrows-split-up-and-left"></i> Split Task</button>
            
            <form method="POST" onsubmit="return confirm('Remove all current assignments?')">
                <button name="undo_split" class="btn btn-unsplit"><i class="fa-solid fa-rotate-left"></i> Unsplit All</button>
            </form>
        </div>
    </div>

    <div id="splitSection" class="split-ui">
        <form method="POST">
            <h4 style="margin-bottom:15px; color:var(--primary);">Assign Tasks to Admins</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                <?php 
                $admins_q = mysqli_query($conn, "SELECT id, name FROM admins");
                while($ad = mysqli_fetch_assoc($admins_q)): 
                ?>
                    <label style="background:#f8fafc; padding:10px 15px; border-radius:10px; border:1px solid #e2e8f0; cursor:pointer; font-weight:700; font-size:14px;">
                        <input type="checkbox" name="admin_ids[]" value="<?= $ad['id'] ?>"> <?= $ad['name'] ?>
                    </label>
                <?php endwhile; ?>
            </div>
            <button name="perform_split" class="btn btn-split" style="width:100%; justify-content:center;">Distribute Equally Among Selected Admins</button>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Details</th>
                    <th>Box Status</th>
                    <th>Reviewed By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($apps_q) > 0): ?>
                    <?php while($app = mysqli_fetch_assoc($apps_q)): ?>
                    <tr>
                        <td style="font-weight: 800; color: #94a3b8;">#<?= $app['id'] ?></td>
                        <td>
                            <span style="display:block; font-weight: 800;"><?= htmlspecialchars($app['student_real_name']) ?></span>
                            <small style="color: #64748b;"><?= $app['student_id'] ?></small>
                        </td>
                        <td>
                            <?php 
                            $rep_check = mysqli_query($conn, "SELECT ar.review_status, ad.name as reviewer_name 
                                                              FROM application_reports ar 
                                                              LEFT JOIN admins ad ON ar.admin_id = ad.id 
                                                              WHERE ar.application_id = ".$app['id']);
                            $rep_data = mysqli_fetch_assoc($rep_check);
                            if($rep_data): ?>
                                <span class="status-pill" style="background:<?= $rep_data['review_status']=='Approved' ? '#dcfce7':'#fee2e2' ?>; color:<?= $rep_data['review_status']=='Approved' ? '#15803d':'#b91c1c' ?>;">
                                    <?= $rep_data['review_status'] ?> (Staged)
                                </span>
                            <?php else: ?>
                                <span class="status-pill" style="background:#f1f5f9; color:#64748b;"><?= $app['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($rep_data && $rep_data['reviewer_name']): ?>
                                <span style="font-weight:700; color:var(--primary); font-size:12px;"><i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($rep_data['reviewer_name']) ?></span>
                            <?php else: ?>
                                <span style="color:#cbd5e1; font-style:italic; font-size:12px;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="review_application.php?app_id=<?= $app['id'] ?>" class="review-btn">Review</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No applications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleSplit() {
    var x = document.getElementById("splitSection");
    x.style.display = (x.style.display === "none" || x.style.display === "") ? "block" : "none";
}
</script>

</body>
</html>