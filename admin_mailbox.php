<?php
session_start();
include("../config/db.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin"){
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? "Admin";
$view = $_GET['view'] ?? 'support'; 

// --- 1. HANDLE ACTIONS (TRASH & STAR) ---
if(isset($_GET['action'])) {
    $msg_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    if($_GET['action'] == 'trash') {
        // Move to trash or hide if already in trash
        $current_folder_query = mysqli_query($conn, "SELECT folder FROM messages WHERE id = '$msg_id'");
        $current_folder = mysqli_fetch_assoc($current_folder_query)['folder'];
        
        $new_folder = ($current_folder == 'trash') ? 'hidden' : 'trash';
        mysqli_query($conn, "UPDATE messages SET folder = '$new_folder' WHERE id = '$msg_id'");
    } elseif($_GET['action'] == 'star') {
        mysqli_query($conn, "UPDATE messages SET is_starred = NOT is_starred WHERE id = '$msg_id'");
    }
    header("Location: admin_mailbox.php?view=$view" . (isset($_GET['thread']) ? "&thread=".$_GET['thread'] : ""));
    exit();
}

// --- 2. HANDLE SENDING REPLIES ---
if(isset($_POST['send_reply'])) {
    $thread_id = mysqli_real_escape_string($conn, $_POST['thread_id']);
    $student_id = (int)$_POST['student_id'];
    $reply_msg = mysqli_real_escape_string($conn, $_POST['reply_content']);

    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE thread_id = '$thread_id' OR id = '$thread_id'");

    $sql = "INSERT INTO messages (thread_id, sender_id, sender_name, recipient_id, sender_role, message, type, folder, is_read) 
            VALUES ('$thread_id', $admin_id, '$admin_name', $student_id, 'admin', '$reply_msg', 'admin_reply', 'inbox', 1)";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: admin_mailbox.php?view=support");
        exit();
    }
}

