<?php
session_start();

// ==========================================
// DUAL-DATABASE CONNECTION
// ==========================================
$core1_host = "127.0.0.1"; // Localhost ng PC mo
$core2_host = "192.168.100.4"; // IP Address ng Core2
$user = "root";
$pass = "";

// Connect sa local PC (Core 1)
$conn = new mysqli($core1_host, $user, $pass, "microfinance_db");
if ($conn->connect_error)
    die("Core 1 Connection Failed: " . $conn->connect_error);

// Connect sa Laptop (Core 2)
$core2_conn = new mysqli($core2_host, $user, $pass, "core2_db");

if ($core2_conn->connect_error)
    die("Core 2 Connection Failed: " . $core2_conn->connect_error);

// ==========================================
// 🚨 AJAX ENDPOINT: FETCH FULL CHAT LOG 🚨
// ==========================================
if (isset($_GET['fetch_chat_history']) && isset($_GET['session_id'])) {
    $sess = $core2_conn->real_escape_string($_GET['session_id']);

    // Sa Core 2 kukuha para updated ang chat history
    $chat_sql = "SELECT * FROM chat_support_messages WHERE session_id = '$sess' ORDER BY created_at ASC";
    $chat_res = $core2_conn->query($chat_sql);

    if ($chat_res && $chat_res->num_rows > 0) {
        while ($c = $chat_res->fetch_assoc()) {

            // Client Message Bubble (Nakatago ang [SYSTEM])
            if (trim($c['message']) !== '[SYSTEM]') {
                echo '<div style="display: flex; flex-direction: column; align-items: flex-start; margin-bottom: 12px;">';
                echo '<span style="font-size: 10px; color: #94a3b8; margin-bottom: 4px; font-weight: bold;">' . htmlspecialchars($c['name']) . ' - ' . date('M d, g:i A', strtotime($c['created_at'])) . '</span>';
                echo '<div style="background: #334155; color: #e2e8f0; padding: 10px 15px; border-radius: 12px 12px 12px 2px; max-width: 85%; font-size: 13px; line-height: 1.5; border: 1px solid #475569;">' . nl2br(htmlspecialchars($c['message'])) . '</div>';
                echo '</div>';
            }

            // Support Agent Reply Bubble
            if (!empty($c['admin_reply'])) {
                $agent = !empty($c['replied_by']) ? htmlspecialchars($c['replied_by']) : 'Support Agent';
                echo '<div style="display: flex; flex-direction: column; align-items: flex-end; margin-bottom: 12px;">';
                echo '<span style="font-size: 10px; color: #3b82f6; margin-bottom: 4px; font-weight: bold;">' . $agent . ' - ' . date('M d, g:i A', strtotime($c['updated_at'])) . '</span>';
                echo '<div style="background: #1e3a8a; color: #fff; padding: 10px 15px; border-radius: 12px 12px 2px 12px; max-width: 85%; font-size: 13px; line-height: 1.5; border: 1px solid #1d4ed8;">' . nl2br(htmlspecialchars($c['admin_reply'])) . '</div>';
                echo '</div>';
            }
        }
    }
    else {
        echo '<div style="text-align: center; color: #94a3b8; padding: 20px;">No chat history found for this session.</div>';
    }
    exit;

}

// ==========================================
// HANDLE RESOLVE TICKET
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_ticket'])) {
    $session_id = $core2_conn->real_escape_string($_POST['session_id']);
    $ticket_id = $core2_conn->real_escape_string($_POST['ticket_id']);
    $finance_notes = $core2_conn->real_escape_string(trim($_POST['finance_notes']));

    $auto_reply = "🎟️ SYSTEM NOTIFICATION: \nYour ticket ($ticket_id) has been marked as RESOLVED by the Core 1 Department.\n\nFinance Notes: $finance_notes";

    $resolve_sql = "UPDATE chat_support_messages SET is_resolved = 1 WHERE ticket_id = '$ticket_id'";
    $conn->query($resolve_sql);
    $core2_conn->query($resolve_sql);

    // Sa Core 2 tayo kukuha ng user data para sure
    $u_res = $core2_conn->query("SELECT user_id, name, email FROM chat_support_messages WHERE session_id = '$session_id' LIMIT 1");
    $u_data = $u_res->fetch_assoc();
    $u_id = $u_data['user_id'] ?? 0;
    $u_name = $core2_conn->real_escape_string($u_data['name'] ?? 'Client');
    $u_email = $core2_conn->real_escape_string($u_data['email'] ?? '');

    $insert_sys_sql = "INSERT INTO chat_support_messages 
        (user_id, name, email, message, admin_reply, replied_by, status, session_id, created_at, updated_at) 
        VALUES 
        ('$u_id', '$u_name', '$u_email', '[SYSTEM]', '$auto_reply', 'Finance Admin', 'replied', '$session_id', NOW(), NOW())";

    $conn->query($insert_sys_sql);
    $core2_conn->query($insert_sys_sql);

    echo "<script>alert('Ticket $ticket_id resolved! A separate chat bubble has been sent to the client.'); window.location.href='finance_tickets.php';</script>";
    exit;
}

