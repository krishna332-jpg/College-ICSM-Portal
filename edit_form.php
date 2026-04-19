<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? $_POST['id'] ?? $_SESSION['last_edited_id'] ?? null;
if(!$id) die("Error: No ID provided.");

$id = mysqli_real_escape_string($conn, $id);
$query = "SELECT * FROM opportunities WHERE id = '$id' LIMIT 1";
$res = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($res);

if(!$data) die("Error: Record not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit | <?= htmlspecialchars($data['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #4f46e5; 
            --bg: #f1f5f9; 
            --white: #ffffff;
            --dark: #0f172a;
            --slate: #64748b;
            --border: #e2e8f0;
            --accent: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--dark); padding: 40px; }

        .container { max-width: 1000px; margin: auto; }

        /* Header */
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-info h1 { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .opp-meta { display: flex; gap: 15px; margin-top: 5px; }
        .badge { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; background: #e2e8f0; color: var(--slate); text-transform: uppercase; }
        .badge-type { background: rgba(79, 70, 229, 0.1); color: var(--primary); }

        .main-card { background: var(--white); padding: 45px; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); }
        .section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--slate); margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        /* Form Layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full-width { grid-column: span 2; }
        label { display: block; font-size: 11px; font-weight: 700; color: var(--dark); margin-bottom: 8px; text-transform: uppercase; }
        input, textarea { width: 100%; padding: 15px; margin-bottom: 20px; border: 1.5px solid var(--border); border-radius: 12px; font-size: 15px; outline: none; transition: 0.2s; }
        input:focus, textarea:focus { border-color: var(--primary); background: #fcfdfe; }

        .btn-save { background: var(--primary); color: white; padding: 18px 40px; border: none; border-radius: 15px; font-weight: 800; cursor: pointer; font-size: 16px; margin-top: 20px; width: 100%; transition: 0.3s; }
        .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }

        .banner-box { display: flex; gap: 20px; align-items: center; background: #f8fafc; padding: 20px; border-radius: 15px; border: 1px dashed var(--border); }
        .banner-preview { width: 120px; height: 80px; object-fit: cover; border-radius: 10px; }

        /* --- CUSTOM POPUP STYLES --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; z-index: 1000;
        }
        .custom-modal {
            background: white; padding: 40px; border-radius: 30px; text-align: center;
            max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            animation: slideUp 0.4s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-icon { font-size: 50px; color: var(--accent); margin-bottom: 20px; }
        .modal-title { font-size: 22px; font-weight: 800; margin-bottom: 10px; }
        .modal-text { color: var(--slate); margin-bottom: 10px; line-height: 1.5; }

        .success-toast {
            position: fixed; bottom: 30px; right: 30px; background: var(--dark); color: white;
            padding: 15px 30px; border-radius: 15px; font-weight: 600; display: none;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2); z-index: 1100;
        }
    </style>
</head>
<body>

<div id="confirmModal" class="modal-overlay">
    <div class="custom-modal">
        <div class="modal-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="modal-title">Success!</div>
        <div class="modal-text">The opportunity details have been updated successfully.</div>
        <p style="font-size: 13px; color: var(--slate);">Redirecting to dashboard...</p>
    </div>
</div>

<div id="successToast" class="success-toast">
    <i class="fa-solid fa-check-double" style="color:var(--accent); margin-right:10px;"></i> Updation Successful
</div>

<div class="container">
    <div class="header-section">
        <div class="header-info">
            <h1>Edit Opportunity</h1>
            <div class="opp-meta">
                <span class="badge"><i class="fa-solid fa-hashtag"></i> ID: <?= $id ?></span>
                <span class="badge badge-type"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($data['type'] ?? 'General') ?></span>
                <span class="badge"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($data['organization']) ?></span>
            </div>
        </div>
        <a href="admin_dashboard.php" style="text-decoration:none; color:var(--slate); font-weight:700;">
            <i class="fa-solid fa-chevron-left"></i> Dashboard
        </a>
    </div>

    <div class="main-card">
        <div class="section-title"><i class="fa-solid fa-pen-nib"></i> General Information</div>
        
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-grid">
                <div class="full-width">
                    <label>Opportunity Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($data['title']) ?>" required>
                </div>
                <div class="full-width">
                    <label>Full Description</label>
                    <textarea name="description" rows="5"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                </div>
                <div class="full-width">
                    <label>Eligibility Criteria</label>
                    <textarea name="eligibility" rows="3"><?= htmlspecialchars($data['eligibility'] ?? '') ?></textarea>
                </div>
                <div>
                    <label>Host Organization</label>
                    <input type="text" name="organization" value="<?= htmlspecialchars($data['organization']) ?>">
                </div>
                <div>
                    <label>Application Deadline</label>
                    <input type="date" name="deadline" value="<?= $data['deadline'] ?>">
                </div>
                <div class="full-width">
                    <label>Header Banner</label>
                    <div class="banner-box">
                        <img src="../<?= $data['image'] ?>" class="banner-preview">
                        <div>
                            <p style="font-size: 12px; font-weight: 700; margin-bottom: 5px;">Update Banner Image</p>
                            <input type="file" name="image" style="margin-bottom:0; padding:5px; border:none;">
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-save">Update Opportunity Details</button>
        </form>
    </div>
</div>

<script>
const form = document.getElementById('editForm');
const modal = document.getElementById('confirmModal');

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('edit_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        // 1. Show the Modal
        modal.style.display = 'flex';
        
        // 2. Automatically redirect after 2 seconds
        setTimeout(() => {
            window.location.href = 'admin_dashboard.php';
        }, 2000);
    })
    .catch(error => {
        alert("Something went wrong with the update.");
    });
});
</script>

</body>
</html>