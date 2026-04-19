<?php
session_start();
require_once("../config/db.php");

// 1. SESSION & IDENTITY FIX + ADMIN RESTRICTION
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') { 
    header("Location: ../auth/login.php"); 
    exit(); 
}

$reg_no = $_SESSION['register_no'] ?? $_SESSION['reg_no'] ?? $_SESSION['username'] ?? $_SESSION['student_id'] ?? 'N/A'; 
$student_name = $_SESSION['student_name'] ?? $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Student';

$op_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT * FROM opportunities WHERE id = $op_id LIMIT 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

// --- DYNAMIC LABEL LOGIC ---
$type = strtolower((string)($data['type'] ?? '')); 
$desc_label = "Description"; 
$show_files = false;

if($type == 'internship') {
    $desc_label = "Job Description";
    $show_files = true; 
}

// --- APPLIED CHECK ---
$already_applied = false;
$check = mysqli_query($conn, "SELECT id FROM applications WHERE student_id = '$reg_no' AND opportunity_id = '$op_id'");
if($check && mysqli_num_rows($check) > 0) { $already_applied = true; }

// --- IMAGE LOGIC ---
$db_filename = basename((string)($data['image'] ?? '')); 
$opp_image = "../images/" . $db_filename; 
if (empty($db_filename) || !file_exists($opp_image)) { $opp_image = "../images/default_placeholder.jpg"; }

// --- UPDATED DEADLINE LOGIC (DATE + TIME) ---
$deadline_raw = $data['deadline'] ?? '';
$current_time = time();
$deadline_time = !empty($deadline_raw) ? strtotime($deadline_raw) : 0;

// Calculate precise time difference
$time_diff = $deadline_time - $current_time;
$days_left = ceil($time_diff / 86400);