// FETCH ACTIVE TICKETS SA CORE 2
$query = "SELECT * FROM chat_support_messages WHERE is_complaint = 1 AND (is_resolved = 0 OR is_resolved IS NULL) GROUP BY ticket_id ORDER BY priority DESC, created_at ASC";
$tickets = $core2_conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Support Tickets</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Google Sans', sans-serif; }
        body { background-color: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; padding: 30px; width: calc(100% - 260px); }
        .page-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { font-size: 24px; color: #fff; margin-bottom: 5px; }
        .page-header p { color: #94a3b8; font-size: 14px; }
        .btn-header { background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-header:hover { background: #334155; color: #fff; }

        .config-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; margin-bottom: 20px; transition: 0.2s; overflow: hidden; }
        .config-card:hover { border-color: #475569; }
        .card-head { display: flex; align-items: center; gap: 15px; padding: 20px 25px; cursor: pointer; transition: 0.2s; user-select: none; }
        .card-head:hover { background: rgba(255,255,255,0.02); }

        .icon-box { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .icon-gold { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .icon-red { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .icon-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }

        .card-title { flex-grow: 1; }
        .card-title h3 { font-size: 16px; color: #fff; margin: 0 0 3px 0; }
        .card-title span { font-size: 12px; color: #94a3b8; }
        .toggle-icon { font-size: 14px; color: #64748b; transition: transform 0.3s ease; }

        .ticket-body { display: none; padding: 0 25px 25px 25px; border-top: 1px solid #334155; margin-top: -5px; padding-top: 20px; }
        .info-row { font-size: 14px; color: #cbd5e1; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        
        .chat-log-box { background: #0f172a; border: 1px solid #334155; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #fbbf24; }
        .chat-label { display: block; font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .chat-text { color: #e2e8f0; font-size: 14px; line-height: 1.5; font-weight: 500; }

        .input-group { margin-bottom: 20px; }
        .input-label { display: block; font-size: 13px; color: #cbd5e1; margin-bottom: 8px; font-weight: 500; }
        .input-field { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 15px; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s; resize: none; height: 90px; }
        .input-field:focus { border-color: #fbbf24; }

        .card-footer { margin-top: 10px; padding-top: 20px; border-top: 1px solid #334155; text-align: right; }
        .btn-save { background: #10b981; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #059669; }

        .empty-state { text-align: center; padding: 60px; background: #1e293b; border-radius: 12px; border: 1px dashed #475569; color: #94a3b8; }
        .empty-state i { font-size: 48px; color: #475569; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Support Tickets Inbox</h1>
                <p>Manage and resolve escalated issues from Core 2 Support.</p>
            </div>
            <a href="finance_archives.php" class="btn-header">
                <i class="bi bi-archive"></i> View Archives
            </a>
        </div>

        <?php if ($tickets && $tickets->num_rows > 0): ?>
            <?php while ($t = $tickets->fetch_assoc()):
        $iconClass = "icon-blue";
        $iconName = "bi-ticket-detailed";
        if ($t['priority'] == 'HIGH') {
            $iconClass = "icon-red";
            $iconName = "bi-exclamation-triangle";
        }
        elseif ($t['priority'] == 'MEDIUM') {
            $iconClass = "icon-gold";
        }
?>
                <div class="config-card">
                    <div class="card-head" onclick="toggleTicket(this)">
                        <div class="icon-box <?php echo $iconClass; ?>">
                            <i class="bi <?php echo $iconName; ?>"></i>
                        </div>
                        <div class="card-title">
                            <h3>Ticket: <?php echo htmlspecialchars($t['ticket_id']); ?></h3>
                            <span>Priority Level: <strong style="color: <?php echo($t['priority'] == 'HIGH') ? '#f87171' : (($t['priority'] == 'MEDIUM') ? '#fbbf24' : '#10b981'); ?>"><?php echo htmlspecialchars($t['priority']); ?></strong></span>
                        </div>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </div>

                    <div class="ticket-body">
                        <div class="info-row">
                            <div>
                                <i class="bi bi-person-circle" style="color:#64748b; margin-right:5px;"></i> 
                                <strong>Client Profile:</strong> <?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['email']); ?>)
                            </div>
                            
                            <button type="button" onclick="openChatModal('<?php echo htmlspecialchars($t['session_id']); ?>', '<?php echo htmlspecialchars($t['ticket_id']); ?>')" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px;">
                                <i class="bi bi-chat-text"></i> View Full Conversation
                            </button>
                        </div>

                        <div class="chat-log-box">
                            <span class="chat-label" style="color: #fbbf24;"><i class="bi bi-pin-angle"></i> Core 2 Escalation Notes</span>
                            <div class="chat-text"><?php echo nl2br(htmlspecialchars($t['escalation_notes'])); ?></div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($t['ticket_id']); ?>">
                            <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($t['session_id']); ?>">
                            
                            <div class="input-group">
                                <label class="input-label">Resolution Action / Message to Client</label>
                                <textarea name="finance_notes" class="input-field" required placeholder="Detail the fix applied here. This will be sent as a system notification..."></textarea>
                            </div>

                            <div class="card-footer">
                                <button type="submit" name="resolve_ticket" class="btn-save" onclick="return confirm('Mark this ticket as resolved?');">
                                    <i class="bi bi-check2-circle"></i> Resolve & Notify Client
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php
    endwhile; ?>
        <?php
else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3 style="color:#e2e8f0; font-size:18px; margin-bottom:5px;">Queue is Empty</h3>
                <p>No pending tickets from Core 2 support.</p>
            </div>
        <?php
endif; ?>

    </div>

<div id="chatHistoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(3px);">
    <div style="background: #0f172a; width: 550px; max-width: 95%; border-radius: 12px; border: 1px solid #334155; display: flex; flex-direction: column; height: 80vh; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        
        <div style="padding: 20px 25px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; background: #1e293b; border-radius: 12px 12px 0 0; flex-shrink: 0;">
            <h3 style="margin: 0; color: #fff; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-chat-dots-fill" style="color: #3b82f6;"></i>
                <span id="modalTicketTitle">Conversation History</span>
            </h3>
            <button onclick="closeChatModal()" style="background: transparent; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; transition: 0.2s;"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="modalChatBody" style="padding: 25px; overflow-y: auto; flex-grow: 1; background: #0b1120; scroll-behavior: smooth;">
            </div>
        
        <div style="padding: 15px; border-top: 1px solid #334155; background: #1e293b; border-radius: 0 0 12px 12px; text-align: center; flex-shrink: 0;">
            <span style="font-size: 11px; color: #64748b;">End of conversation history</span>
        </div>
    </div>
</div>

    <script>
        // Ticket Accordion Logic
        function toggleTicket(headerElement) {
            const card = headerElement.closest('.config-card');
            const body = card.querySelector('.ticket-body');
            const icon = card.querySelector('.toggle-icon');

            if (body.style.display === "none" || body.style.display === "") {
                body.style.display = "block";
                icon.style.transform = "rotate(180deg)";
                card.style.borderColor = "#fbbf24"; 
            } else {
                body.style.display = "none";
                icon.style.transform = "rotate(0deg)";
                card.style.borderColor = "#334155"; 
            }
        }

        // Modal Logic
function openChatModal(sessionId, ticketId) {
    document.getElementById('chatHistoryModal').style.display = 'flex';
    document.getElementById('modalTicketTitle').innerText = "History - " + ticketId;
    
    // Loading state
    const modalBody = document.getElementById('modalChatBody');
    modalBody.innerHTML = '<div style="text-align: center; color: #94a3b8; font-size: 13px; margin-top: 20px;"><i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Loading...</div>';
    
    fetch('finance_tickets.php?fetch_chat_history=1&session_id=' + encodeURIComponent(sessionId))
        .then(res => res.text())
        .then(html => {
            modalBody.innerHTML = html;
            
            // 🚨 AUTO-SCROLL PABABA PAGKAPOAD NG DATA 🚨
            setTimeout(() => {
                modalBody.scrollTop = modalBody.scrollHeight;
            }, 100); 
        });
}

        function closeChatModal() {
            document.getElementById('chatHistoryModal').style.display = 'none';
        }
    </script>
</body>
</html>