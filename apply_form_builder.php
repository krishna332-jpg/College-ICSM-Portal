<?php
session_start();
include("../config/db.php");

// 1. Identify Mode (Edit vs. Create)
$opportunity_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if ($opportunity_id) {
    // EDIT MODE: Fetch from Database
    $sql = "SELECT * FROM opportunities WHERE id = '$opportunity_id' LIMIT 1";
    $res = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($res);
    
    if (!$data) exit("Error: Opportunity ID not found in database.");
    
    // Crucial: Keep the JSON exactly as it is in the DB for the JS parser
    $existing_json = (!empty($data['form_fields'])) ? $data['form_fields'] : '[]';
} else {
    // CREATE MODE: Fetch from Session
    if (!isset($_SESSION['temp_opp'])) { 
        header("Location: add_form.php"); 
        exit(); 
    }
    $data = $_SESSION['temp_opp'];
    $existing_json = '[]'; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designer | <?= htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --accent: #10b981; --danger: #ef4444; --slate: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; color: var(--dark); }
        
        .designer-card { width: 100%; max-width: 1250px; background: white; border-radius: 30px; display: flex; overflow: hidden; height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        
        /* Left Side: Preview */
        .preview-pane { flex: 1.2; padding: 50px; overflow-y: auto; border-right: 1px solid #f1f5f9; background: #fff; }
        .preview-pane h1 { font-size: 32px; font-weight: 800; color: var(--dark); line-height: 1.1; }
        .org-badge { display: inline-block; padding: 6px 12px; background: #f1f5f9; border-radius: 8px; color: var(--slate); font-weight: 700; font-size: 13px; margin: 15px 0 25px 0; }
        .preview-img { width: 100%; height: 260px; object-fit: cover; border-radius: 20px; margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .section-label { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 30px; display: block; }
        .content-box { color: #475569; line-height: 1.6; font-size: 15px; margin-top: 8px; }

        /* Right Side: Builder */
        .builder-pane { flex: 1; padding: 50px; background: #f8fafc; overflow-y: auto; display: flex; flex-direction: column; }
        .nav-actions { display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px; }
        .nav-btn { width: 40px; height: 40px; border-radius: 12px; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: var(--slate); text-decoration: none; transition: 0.2s; cursor: pointer; }
        .nav-btn:hover { color: var(--primary); border-color: var(--primary); }

        .field-card { background: white; padding: 16px 20px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .field-card:hover { border-color: #cbd5e1; }
        .btn-del { color: #cbd5e1; cursor: pointer; font-size: 18px; transition: 0.2s; }
        .btn-del:hover { color: var(--danger); }

        .add-box { margin-top: auto; padding-top: 30px; border-top: 2px dashed #e2e8f0; }
        .input-row { display: flex; gap: 10px; margin-top: 15px; }
        input, select { padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none; font-size: 14px; }
        .btn-add { background: var(--dark); color: white; border: none; padding: 0 20px; border-radius: 12px; cursor: pointer; font-weight: 700; }
        
        .btn-launch { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 18px; font-weight: 800; font-size: 16px; margin-top: 30px; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4); transition: 0.2s; }
        .btn-launch:hover { transform: translateY(-2px); opacity: 0.9; }

        /* Modals */
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); display: none; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
        .modal { background: white; padding: 40px; border-radius: 24px; text-align: center; max-width: 400px; width: 90%; }
        .btn-group { display: flex; gap: 12px; margin-top: 25px; }
        .m-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; }
        
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div id="statusOverlay" class="overlay">
    <div class="modal">
        <div id="loader">
            <div class="spinner"></div>
            <h3 id="statusTitle">Saving Changes...</h3>
        </div>
        <div id="successContent" style="display:none;">
            <i class="fa-solid fa-circle-check" style="font-size: 48px; color: var(--accent); margin-bottom: 15px;"></i>
            <h3>Successfully Updated!</h3>
        </div>
        <div id="errorContent" style="display:none;">
            <h3 style="color:var(--danger)">Update Failed</h3>
            <p id="errorMsg" style="margin:10px 0; color:var(--slate); font-size: 14px;"></p>
            <button class="m-btn" style="background:#f1f5f9" onclick="closeStatus()">Try Again</button>
        </div>
    </div>
</div>

<div class="designer-card">
    <div class="preview-pane">
        <h1><?= htmlspecialchars($data['title']); ?></h1>
        <div class="org-badge"><?= htmlspecialchars($data['organization']); ?></div>
        
        <?php 
            $img = $data['image'] ?? $data['image_path'] ?? null;
            if($img): 
        ?>
            <img src="../<?= $img ?>" class="preview-img">
        <?php endif; ?>

        <span class="section-label">Description</span>
        <div class="content-box"><?= nl2br(htmlspecialchars($data['description'])); ?></div>
        
        <span class="section-label">Eligibility</span>
        <div class="content-box"><?= nl2br(htmlspecialchars($data['eligibility'])); ?></div>
        
        <div style="margin-top:40px; padding:20px; background:#f8fafc; border-radius:15px; border:1px solid #e2e8f0;">
            <span class="section-label" style="margin-top:0">Closing Date</span>
            <p style="font-weight:800; font-size:18px; margin-top:5px;"><?= date('D, d M Y', strtotime($data['deadline'])); ?></p>
        </div>
    </div>

    <div class="builder-pane">
        <div class="nav-actions">
            <a href="admin_dashboard.php" class="nav-btn" title="Dashboard"><i class="fa-solid fa-house"></i></a>
            <a href="../auth/logout.php" class="nav-btn" style="color:var(--danger)"><i class="fa-solid fa-power-off"></i></a>
        </div>

        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 5px;">Form Designer</h2>
        <p style="color:var(--slate); font-size: 14px; margin-bottom: 30px;">Add or remove fields for the application form.</p>
        
        <div id="field-list">
            </div>

        <div class="add-box">
            <span class="section-label">New Question</span>
            <div class="input-row">
                <input type="text" id="fieldName" style="flex:2;" placeholder="Label (e.g. GitHub URL)">
                <select id="fieldType" style="flex:1;">
                    <option value="text">Short Text</option>
                    <option value="textarea">Long Text</option>
                    <option value="file">File Upload</option>
                </select>
                <button onclick="addField()" class="btn-add"><i class="fa-solid fa-plus"></i></button>
            </div>
        </div>
        
        <button onclick="saveAll()" class="btn-launch">
            <i class="fa-solid fa-cloud-arrow-up"></i> <?= $opportunity_id ? 'Update Application Form' : 'Launch Opportunity'; ?>
        </button>
    </div>
</div>

<script>
// --- DATA HANDLING ---
// Load data from PHP safely
let rawData = <?php echo json_encode($existing_json); ?>;
let fields = [];

try {
    // Failsafe: Handle strings, objects, or nested JSON
    if (typeof rawData === 'string') {
        fields = JSON.parse(rawData);
    } else {
        fields = rawData;
    }
} catch (e) {
    console.error("Data Parse Error, initializing empty array.");
    fields = [];
}

// Initial Render
window.onload = renderFields;

function renderFields() {
    const list = document.getElementById('field-list');
    
    // 1. Static Mandatory Fields
    let html = `
        <div class="field-card" style="opacity:0.6; background:#f1f5f9; border-style: dashed;">
            <span><i class="fa-solid fa-lock" style="margin-right:8px;"></i> Full Name</span>
            <small style="font-weight:700; color:var(--slate)">REQUIRED</small>
        </div>
        <div class="field-card" style="opacity:0.6; background:#f1f5f9; border-style: dashed;">
            <span><i class="fa-solid fa-lock" style="margin-right:8px;"></i> Register Number</span>
            <small style="font-weight:700; color:var(--slate)">REQUIRED</small>
        </div>
    `;
    
    // 2. Dynamic Custom Fields
    fields.forEach((f, i) => {
        html += `
        <div class="field-card">
            <div>
                <strong style="display:block; font-size:14px;">${f.name}</strong>
                <small style="color:var(--primary); font-weight:700; text-transform:uppercase; font-size:10px;">${f.type}</small>
            </div>
            <i class="fa-solid fa-trash-can btn-del" onclick="removeField(${i})"></i>
        </div>`;
    });
    
    list.innerHTML = html;
}

function addField() {
    const nameInput = document.getElementById('fieldName');
    const typeInput = document.getElementById('fieldType');
    
    if(nameInput.value.trim() !== "") {
        fields.push({ name: nameInput.value.trim(), type: typeInput.value });
        nameInput.value = "";
        renderFields();
    }
}

function removeField(index) {
    fields.splice(index, 1);
    renderFields();
}

function closeStatus() { document.getElementById('statusOverlay').style.display = 'none'; }

function saveAll() {
    const overlay = document.getElementById('statusOverlay');
    const loader = document.getElementById('loader');
    const success = document.getElementById('successContent');
    const error = document.getElementById('errorContent');
    
    overlay.style.display = 'flex';
    loader.style.display = 'block';
    success.style.display = 'none';
    error.style.display = 'none';

    let formData = new FormData();
    formData.append('final_fields', JSON.stringify(fields));
    
    // Only append ID if we are editing
    <?php if($opportunity_id): ?>
    formData.append('update_id', '<?= $opportunity_id ?>');
    <?php endif; ?>

    fetch('final_save.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(result => {
        if(result.trim() === "success") {
            loader.style.display = 'none';
            success.style.display = 'block';
            setTimeout(() => { window.location.href = 'admin_dashboard.php'; }, 1500);
        } else {
            loader.style.display = 'none';
            error.style.display = 'block';
            document.getElementById('errorMsg').innerText = result;
        }
    })
    .catch(err => {
        loader.style.display = 'none';
        error.style.display = 'block';
        document.getElementById('errorMsg').innerText = "Network Error: Could not connect to server.";
    });
}
</script>
</body>
</html>