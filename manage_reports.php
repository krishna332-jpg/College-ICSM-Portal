<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$op_id = intval($_GET['id']);

// 1. Fetch Opportunity Details for the Header
$opp_res = mysqli_query($conn, "SELECT title FROM opportunities WHERE id = $op_id");
$opp_data = mysqli_fetch_assoc($opp_res);
$title = $opp_data['title'] ?? "Opportunity";

// 2. Check if this specific opportunity is already finalized
$check_final = mysqli_query($conn, "SELECT id FROM application_reports WHERE opportunity_id = $op_id AND finalized = 1 LIMIT 1");
$is_finalized = (mysqli_num_rows($check_final) > 0);

// 3. Move Student Logic (Only if NOT finalized)
if(isset($_GET['move_id']) && !$is_finalized) {
    $rep_id = intval($_GET['move_id']);
    $to = ($_GET['to'] == 'Approved') ? 'Approved' : 'Rejected';
    mysqli_query($conn, "UPDATE application_reports SET review_status = '$to' WHERE id = $rep_id");
    header("Location: manage_reports.php?id=$op_id");
    exit();
}

// 4. Fetch the Lists
$approved = mysqli_query($conn, "SELECT r.*, s.name FROM application_reports r JOIN students s ON r.student_id = s.register_no WHERE r.opportunity_id = $op_id AND r.review_status = 'Approved'");
$rejected = mysqli_query($conn, "SELECT r.*, s.name FROM application_reports r JOIN students s ON r.student_id = s.register_no WHERE r.opportunity_id = $op_id AND r.review_status = 'Rejected'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finalize | <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --bg: #f8fafc; --danger: #ef4444; --success: #10b981; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 30px; color: var(--dark); }
        .container { max-width: 1200px; margin: auto; }
        
        /* Header Section */
        .header-box { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .back-link { color: var(--primary); text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 10px; }
        
        /* Status Badges */
        .status-pill { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        .pill-approved { background: #dcfce7; color: #15803d; }
        .pill-rejected { background: #fee2e2; color: #b91c1c; }
        .pill-finalized { background: var(--dark); color: white; padding: 10px 20px; font-size: 13px; border-radius: 50px; }

        /* Table Card UI */
        .section-label { font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .table-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 40px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 15px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 800; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        
        /* Action Buttons */
        .action-btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 12px; transition: 0.2s; display: inline-flex; align-items: center; }
        .btn-reject { background: #fee2e2; color: var(--danger); }
        .btn-approve { background: #dcfce7; color: var(--success); }
        .action-btn:hover { transform: translateY(-1px); filter: brightness(0.95); }

        .finalize-bar { background: white; padding: 25px; border-radius: 20px; border: 1px dashed var(--primary); text-align: center; margin-top: 20px; }
        .btn-publish { background: var(--primary); color: white; padding: 15px 40px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer; font-size: 15px; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; }
        .btn-publish:hover { background: var(--dark); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h1 style="font-weight: 900; letter-spacing: -1.5px; font-size: 32px;"><?= htmlspecialchars($title) ?></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Review final lists before locking the selection.</p>
        </div>
        <?php if($is_finalized): ?>
            <div class="pill-finalized"><i class="fa-solid fa-lock"></i> RESULTS PUBLISHED</div>
        <?php endif; ?>
    </div>

    <div class="section-label" style="color: var(--success);"><i class="fa-solid fa-circle-check"></i> Approved Students (<?= mysqli_num_rows($approved) ?>)</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Registration No.</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($approved) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($approved)): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--dark);"><?= htmlspecialchars($row['name']) ?></td>
                        <td style="color: #64748b; font-family: monospace; font-weight: 600;"><?= $row['student_id'] ?></td>
                        <td><span class="status-pill pill-approved">Approved</span></td>
                        <td style="text-align: right;">
                            <?php if(!$is_finalized): ?>
                                <a href="?id=<?= $op_id ?>&move_id=<?= $row['id'] ?>&to=Rejected" class="action-btn btn-reject">Move to Rejected</a>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-size: 12px; font-style: italic;">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;">No approved students yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-label" style="color: var(--danger);"><i class="fa-solid fa-circle-xmark"></i> Rejected Students (<?= mysqli_num_rows($rejected) ?>)</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Registration No.</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($rejected) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($rejected)): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--dark);"><?= htmlspecialchars($row['name']) ?></td>
                        <td style="color: #64748b; font-family: monospace; font-weight: 600;"><?= $row['student_id'] ?></td>
                        <td><span class="status-pill pill-rejected">Rejected</span></td>
                        <td style="text-align: right;">
                            <?php if(!$is_finalized): ?>
                                <a href="?id=<?= $op_id ?>&move_id=<?= $row['id'] ?>&to=Approved" class="action-btn btn-approve">Move to Approved</a>
                            <?php else: ?>
                                <span style="color: #cbd5e1; font-size: 12px; font-style: italic;">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;">No rejected students.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if(!$is_finalized): ?>
    <div class="finalize-bar">
        <h3 style="margin-bottom: 10px; font-weight: 800;">Ready to finalize?</h3>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">This will lock the selection and notify all applicants of their status.</p>
        <form action="finalize_process.php" method="POST">
            <input type="hidden" name="opp_id" value="<?= $op_id ?>">
            <button type="submit" class="btn-publish" onclick="return confirm('Lock results and notify students? This cannot be undone.')">
                <i class="fa-solid fa-cloud-arrow-up"></i> Confirm & Publish Results
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

</body>
</html>