// Check if deadline is passed (Comparison based on Unix timestamp for second-level accuracy)
$is_closed = (!empty($deadline_raw) && $current_time > $deadline_time);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title'] ?? 'Opportunity'); ?> | ICSM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #6366f1; --dark: #0f172a; --glass: rgba(255, 255, 255, 0.05); --glass-border: rgba(255, 255, 255, 0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #070b14; height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        
        .bg-blur { position: fixed; inset: 0; background: url('<?php echo $opp_image; ?>') center/cover no-repeat; filter: blur(80px) brightness(0.2); z-index: -1; transform: scale(1.1); }
        .glass-container { width: 92%; max-width: 1350px; height: 85vh; background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(35px); border-radius: 40px; border: 1px solid var(--glass-border); display: flex; overflow: hidden; box-shadow: 0 50px 100px rgba(0,0,0,0.6); }
        
        .info-panel { flex: 1.6; background: white; overflow-y: auto; border-radius: 40px 0 0 40px; scrollbar-width: none; }
        .info-panel::-webkit-scrollbar { display: none; }
        .info-content-inner { padding: 45px 50px; }
        .hero-img { width: 100%; height: 340px; object-fit: cover; border-radius: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .header-text { margin: 30px 0; }
        .header-text h1 { font-size: 42px; font-weight: 800; color: var(--dark); letter-spacing: -1.5px; line-height: 1.1; }
        
        .deadline-card { display: flex; align-items: center; background: #fff1f2; padding: 15px 25px; border-radius: 20px; width: fit-content; margin-top: 15px; border: 1px solid #fecdd3; }
        .deadline-card i { color: #e11d48; font-size: 20px; margin-right: 15px; }
        .deadline-date { color: #e11d48; font-weight: 800; font-size: 15px; border-right: 1px solid rgba(225,29,72,0.2); padding-right: 15px; margin-right: 15px; }
        .deadline-days { color: #e11d48; font-weight: 700; font-size: 13px; opacity: 0.8; }

        .section-label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin: 35px 0 12px 0; border-top: 1px solid #f1f5f9; padding-top: 25px; }
        .section-content { color: #475569; line-height: 1.8; font-size: 16px; }

        .form-panel { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; border-left: 1px solid var(--glass-border); scrollbar-width: none; background: rgba(15, 23, 42, 0.8); position: relative; }
        .form-panel::-webkit-scrollbar { display: none; }
        
        .nav-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .icon-btn { width: 44px; height: 44px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; }
        .icon-btn:hover { background: #ef4444; border-color: #ef4444; color: white; }
        .icon-btn.home:hover { background: var(--primary); border-color: var(--primary); }

        .user-status { background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); padding: 15px; border-radius: 18px; color: white; font-size: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .id-chip { background: var(--glass); padding: 15px; border-radius: 15px; border: 1px solid var(--glass-border); color: #cbd5e1; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        .input-group { margin-bottom: 22px; }
        .input-group label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; }
        input, select, textarea { width: 100%; padding: 16px; border-radius: 15px; background: var(--glass); border: 1px solid var(--glass-border); color: white; outline: none; }
        
        .btn-confirm { width: 100%; padding: 20px; background: white; color: var(--dark); border-radius: 20px; font-weight: 800; border: none; cursor: pointer; margin-top: auto; transition: 0.4s; font-size: 16px; }
        .btn-confirm:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.4); }

        /* Deadline Over UI */
        .closed-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; }
        .closed-box { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 30px; border-radius: 30px; }
    </style>
</head>
<body>

<div class="bg-blur"></div>

<div class="glass-container">
    <div class="info-panel">
        <div class="info-content-inner">
            <img src="<?php echo $opp_image; ?>" class="hero-img" onerror="this.src='../images/default_placeholder.jpg'">
            
            <div class="header-text">
                <h1><?php echo htmlspecialchars($data['title'] ?? ''); ?></h1>
                <div style="color: var(--primary); font-weight: 700; font-size: 19px; margin-top: 10px;">
                    <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($data['organization'] ?? ''); ?>
                </div>
            </div>

            <span class="section-label"><?php echo $desc_label; ?></span>
            <div class="section-content"><?php echo nl2br(htmlspecialchars($data['description'] ?? '')); ?></div>

            <?php if(!empty($data['eligibility'])): ?>
                <span class="section-label">Eligibility Requirements</span>
                <div class="section-content"><?php echo nl2br(htmlspecialchars($data['eligibility'])); ?></div>
            <?php endif; ?>

            <span class="section-label">Submission Deadline</span>
            <div class="deadline-card">
                <i class="fa-solid fa-calendar-check"></i>
                <span class="deadline-date">
                    <?php echo !empty($deadline_raw) ? date('D, d M Y | h:i A', strtotime($deadline_raw)) : 'No Deadline'; ?>
                </span>
                <span class="deadline-days">
                    <?php echo (!$is_closed && $deadline_time > 0) ? ($days_left > 0 ? $days_left . " Days Left" : "Closing Soon") : "Closed"; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="form-panel">
        <?php if($is_closed): ?>
            <div class="closed-overlay">
                <div class="closed-box">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 50px; color: #ef4444; margin-bottom: 20px;"></i>
                    <h2 style="color: white; font-weight: 800;">Application Closed</h2>
                    <p style="color: #94a3b8; margin-top: 10px;">The deadline for this opportunity has passed. We are no longer accepting new submissions.</p>
                    <button onclick="window.history.back()" class="btn-confirm" style="margin-top: 30px; width: auto; padding: 15px 40px;">Back to Dashboard</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="nav-row">
            <h2 style="color: white; font-weight: 800; font-size: 26px;">Apply Now</h2>
            <div style="display: flex; gap: 10px;">
                <div class="icon-btn home" onclick="confirmExit('student_dashboard.php')"><i class="fa-solid fa-house"></i></div>
                <div class="icon-btn" onclick="confirmExit('../auth/logout.php')"><i class="fa-solid fa-power-off"></i></div>
            </div>
        </div>

        <div class="user-status">
            <i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Applying as <b><?php echo htmlspecialchars($student_name); ?></b>
        </div>

        <div class="id-chip">
            <i class="fa-solid fa-id-card" style="color: var(--primary);"></i> Register Number: <b><?php echo htmlspecialchars($reg_no); ?></b>
        </div>

        <?php if($already_applied): ?>
            <div style="text-align: center; margin-top: 50px; color: #10b981;">
                <i class="fa-solid fa-circle-check" style="font-size: 60px; margin-bottom: 20px;"></i>
                <h3 style="color: white;">Application Received</h3>
                <p style="color: #94a3b8; margin-top: 10px;">Your response has already been submitted.</p>
                <button onclick="window.history.back()" class="btn-confirm" style="margin-top: 40px;">Go Back</button>
            </div>
        <?php else: ?>
            <form id="applyForm" action="submit_handler.php" method="POST" enctype="multipart/form-data" style="flex:1; display:flex; flex-direction:column;">
                <input type="hidden" name="op_id" value="<?php echo $op_id; ?>">
                
                <?php 
                $form_json = $data['custom_fields'] ?? ''; 
                $fields = json_decode($form_json, true);

                if(!empty($fields) && is_array($fields)) {
                    foreach($fields as $field) {
                        $raw_label = $field['label'] ?? $field['name'] ?? 'Requirement';
                        $f_label = ucwords(str_replace(['_', '-'], ' ', $raw_label));
                        
                        $f_name = htmlspecialchars((string)($field['name'] ?? 'field'));
                        $f_type = $field['type'] ?? 'text';

                        echo '<div class="input-group">';
                        echo '<label>' . $f_label . '</label>';
                        if($f_type == 'file') {
                            echo '<input type="file" name="custom_' . $f_name . '" required ' . ($is_closed ? 'disabled' : '') . '>';
                        } else {
                            echo '<input type="text" name="custom_' . $f_name . '" placeholder="Enter ' . $f_label . '" required ' . ($is_closed ? 'disabled' : '') . '>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="input-group"><label>Contact Phone</label><input type="text" name="phone" placeholder="+91..." required ' . ($is_closed ? 'disabled' : '') . '></div>';
                    if($show_files) {
                        echo '<div class="input-group"><label>Upload CV (PDF)</label><input type="file" name="cv" accept=".pdf" required ' . ($is_closed ? 'disabled' : '') . '></div>';
                    }
                }
                ?>
                <button type="submit" class="btn-confirm" <?php echo $is_closed ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>Submit Application</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('applyForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const btn = form.querySelector('.btn-confirm');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            Swal.fire({
                title: 'Application Sent!',
                text: 'Successfully submitted your application.',
                icon: 'success',
                timer: 2000,
                width: '380px',
                padding: '2em',
                showConfirmButton: false,
                background: '#ffffff',
                color: '#0f172a',
                iconColor: '#6366f1',
                customClass: {
                    title: 'swal-title-custom',
                    popup: 'swal-popup-border'
                }
            }).then(() => {
                window.location.href = "student_<?php echo $type; ?>.php";
            });
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = 'Submit Application';
            Swal.fire({
                title: 'Submission Failed',
                text: 'Network error, please try again.',
                icon: 'error',
                width: '380px',
                background: '#ffffff'
            });
        });
    });

    function confirmExit(url) {
        Swal.fire({
            title: 'Leave page?',
            text: "Your application progress will be lost.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#cbd5e1',
            confirmButtonText: 'Yes, Leave',
            cancelButtonText: 'Stay',
            background: '#ffffff',
            color: '#0f172a',
            width: '350px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

<style>
    .swal-popup-border { border-radius: 30px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important; }
    .swal-title-custom { font-weight: 800 !important; letter-spacing: -0.5px; }
</style>

</body>
</html>