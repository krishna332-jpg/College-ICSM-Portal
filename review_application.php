<?php
session_start();
include("../config/db.php");

// ERROR REPORTING
ini_set('display_errors', 1);
error_reporting(E_ALL);

// AUTH
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php"); 
    exit();
}

// GET APPLICATION ID
if(!isset($_GET['app_id'])) { 
    die("No Application ID provided."); 
}

$app_id = intval($_GET['app_id']);
$admin_id = $_SESSION['user_id'];

// FETCH DATA
$query = "SELECT a.*, o.title as opp_title, s.name as student_name, s.email as student_email 
          FROM applications a
          JOIN opportunities o ON a.opportunity_id = o.id
          JOIN students s ON a.student_id = s.register_no
          WHERE a.id = $app_id LIMIT 1";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) { 
    die("Application not found."); 
}

// ACTION (APPROVE / REJECT)
if (isset($_POST['action'])) {
    $new_status = ($_POST['action'] == 'approve') ? 'Approved' : 'Rejected';
    $opp_id = $data['opportunity_id'];
    $reg_no = $data['student_id']; 

    $report_sql = "INSERT INTO application_reports 
                   (application_id, opportunity_id, student_id, review_status, admin_id) 
                   VALUES ($app_id, $opp_id, '$reg_no', '$new_status', $admin_id)
                   ON DUPLICATE KEY UPDATE 
                   review_status = '$new_status', admin_id = $admin_id";
    
    if(mysqli_query($conn, $report_sql)) {
        mysqli_query($conn, "UPDATE applications SET status = 'Reviewed' WHERE id = $app_id");
        header("Location: view_applicants.php?id=" . $opp_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review: <?= htmlspecialchars($data['student_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --slate: #64748b;
            --bg: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { background: var(--bg); color: var(--dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }

        .container {
            width: 100%;
            max-width: 1000px;
            height: 85vh;
            background: white;
            border-radius: 32px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(0,0,0,0.03);
        }

        .sidebar {
            flex: 0 0 340px;
            background: var(--dark);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .back-link {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .back-link:hover { color: white; transform: translateX(-5px); }

        .avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 24px;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .student-info h1 { font-size: 28px; font-weight: 800; line-height: 1.2; margin-bottom: 8px; }
        .student-info p { color: var(--slate); font-size: 15px; font-weight: 500; margin-bottom: 20px; }
        
        .badge {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .badge i { color: var(--primary); }

        .content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            padding: 50px 50px 30px 50px; 
            position: relative;
        }

        .scroll-area {
            flex: 1;
            overflow-y: auto;
            padding-right: 15px;
            margin-bottom: 20px;
        }

        .scroll-area::-webkit-scrollbar { width: 6px; }
        .scroll-area::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .scroll-area::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        .section-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--slate);
            font-weight: 800;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .section-title::after { content: ""; height: 1px; flex: 1; background: #e2e8f0; }

        .data-grid { display: grid; gap: 20px; }

        .detail-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            transition: 0.3s;
        }
        .detail-item:hover { background: white; border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.02); }

        .detail-label { font-size: 11px; font-weight: 700; color: var(--slate); text-transform: uppercase; margin-bottom: 6px; }
        .detail-value { font-size: 16px; font-weight: 600; color: var(--dark); word-break: break-word; }

        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: var(--primary);
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
        }
        .btn-view:hover { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); }

        .action-bar {
            display: flex;
            gap: 16px;
            background: white;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            flex-shrink: 0; 
        }

        .btn {
            flex: 1;
            padding: 18px;
            border-radius: 18px;
            font-weight: 800;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-reject { background: #fee2e2; color: var(--danger); }
        .btn-reject:hover { background: var(--danger); color: white; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2); }

        .btn-approve { background: var(--primary); color: white; }
        .btn-approve:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); filter: brightness(1.1); }

        /* Toast Popup Styles */
        .toast {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            padding: 16px 30px;
            border-radius: 16px;
            background: var(--dark);
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        .toast-success { background: var(--success); }
        .toast-danger { background: var(--danger); }

    </style>
</head>
<body>

<div id="toastBox" class="toast">
    <i id="toastIcon"></i>
    <span id="toastMsg"></span>
</div>

<div class="container">
    <div class="sidebar">
        <a href="view_applicants.php?id=<?= $data['opportunity_id'] ?>" class="back-link">
            <i class="fa-solid fa-chevron-left"></i> Back to Applicants
        </a>
        
        <div class="avatar">
            <?= strtoupper(substr($data['student_name'], 0, 1)) ?>
        </div>

        <div class="student-info">
            <h1><?= htmlspecialchars($data['student_name']) ?></h1>
            <p><?= htmlspecialchars($data['student_email']) ?></p>
            
            <div class="badge">
                <i class="fa-solid fa-id-badge"></i>
                <span>Reg: <?= htmlspecialchars($data['student_id']) ?></span>
            </div>
            
            <div class="badge">
                <i class="fa-solid fa-briefcase"></i>
                <span><?= htmlspecialchars($data['opp_title']) ?></span>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="section-title">Application Details</div>

        <div class="scroll-area">
            <div class="data-grid">
                <?php
                $exclude = [
                    'id','opportunity_id','student_id','status','created_at',
                    'assigned_to','split_by','opp_title','student_name',
                    'student_email','form_data'
                ];

                foreach($data as $key => $val){
                    if(in_array($key, $exclude)) continue;
                    if(empty($val)) continue;
                    ?>
                    <div class="detail-item">
                        <div class="detail-label"><?= str_replace('_',' ',$key) ?></div>
                        <div class="detail-value"><?= htmlspecialchars($val) ?></div>
                    </div>
                    <?php
                }

                $form_json = $data['form_data'] ?? '';
                $decoded = json_decode($form_json, true);

                if (!empty($decoded) && is_array($decoded)) {
                    foreach($decoded as $key => $val){
                        if(empty($val)) continue;
                        $label = ucwords(str_replace(['custom_','_'],' ',$key));
                        $val_str = (string)$val;
                        ?>
                        <div class="detail-item">
                            <div class="detail-label"><?= $label ?></div>
                            <div class="detail-value">
                                <?php
                                $ext = strtolower(pathinfo($val_str, PATHINFO_EXTENSION));
                                $file_types = ['pdf','doc','docx','jpg','jpeg','png'];
                                if(in_array($ext, $file_types)){
                                    echo '<div style="font-size:13px; color:var(--slate); margin-bottom:5px;">'.htmlspecialchars($val_str).'</div>';
                                    echo '<a href="../uploads/'.htmlspecialchars($val_str).'" target="_blank" class="btn-view">
                                            <i class="fa-solid fa-file-lines"></i> View Document
                                          </a>';
                                } else {
                                    echo nl2br(htmlspecialchars($val_str));
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <form id="reviewForm" method="POST" class="action-bar">
            <button type="button" class="btn btn-reject" onclick="handleReview('reject')">
                <i class="fa-solid fa-xmark"></i> Reject
            </button>
            <button type="button" class="btn btn-approve" onclick="handleReview('approve')">
                <i class="fa-solid fa-check"></i> Approve
            </button>
            <input type="hidden" name="action" id="actionInput">
        </form>
    </div>
</div>

<script>
function handleReview(type) {
    const toast = document.getElementById('toastBox');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMsg');
    const actionInput = document.getElementById('actionInput');
    const form = document.getElementById('reviewForm');

    // Configure visual appearance
    if(type === 'approve') {
        toast.className = "toast show toast-success";
        icon.className = "fa-solid fa-circle-check";
        msg.innerText = "Application Approved!";
    } else {
        toast.className = "toast show toast-danger";
        icon.className = "fa-solid fa-circle-xmark";
        msg.innerText = "Application Rejected!";
    }

    // Set value and submit after 2 seconds
    actionInput.value = type;
    setTimeout(() => {
        form.submit();
    }, 2000);
}
</script>

</body>
</html>