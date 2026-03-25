<?php
require_once __DIR__ . '/include/config.php'; // CORE1 DB 

// CORE2 CONNECTION (STAFF SERVER)
$core2_host = "192.168.100.4"; //palitan ng ip address ng core 2 oki? 
$core2_user = "root";
$core2_pass = "";
$core2_dbname = "core2_db";

$core2_conn = new mysqli($core2_host, $core2_user, $core2_pass, $core2_dbname);

if ($core2_conn->connect_error) {
    die("Core2 DB Connection failed: " . $core2_conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_id = intval($user_id);

$session_id = session_id();

$name = $_SESSION['fullname'] ?? 'Client';
$email = $_SESSION['user_email'] ?? 'client@example.com';


// =======================================================
// END CONVERSATION
// =======================================================

if (isset($_POST['end_chat'])) {

    $sql = "UPDATE chat_support_messages 
            SET client_archived = 1 
            WHERE session_id = ? OR user_id = ?";

    $stmt1 = $conn->prepare($sql);
    $stmt1->bind_param("si", $session_id, $user_id);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $core2_conn->prepare($sql);
    $stmt2->bind_param("si", $session_id, $user_id);
    $stmt2->execute();
    $stmt2->close();

    $_SESSION['chat_messages_sent'] = 0;

    $redirect = strtok($_SERVER['REQUEST_URI'], '?') . "?chat=open";

    echo "<script>window.location.href='{$redirect}'</script>";
    exit();
}


// =======================================================
// AJAX FETCH CHAT
// =======================================================

if (isset($_GET['ajax_fetch_chat'])) {

    $sql = "SELECT * 
            FROM chat_support_messages
            WHERE (session_id=? OR user_id=?)
            AND client_archived=0
            ORDER BY created_at ASC";

    $stmt = $core2_conn->prepare($sql);
    $stmt->bind_param("si", $session_id, $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    echo '<div class="chat-bubble bubble-admin">
    <div class="time">System</div>
    <div class="msg-content">Hello ' . htmlspecialchars(explode(' ', $name)[0]) . '! How can we help you?</div>
    </div>';

    while ($msg = $result->fetch_assoc()) {

        if (!empty($msg['message']) && $msg['message'] != '[SYSTEM]') {

            echo '<div class="chat-bubble bubble-you">';
            echo '<div class="time">' . date('g:i A', strtotime($msg['created_at'])) . '</div>';
            echo '<div class="msg-content">' . nl2br(htmlspecialchars($msg['message'])) . '</div>';
            echo '</div>';

        }

        if (!empty($msg['admin_reply'])) {

            $agent = $msg['replied_by'] ?? "Support";

            echo '<div class="chat-bubble bubble-admin">';
            echo '<div class="time">' . $agent . ' - ' . date('g:i A', strtotime($msg['updated_at'])) . '</div>';
            echo '<div class="msg-content">' . nl2br(htmlspecialchars($msg['admin_reply'])) . '</div>';
            echo '</div>';

        }

    }

    $stmt->close();
    exit();

}



// =======================================================
// MESSAGE LIMIT
// =======================================================

if (!isset($_SESSION['chat_messages_sent'])) {
    $_SESSION['chat_messages_sent'] = 0;
}

$limit = 15;

$has_reached_limit = $_SESSION['chat_messages_sent'] >= $limit;



// =======================================================
// CLIENT SEND MESSAGE
// =======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {

    $redirect = strtok($_SERVER['REQUEST_URI'], '?') . "?chat=open";

    if (!$has_reached_limit) {

        $message = trim($_POST['message']);

        if ($message != "") {

            $sql = "INSERT INTO chat_support_messages
            (user_id,name,email,message,created_at,status,session_id,client_archived)
            VALUES (?,?,?,?,NOW(),'pending',?,0)";

            // CORE1 SAVE
            $stmt1 = $conn->prepare($sql);
            $stmt1->bind_param("issss", $user_id, $name, $email, $message, $session_id);
            $ok1 = $stmt1->execute();
            $stmt1->close();

            // CORE2 SAVE
            $stmt2 = $core2_conn->prepare($sql);
            $stmt2->bind_param("issss", $user_id, $name, $email, $message, $session_id);
            $ok2 = $stmt2->execute();
            $stmt2->close();

            if ($ok1 && $ok2) {
                $_SESSION['chat_messages_sent']++;
            }

        }

    }

    echo "<script>window.location.href='{$redirect}'</script>";
    exit();

}



// =======================================================
// COUNT ADMIN REPLIES
// =======================================================

$admin_reply_count = 0;

$res = $core2_conn->query("SELECT id 
FROM chat_support_messages
WHERE (session_id='$session_id' OR user_id=$user_id)
AND admin_reply IS NOT NULL
AND admin_reply!=''
AND client_archived=0");

if ($res) {
    $admin_reply_count = $res->num_rows;
}


$isOpen = isset($_GET['chat']) && $_GET['chat'] == "open";

?>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/chat_support.css">

<style>
    /* CSS for Client Quick Replies */
    .client-quick-replies { 
        display: flex; 
        gap: 6px; 
        overflow-x: auto; 
        padding-bottom: 8px; 
        margin-bottom: 10px;
    }
    .client-quick-replies::-webkit-scrollbar { height: 3px; }
    .client-quick-replies::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    
    .client-badge-reply { 
        background: #f3f4f6; 
        border: 1px solid #d1d5db; 
        color: #4b5563; 
        padding: 5px 12px; 
        border-radius: 15px; 
        font-size: 11px; 
        cursor: pointer; 
        white-space: nowrap; 
        transition: 0.2s; 
    }
    .client-badge-reply:hover { 
        background: #10b981; /* Matches your theme's brand green */
        color: white; 
        border-color: #10b981; 
    }
</style>

<div class="chat-support-btn">
    <button type="button" onclick="toggleChatSupport()" aria-label="Open chat support">
        <i class="fas fa-comment-dots"></i>
        <span class="chat-badge" id="chatBadge" style="display: none;"></span>
    </button>
</div>

<div class="chat-support <?php echo $isOpen ? 'active' : ''; ?>" id="chatSupport">
    
    <div class="chat-support-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="chat-header-info">
            <h2><i class="fas fa-headset" style="color:#10b981;"></i> Live Support</h2>
        </div>
        
        <div style="display: flex; gap: 8px; align-items: center;">
            <form method="post" style="margin:0;">
                <button type="submit" name="end_chat" title="End Conversation" onclick="return confirm('Are you sure you want to end this conversation? Your chat will be cleared.');" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight:bold; transition: 0.2s;">
                    <i class="fas fa-power-off"></i> End Chat
                </button>
            </form>
            <button type="button" class="close-chat" onclick="toggleChatSupport()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 16px;"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <?php if ($has_reached_limit): ?>
        <div class="limit-warning"><i class="fas fa-exclamation-circle"></i> Message limit reached for this session.</div>
    <?php
endif; ?>

    <div class="chat-history" id="chatHistory"></div>

    <div class="chat-input-area">
        
        <div class="client-quick-replies">
            <button type="button" class="client-badge-reply" onclick="insertClientReply('I have a question about my loan balance.')" <?php echo $has_reached_limit ? 'disabled' : ''; ?>>Loan Balance</button>
            <button type="button" class="client-badge-reply" onclick="insertClientReply('Why is my payment not reflecting?')" <?php echo $has_reached_limit ? 'disabled' : ''; ?>>Missing Payment</button>
            <button type="button" class="client-badge-reply" onclick="insertClientReply('I want to restructure my loan.')" <?php echo $has_reached_limit ? 'disabled' : ''; ?>>Restructure Request</button>
            <button type="button" class="client-badge-reply" onclick="insertClientReply('When will my loan be disbursed?')" <?php echo $has_reached_limit ? 'disabled' : ''; ?>>Disbursement Status</button>
        </div>

        <form id="chatForm" class="chat-form" method="post" action="">
            <textarea id="message" name="message" placeholder="Type your message..." required rows="1" <?php echo $has_reached_limit ? 'disabled' : ''; ?>></textarea>
            <button type="submit" class="btn-send" id="submitBtn" <?php echo $has_reached_limit ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const totalAdminReplies = <?php echo $admin_reply_count; ?>;
    const currentUserId = <?php echo $user_id; ?>;
    const storageKey = 'read_replies_' + currentUserId;
    const isOpenOnLoad = <?php echo $isOpen ? 'true' : 'false'; ?>;

    let readReplies = parseInt(localStorage.getItem(storageKey)) || 0;
    let unreadCount = totalAdminReplies - readReplies;
    const badge = document.getElementById('chatBadge');

    if (unreadCount > 0 && !isOpenOnLoad) {
        badge.textContent = unreadCount;
        badge.style.display = 'block';
    } else if (isOpenOnLoad) {
        localStorage.setItem(storageKey, totalAdminReplies);
    }

    function fetchClientMessages() {
        const chatSupport = document.getElementById('chatSupport');
        if (!chatSupport || !chatSupport.classList.contains('active')) return;

        let url = new URL(window.location.href);
        url.searchParams.set('ajax_fetch_chat', '1');

        fetch(url)
            .then(res => res.text())
            .then(html => {
                const historyBox = document.getElementById('chatHistory');
                if(!historyBox) return;
                
                const isAtBottom = Math.abs((historyBox.scrollHeight - historyBox.scrollTop) - historyBox.clientHeight) < 10;
                historyBox.innerHTML = html;
                if (isAtBottom) historyBox.scrollTop = historyBox.scrollHeight;
            });
    }

    fetchClientMessages();
    setInterval(fetchClientMessages, 2000); 

    function toggleChatSupport() {
        const chat = document.getElementById('chatSupport');
        chat.classList.toggle('active');
        
        if(window.history.replaceState) {
            let cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

        if (chat.classList.contains('active')) {
            fetchClientMessages();
            document.getElementById('message').focus();
            localStorage.setItem(storageKey, totalAdminReplies);
            if (badge) badge.style.display = 'none';
        }
    }

    // FUNCTION PARA SA CLIENT QUICK REPLIES
    function insertClientReply(text) {
        const textarea = document.getElementById('message');
        if(textarea && !textarea.disabled) {
            textarea.value = text;
            textarea.focus();
        }
    }

    const textarea = document.getElementById('message');
    if(textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); 
                if (this.value.trim().length > 0) {
                    document.getElementById('submitBtn').disabled = true;
                    this.closest('form').submit();
                }
            }
        });
    }
</script>   