<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['student_name'] ?? "Student";
$view = $_GET['view'] ?? 'inbox';
$thread_id = $_GET['thread'] ?? null; 

// --- HANDLE ACTIONS (TRASH, STAR, & REPLY) ---
if(isset($_GET['action'])) {
    $msg_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    if($_GET['action'] == 'trash') {
        // If the user is already in the 'trash' view and clicks delete, mark as 'hidden'
        if($view == 'trash') {
            mysqli_query($conn, "UPDATE messages SET folder = 'hidden' WHERE id = '$msg_id' AND (sender_id = '$user_id' OR recipient_id = '$user_id')");
        } else {
            // Otherwise, just move it to the trash folder
            mysqli_query($conn, "UPDATE messages SET folder = 'trash' WHERE id = '$msg_id' AND (sender_id = '$user_id' OR recipient_id = '$user_id')");
        }
    } elseif($_GET['action'] == 'star') {
        mysqli_query($conn, "UPDATE messages SET is_starred = NOT is_starred WHERE id = '$msg_id' AND (sender_id = '$user_id' OR recipient_id = '$user_id')");
    }
    header("Location: ?view=$view");
    exit();
}

// Handle Direct Reply from within a thread
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_msg'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    $t_id = mysqli_real_escape_string($conn, $_POST['thread_id']);
    $subj = mysqli_real_escape_string($conn, $_POST['subject']);
    
    $sql = "INSERT INTO messages (sender_id, sender_name, sender_role, recipient_id, subject, message, type, thread_id) 
            VALUES ('$user_id', '$student_name', 'student', '1', '$subj', '$msg', 'student_query', '$t_id')";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: ?view=$view&thread=$t_id");
        exit();
    }
}

// --- FETCH MESSAGES (Exclude 'hidden' from all views) ---
if ($view == 'sent') {
    $query = "SELECT * FROM messages WHERE sender_id = '$user_id' AND type = 'student_query' AND folder != 'trash' AND folder != 'hidden'";
} elseif ($view == 'starred') {
    $query = "SELECT * FROM messages WHERE (sender_id = '$user_id' OR recipient_id = '$user_id') AND is_starred = 1 AND folder != 'trash' AND folder != 'hidden'";
} elseif ($view == 'inbox') {
    $query = "SELECT * FROM messages WHERE recipient_id = '$user_id' AND type = 'admin_reply' AND folder != 'trash' AND folder != 'hidden'";
} else {
    // This handles the 'trash' view specifically or any other custom folder, excluding 'hidden'
    $query = "SELECT * FROM messages WHERE (recipient_id = '$user_id' OR sender_id = '$user_id') AND folder = '$view' AND folder != 'hidden'";
}