// --- 3. HANDLE ADMIN GROUP MESSAGES ---
if(isset($_POST['send_group_msg'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['group_msg']);
    mysqli_query($conn, "INSERT INTO messages (sender_id, sender_name, message, type, sender_role, is_read) 
                        VALUES ($admin_id, '$admin_name', '$msg', 'admin_group', 'admin', 1)");
    header("Location: admin_mailbox.php?view=group");
    exit();
}

// --- 4. DATA FETCHING LOGIC (With Folder Filtering) ---
if($view == 'group') {
    $chat_data = mysqli_query($conn, "SELECT * FROM messages WHERE type = 'admin_group' ORDER BY created_at ASC");
} elseif($view == 'starred') {
    $threads_query = "SELECT * FROM messages WHERE is_starred = 1 AND folder != 'hidden' GROUP BY IFNULL(thread_id, id) ORDER BY created_at DESC";
    $threads = mysqli_query($conn, $threads_query);
} elseif($view == 'trash') {
    $threads_query = "SELECT * FROM messages WHERE folder = 'trash' GROUP BY IFNULL(thread_id, id) ORDER BY created_at DESC";
    $threads = mysqli_query($conn, $threads_query);
} else {
    if($view == 'support') {
        $replied_threads_query = "SELECT DISTINCT thread_id FROM messages WHERE sender_role = 'admin' AND thread_id IS NOT NULL";
        $replied_res = mysqli_query($conn, $replied_threads_query);
        $replied_ids = [];
        while($r = mysqli_fetch_assoc($replied_res)) $replied_ids[] = "'".$r['thread_id']."'";
        $exclude_sql = !empty($replied_ids) ? "AND (IFNULL(thread_id, id) NOT IN (".implode(',', $replied_ids)."))" : "";

        $threads_query = "SELECT * FROM messages 
                          WHERE type = 'student_query' AND folder != 'trash' AND folder != 'hidden' $exclude_sql
                          GROUP BY IFNULL(thread_id, id) 
                          ORDER BY created_at DESC";
    } else {
        $threads_query = "SELECT m.* FROM messages m
                          INNER JOIN (SELECT DISTINCT thread_id FROM messages WHERE sender_role = 'admin') r
                          ON IFNULL(m.thread_id, m.id) = r.thread_id
                          WHERE m.type = 'student_query' AND m.folder != 'trash' AND m.folder != 'hidden'
                          GROUP BY IFNULL(m.thread_id, m.id)
                          ORDER BY m.created_at DESC";
    }
    $threads = mysqli_query($conn, $threads_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ICSM | Admin Mailbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --admin-lounge: #0f172a; --glass: rgba(255, 255, 255, 0.95); --danger: #ef4444; --star: #eab308; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: url('../images/loginpage.jpg') no-repeat center center fixed; background-size: cover; height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .mailbox-card { width: 95%; max-width: 1300px; height: 85vh; background: var(--glass); backdrop-filter: blur(20px); border-radius: 32px; display: grid; grid-template-columns: 80px 350px 1fr; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.3); }
        
        .nav-rail { background: rgba(15, 23, 42, 0.05); display: flex; flex-direction: column; align-items: center; padding: 30px 0; border-right: 1px solid rgba(0,0,0,0.05); }
        .nav-item { width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; color: #64748b; text-decoration: none; transition: 0.3s; }
        .nav-item.active { background: var(--primary); color: white; box-shadow: 0 8px 15px rgba(79, 70, 229, 0.3); }

        .list-pane { border-right: 1px solid rgba(0,0,0,0.05); overflow-y: auto; background: rgba(255,255,255,0.3); }
        .pane-title { padding: 25px; font-weight: 800; font-size: 18px; color: #1e293b; border-bottom: 1px solid rgba(0,0,0,0.05); }
        
        /* UPDATED THREAD ITEM FOR ACTIONS */
        .thread-item { padding: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); cursor: pointer; transition: 0.2s; position: relative; display: flex; align-items: center; gap: 12px; }
        .thread-item:hover { background: white; }
        .thread-item.active { background: white; border-left: 5px solid var(--primary); }
        .thread-content { flex: 1; min-width: 0; }
        .thread-name { display: block; font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 4px; }
        .thread-msg { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .action-btns { display: flex; flex-direction: column; gap: 8px; opacity: 0; transition: 0.2s; }
        .thread-item:hover .action-btns { opacity: 1; }
        
        .icon-btn { border: none; background: none; cursor: pointer; font-size: 14px; transition: 0.2s; color: #cbd5e1; }
        .icon-btn.star.active { color: var(--star); opacity: 1; }
        .icon-btn.trash:hover { color: var(--danger); }

        .chat-pane { background: white; display: flex; flex-direction: column; position: relative; }
        .chat-header { padding: 20px 30px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
        .chat-messages { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: #f8fafc; }
        
        .bubble { max-width: 70%; padding: 14px 18px; border-radius: 20px; font-size: 14px; line-height: 1.6; }
        .bubble.me { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 4px; }
        .bubble.them { align-self: flex-start; background: white; color: #1e293b; border-bottom-left-radius: 4px; border: 1px solid #e2e8f0; }
        
        .view-group .chat-header { background: var(--admin-lounge); color: white; }
        .view-group .chat-header h4 { color: white !important; }

        .input-area { padding: 25px 30px; border-top: 1px solid #f1f5f9; display: flex; gap: 15px; background: white; }
        .input-area input { flex: 1; padding: 14px 20px; border-radius: 15px; border: 1px solid #e2e8f0; outline: none; background: #f1f5f9; transition: 0.3s; }
        .send-btn { background: var(--primary); color: white; border: none; width: 50px; height: 50px; border-radius: 15px; cursor: pointer; transition: 0.3s; }
        .send-btn:hover { transform: scale(1.05); }
    </style>
</head>
<body class="<?= $view == 'group' ? 'view-group' : '' ?>">

<div class="mailbox-card">
    <div class="nav-rail">
        <a href="admin_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i></a>
        <a href="?view=support" class="nav-item <?= $view=='support'?'active':'' ?>" title="Pending Queries"><i class="fa-solid fa-comment-dots"></i></a>
        <a href="?view=message" class="nav-item <?= $view=='message'?'active':'' ?>" title="Answered History"><i class="fa-solid fa-envelope-open-text"></i></a>
        <a href="?view=starred" class="nav-item <?= $view=='starred'?'active':'' ?>" title="Starred"><i class="fa-solid fa-star"></i></a>
        <a href="?view=trash" class="nav-item <?= $view=='trash'?'active':'' ?>" title="Trash"><i class="fa-solid fa-trash-can"></i></a>
        <a href="?view=group" class="nav-item <?= $view=='group'?'active':'' ?>" title="Admin Lounge"><i class="fa-solid fa-shield-halved"></i></a>
    </div>

    <div class="list-pane">
        <div class="pane-title">
            <?php 
                if($view == 'group') echo "Admin Staff";
                elseif($view == 'support') echo "Pending Queries";
                elseif($view == 'starred') echo "Starred Messages";
                elseif($view == 'trash') echo "Trash";
                else echo "Resolved History";
            ?>
        </div>
        <?php if($view != 'group'): ?>
            <?php if($threads && mysqli_num_rows($threads) > 0): ?>
                <?php while($t = mysqli_fetch_assoc($threads)): 
                    $link_id = !empty($t['thread_id']) ? $t['thread_id'] : $t['id']; 
                    $isStarred = $t['is_starred'] ?? 0;
                ?>
                    <div class="thread-item <?= (isset($_GET['thread']) && $_GET['thread'] == $link_id) ? 'active' : '' ?>" 
                         onclick="location.href='?view=<?= $view ?>&thread=<?= $link_id ?>'">
                        
                        <div class="action-btns">
                            <a href="?view=<?= $view ?>&action=star&id=<?= $t['id'] ?>" class="icon-btn star <?= $isStarred?'active':'' ?>" onclick="event.stopPropagation()">
                                <i class="fa-<?= $isStarred?'solid':'regular' ?> fa-star"></i>
                            </a>
                            <a href="?view=<?= $view ?>&action=trash&id=<?= $t['id'] ?>" class="icon-btn trash" onclick="event.stopPropagation()">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>

                        <div class="thread-content">
                            <span class="thread-name"><?= htmlspecialchars($t['sender_name']) ?></span>
                            <p class="thread-msg"><?= htmlspecialchars($t['message']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="padding: 20px; font-size: 12px; color: #94a3b8; text-align: center;">No messages here.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="thread-item active">
                <div class="thread-content">
                    <span class="thread-name"># Internal_Lounge</span>
                    <p class="thread-msg">Secure Broadcast Channel</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="chat-pane">
        <div class="chat-header">
            <h4 style="font-weight:800; color:#1e293b;">
                <i class="fa-solid <?= $view == 'group' ? 'fa-lock' : 'fa-user-graduate' ?> mr-2"></i>
                <?= $view == 'group' ? 'Admin Lounge' : ($view == 'message' ? 'Resolved History' : ($view == 'starred' ? 'Starred' : ($view == 'trash' ? 'Trash' : 'Pending Conversation'))) ?>
            </h4>
        </div>

        <div class="chat-messages" id="chatBox">
            <?php 
            if($view == 'group') {
                while($msg = mysqli_fetch_assoc($chat_data)) {
                    $isMe = ($msg['sender_id'] == $admin_id);
                    echo "<div class='bubble ".($isMe?'me':'them')."'>";
                    if(!$isMe) echo "<small style='font-weight:800; font-size:10px; display:block;'>{$msg['sender_name']}</small>";
                    echo htmlspecialchars($msg['message'])."</div>";
                }
            } elseif(isset($_GET['thread'])) {
                $tid = mysqli_real_escape_string($conn, $_GET['thread']);
                $convo = mysqli_query($conn, "SELECT * FROM messages WHERE (thread_id = '$tid' OR id = '$tid') AND folder != 'hidden' ORDER BY created_at ASC");
                $current_student_id = 0;
                while($msg = mysqli_fetch_assoc($convo)) {
                    $isMe = ($msg['sender_role'] == 'admin');
                    echo "<div class='bubble ".($isMe?'me':'them')."'>".htmlspecialchars($msg['message'])."</div>";
                    if($msg['sender_role'] == 'student') $current_student_id = $msg['sender_id'];
                }
            } else {
                echo "<div style='margin:auto; text-align:center; color:#94a3b8;'>Select a thread to respond</div>";
            }
            ?>
        </div>

        <?php if(($view == 'group' || isset($_GET['thread'])) && $view != 'trash'): ?>
        <form class="input-area" method="POST">
            <?php if($view == 'group'): ?>
                <input type="text" name="group_msg" placeholder="Message admins..." required>
                <button name="send_group_msg" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
            <?php else: ?>
                <input type="hidden" name="thread_id" value="<?= htmlspecialchars($_GET['thread']) ?>">
                <input type="hidden" name="student_id" value="<?= $current_student_id ?>">
                <input type="text" name="reply_content" placeholder="Type your reply..." required>
                <button name="send_reply" class="send-btn"><i class="fa-solid fa-reply"></i></button>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
    var chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

</body>
</html>