$query .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ICSM Portal | Student Mailbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3b82f6; --primary-bg: #eff6ff; --bg-glass: rgba(255, 255, 255, 0.95); --text-main: #1e293b; --text-muted: #64748b; --danger: #ef4444; --star: #eab308; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background: url('../images/loginpage.jpg') no-repeat center center fixed; 
            background-size: cover; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
        }

        .mailbox-app { 
            width: 95%; 
            max-width: 1200px; 
            height: 85vh; 
            background: var(--bg-glass); 
            border-radius: 30px; 
            display: flex; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        
        .sidebar { width: 260px; background: rgba(255, 255, 255, 0.5); border-right: 1px solid rgba(0,0,0,0.03); padding: 40px 20px; display: flex; flex-direction: column; }
        .compose-btn { background: var(--primary); color: white; padding: 14px; border-radius: 14px; text-align: center; text-decoration: none; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 35px; transition: 0.3s; }
        .compose-btn:hover { background: #2563eb; transform: translateY(-2px); }
        .nav-link { display: flex; align-items: center; gap: 18px; padding: 14px 20px; text-decoration: none; color: var(--text-muted); font-weight: 600; border-radius: 12px; margin-bottom: 8px; font-size: 15px; transition: 0.2s; }
        .nav-link.active { background: var(--primary-bg); color: var(--primary); }
        .nav-link:hover:not(.active) { background: rgba(0,0,0,0.02); }

        .mail-area { flex-grow: 1; display: flex; flex-direction: column; background: white; min-width: 0; }
        .mail-header { padding: 30px 50px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        
        .msg-card { padding: 18px 40px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; cursor: pointer; transition: 0.2s; position: relative; min-width: 0; }
        .msg-card:hover { background: #f8fafc; }
        
        .icon-btn { border: none; background: none; cursor: pointer; font-size: 16px; transition: 0.2s; padding: 5px; }
        .icon-btn.star { color: #cbd5e1; margin-right: 15px; flex-shrink: 0; }
        .icon-btn.star.active { color: var(--star); }
        .icon-btn.trash { color: #cbd5e1; }
        .icon-btn.trash:hover { color: var(--danger); }

        .msg-sender { font-weight: 700; width: 150px; min-width: 150px; font-size: 14px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 20px; flex-shrink: 0; }
        .msg-body { flex: 1; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-muted); padding-right: 30px; min-width: 0; }
        .msg-body strong { color: var(--text-main); }

        .action-btns { display: flex; gap: 15px; opacity: 0; transition: 0.2s; width: 50px; justify-content: center; flex-shrink: 0; }
        .msg-card:hover .action-btns { opacity: 1; }
        .msg-date { font-size: 12px; color: #94a3b8; width: 80px; text-align: right; flex-shrink: 0; }

        .thread-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: #f8fafc; }
        .thread-container { padding: 30px 50px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 15px; }
        .chat-bubble { max-width: 80%; padding: 15px; border-radius: 18px; font-size: 14px; line-height: 1.5; }
        .chat-bubble.admin { align-self: flex-start; background: white; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px; }
        .chat-bubble.student { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }

        .reply-bar { padding: 20px 50px; background: white; border-top: 1px solid #f1f5f9; display: flex; gap: 15px; align-items: center; }
        .reply-input { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 12px; outline: none; font-size: 14px; }
        .reply-send { background: var(--primary); color: white; border: none; width: 45px; height: 45px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.2s; }
        .reply-send:hover { background: #2563eb; transform: scale(1.05); }

        #composeModal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(5px); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; width: 600px; border-radius: 24px; padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 11px; font-weight: 800; color: var(--primary); margin-bottom: 8px; text-transform: uppercase; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #f1f5f9; background: #f8fafc; border-radius: 10px; outline: none; }
        .send-btn { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 700; cursor: pointer; float: right; }
    </style>
</head>
<body>

<div class="mailbox-app">
    <div class="sidebar">
        <a href="#" class="compose-btn" onclick="openModal()"><i class="fa-solid fa-plus"></i> Compose</a>
        <nav class="nav-menu">
            <a href="?view=inbox" class="nav-link <?= $view=='inbox'?'active':'' ?>"><i class="fa-solid fa-inbox"></i> Inbox</a>
            <a href="?view=starred" class="nav-link <?= $view=='starred'?'active':'' ?>"><i class="fa-solid fa-star"></i> Starred</a>
            <a href="?view=sent" class="nav-link <?= $view=='sent'?'active':'' ?>"><i class="fa-solid fa-paper-plane"></i> Sent</a>
            <a href="?view=trash" class="nav-link <?= $view=='trash'?'active':'' ?>"><i class="fa-solid fa-trash-can"></i> Trash</a>
        </nav>
        <a href="student_dashboard.php" style="margin-top:auto; text-decoration:none; color:var(--text-muted); font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="mail-area">
        <div class="mail-header">
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a;">
                <?php if($thread_id): ?>
                    <a href="?view=<?= $view ?>" style="text-decoration:none; color:inherit; margin-right:15px;"><i class="fa-solid fa-arrow-left"></i></a> Conversation
                <?php else: ?>
                    <?= ucfirst($view) ?>
                <?php endif; ?>
            </h1>
        </div>

        <div style="overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column;">
            <?php if($thread_id): ?>
                <div class="thread-wrapper">
                    <div class="thread-container">
                        <?php 
                        $tid = mysqli_real_escape_string($conn, $thread_id);
                        $convo = mysqli_query($conn, "SELECT * FROM messages WHERE (thread_id = '$tid' OR id = '$tid') AND folder != 'hidden' ORDER BY created_at ASC");
                        $subject = ""; 
                        while($m = mysqli_fetch_assoc($convo)): 
                            $is_admin = ($m['sender_role'] == 'admin');
                            if(empty($subject)) $subject = $m['subject'];
                        ?>
                            <div class="chat-bubble <?= $is_admin ? 'admin' : 'student' ?>">
                                <small style="display:block; font-weight:800; font-size:10px; margin-bottom:5px; opacity:0.7;">
                                    <?= $is_admin ? 'ADMIN' : 'YOU' ?> • <?= date('H:i', strtotime($m['created_at'])) ?>
                                </small>
                                <?= htmlspecialchars($m['message'] ?? '') ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <form class="reply-bar" method="POST">
                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                        <input type="hidden" name="subject" value="Re: <?= $subject ?>">
                        <input type="text" name="message" class="reply-input" placeholder="Type your reply here..." required>
                        <button type="submit" name="reply_msg" class="reply-send">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $link_id = !empty($row['thread_id']) ? $row['thread_id'] : $row['id'];
                        $isStarred = $row['is_starred'] ?? 0;
                    ?>
                        <div class="msg-card" onclick="location.href='?view=<?= $view ?>&thread=<?= $link_id ?>'">
                            <a href="?view=<?= $view ?>&action=star&id=<?= $row['id'] ?>" class="icon-btn star <?= $isStarred?'active':'' ?>" onclick="event.stopPropagation()">
                                <i class="fa-<?= $isStarred?'solid':'regular' ?> fa-star"></i>
                            </a>
                            <span class="msg-sender"><?= htmlspecialchars($row['sender_name'] ?? '') ?></span>
                            <div class="msg-body">
                                <strong><?= htmlspecialchars($row['subject'] ?? '') ?></strong> - <?= htmlspecialchars($row['message'] ?? '') ?>
                            </div>
                            <div class="action-btns">
                                <a href="?view=<?= $view ?>&action=trash&id=<?= $row['id'] ?>" class="icon-btn trash" onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                            <span class="msg-date"><?= date('M d', strtotime($row['created_at'])) ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:100px; color:#cbd5e1;">
                        <i class="fa-solid fa-envelope-open" style="font-size:50px; margin-bottom:20px;"></i>
                        <p>No messages found in <?= $view ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="composeModal">
    <div class="modal-content">
        <h2 style="margin-bottom: 20px; font-weight: 800;">New Message</h2>
        <form action="send_process.php" method="POST">
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="What is this regarding?" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="5" placeholder="Write your query here..." required></textarea>
            </div>
            <div style="overflow: hidden; margin-top: 10px;">
                <button type="button" onclick="closeModal()" style="background:none; border:none; color:#64748b; cursor:pointer; font-weight: 600;">Cancel</button>
                <button type="submit" class="send-btn">Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('composeModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('composeModal').style.display = 'none'; }
</script>
</body>
</html>