<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Fetch admin data
$stmt = $conn->prepare("SELECT * FROM ipark_admins WHERE id = ?");
$admin = null;
$unreadMessages = 0;
$conversations = [];
$selectedUser = null;
$messages = [];

if ($stmt) {
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    // Fetch unread message count
    $result = $conn->query("
        SELECT COUNT(*) as unread_count 
        FROM ipark_messages 
        WHERE is_from_user = TRUE AND is_read = FALSE
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $unreadMessages = $row['unread_count'];
    }

    // Fetch all unique conversations (users who have sent/received messages)
    $result = $conn->query("
        SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.profile_picture,
               (SELECT COUNT(*) FROM ipark_messages m2 
                WHERE m2.user_id = u.id AND m2.is_from_user = FALSE AND m2.is_read = FALSE) as unread_count,
               (SELECT MAX(created_at) FROM ipark_messages m3 WHERE m3.user_id = u.id) as last_message_time
        FROM ipark_messages m
        JOIN ipark_users u ON m.user_id = u.id
        ORDER BY last_message_time DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
    }

    // Get selected user's messages if a user is selected
    $selectedUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

    if ($selectedUserId) {
        // Fetch user details
        $stmt = $conn->prepare("SELECT * FROM ipark_users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $selectedUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $selectedUser = $result->fetch_assoc();

            if ($selectedUser) {
                // Fetch messages for this user
                $result = $conn->query("
                    SELECT m.*, 
                           CASE 
                               WHEN m.is_from_user THEN u.first_name 
                               ELSE a.username 
                           END as sender_name,
                           CASE 
                               WHEN m.is_from_user THEN u.profile_picture 
                               ELSE NULL 
                           END as sender_avatar
                    FROM ipark_messages m
                    LEFT JOIN ipark_users u ON m.user_id = u.id
                    LEFT JOIN ipark_admins a ON m.admin_id = a.id
                    WHERE m.user_id = {$selectedUserId}
                    ORDER BY m.created_at ASC
                ");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $messages[] = $row;
                    }
                }

                // Mark unread messages as read
                $stmt = $conn->prepare("
                    UPDATE ipark_messages 
                    SET is_read = TRUE 
                    WHERE user_id = ? AND is_from_user = TRUE AND is_read = FALSE
                ");
                if ($stmt) {
                    $stmt->bind_param("i", $selectedUserId);
                    $stmt->execute();
                }
            }
        }
    }
} else {
    $_SESSION['error'] = 'Error fetching messages';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - IPark Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/styles.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f1f5f9;
            --accent: #22d3ee;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--dark) 0%, var(--gray-800) 100%);
            color: var(--light);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header {
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0 1rem;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .nav-link:hover {
            color: var(--light);
            transform: translateX(4px);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            width: 100%;
        }

        .nav-link.active {
            color: var(--light);
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-icon {
            font-size: 1.25rem;
            opacity: 0.8;
        }

        .logout-section {
            position: absolute;
            bottom: 2rem;
            left: 1rem;
            right: 1rem;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-link:hover {
            color: var(--danger);
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            max-width: calc(100vw - 280px);
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .header p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* Messages Container Styles */
        .messages-container {
            display: flex;
            height: calc(100vh - 180px);
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .conversations-list {
            width: 320px;
            border-right: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .conversations-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .conversations-search {
            margin-top: 1rem;
            position: relative;
        }

        .conversations-search input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .conversations-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .conversations-search::before {
            content: '🔍';
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .conversations {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .conversation-item:hover {
            background: var(--gray-50);
        }

        .conversation-item.active {
            background: var(--primary);
            color: var(--light);
        }

        .conversation-item.active .conversation-name,
        .conversation-item.active .conversation-email {
            color: var(--light);
        }

        .conversation-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-700);
            flex-shrink: 0;
        }

        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-email {
            font-size: 0.875rem;
            color: var(--gray-500);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .unread-badge {
            background: var(--primary);
            color: var(--light);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chat-header-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-700);
        }

        .chat-header-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-header-info {
            flex: 1;
        }

        .chat-header-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .chat-header-email {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 1rem;
            max-width: 80%;
        }

        .message.admin-message {
            margin-left: auto;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--gray-700);
            flex-shrink: 0;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-content {
            background: var(--gray-100);
            padding: 1rem;
            border-radius: var(--border-radius);
            position: relative;
        }

        .admin-message .message-content {
            background: var(--primary);
            color: var(--light);
        }

        .message-sender {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: var(--gray-700);
        }

        .admin-message .message-sender {
            color: var(--light);
            opacity: 0.9;
        }

        .message-text {
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
            text-align: right;
        }

        .admin-message .message-time {
            color: var(--light);
            opacity: 0.8;
        }

        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .message-form {
            display: flex;
            gap: 1rem;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            resize: none;
            min-height: 60px;
            max-height: 120px;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .send-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--light);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            align-self: flex-end;
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .send-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            color: var(--gray-500);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        /* Loading Indicator */
        .loading-indicator {
            display: none;
            text-align: center;
            padding: 1rem;
            color: var(--gray-500);
        }

        .loading-indicator.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
                max-width: 100vw;
            }

            .messages-container {
                margin: 0;
                height: calc(100vh - 140px);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .messages-container {
                flex-direction: column;
            }

            .conversations-list {
                width: 100%;
                height: 300px;
                border-right: none;
                border-bottom: 1px solid var(--gray-200);
            }

            .chat-container {
                height: calc(100vh - 440px);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="IPark Logo" style="max-width: 80px; display: block; margin: 0 auto 1rem auto;">
                <h1>
                    IPark Admin
                </h1>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <span class="nav-icon">📊</span>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="parking_slots.php" class="nav-link">
                            <span class="nav-icon">🚗</span>
                            Parking Slots
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reservations.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link active">
                            <span class="nav-icon">💬</span>
                            Messages
                            <?php if($unreadMessages > 0): ?>
                                <span class="nav-badge"><?php echo $unreadMessages; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <span class="nav-icon">👥</span>
                            User Management
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="logout-section">
                <a href="../logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Messages</h1>
                <p>Manage your conversations with users</p>
            </div>

            <div class="messages-container">
                <!-- Conversations List -->
                <div class="conversations-list">
                    <div class="conversations-header">
                        <h2>
                            <span style="font-size: 1.5rem;">💬</span>
                            Messages
                        </h2>
                        <div class="conversations-search">
                            <input type="text" id="searchInput" placeholder="Search users...">
                        </div>
                    </div>
                    <div class="conversations" id="conversationsList">
                        <?php if(empty($conversations)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💬</div>
                                <h3>No Conversations</h3>
                                <p>No users have sent messages yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($conversations as $conversation): ?>
                                <a href="?user_id=<?php echo $conversation['id']; ?>" 
                                   class="conversation-item <?php echo $selectedUserId == $conversation['id'] ? 'active' : ''; ?>">
                                    <div class="conversation-avatar">
                                        <?php if($conversation['profile_picture']): ?>
                                            <img src="<?php echo htmlspecialchars($conversation['profile_picture']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($conversation['first_name'], 0, 1) . substr($conversation['last_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-info">
                                        <div class="conversation-name">
                                            <?php echo htmlspecialchars($conversation['first_name'] . ' ' . $conversation['last_name']); ?>
                                        </div>
                                        <div class="conversation-email">
                                            <?php echo htmlspecialchars($conversation['email']); ?>
                                        </div>
                                        <?php if($conversation['unread_count'] > 0): ?>
                                            <div class="conversation-meta">
                                                <span class="unread-badge">
                                                    <?php echo $conversation['unread_count']; ?> unread
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Container -->
                <div class="chat-container">
                    <?php if($selectedUser): ?>
                        <div class="chat-header">
                            <div class="chat-header-avatar">
                                <?php if($selectedUser['profile_picture']): ?>
                                    <img src="<?php echo htmlspecialchars($selectedUser['profile_picture']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($selectedUser['first_name'], 0, 1) . substr($selectedUser['last_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="chat-header-info">
                                <div class="chat-header-name">
                                    <?php echo htmlspecialchars($selectedUser['first_name'] . ' ' . $selectedUser['last_name']); ?>
                                </div>
                                <div class="chat-header-email">
                                    <?php echo htmlspecialchars($selectedUser['email']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php if(empty($messages)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">💬</div>
                                    <h3>No Messages Yet</h3>
                                    <p>Start a conversation with this user</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($messages as $message): ?>
                                    <div class="message <?php echo !$message['is_from_user'] ? 'admin-message' : ''; ?>" 
                                         data-message-id="<?php echo $message['id']; ?>">
                                        <div class="message-avatar">
                                            <?php if($message['is_from_user'] && $message['sender_avatar']): ?>
                                                <img src="<?php echo htmlspecialchars($message['sender_avatar']); ?>" alt="Avatar">
                                            <?php else: ?>
                                                <?php echo $message['is_from_user'] ? 
                                                    strtoupper(substr($selectedUser['first_name'], 0, 1) . substr($selectedUser['last_name'], 0, 1)) : 
                                                    '👨‍💼'; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-content">
                                            <div class="message-sender">
                                                <?php echo htmlspecialchars($message['sender_name']); ?>
                                            </div>
                                            <div class="message-text">
                                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            </div>
                                            <div class="message-time">
                                                <?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="loading-indicator">Loading new messages...</div>
                        </div>

                        <div class="chat-input">
                            <form class="message-form" id="messageForm">
                                <textarea class="message-input" id="messageInput" 
                                          placeholder="Type your message here..." required></textarea>
                                <button type="submit" class="send-button" id="sendButton">
                                    <span>📤</span>
                                    Send Message
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <h3>Select a Conversation</h3>
                            <p>Choose a user from the list to start messaging</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Initial scroll
        scrollToBottom();

        // Handle message form submission
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');

        if (messageForm) {
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message) return;

                // Disable input and button while sending
                messageInput.disabled = true;
                sendButton.disabled = true;

                try {
                    const response = await fetch('send_admin_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=<?php echo $selectedUserId; ?>&message=${encodeURIComponent(message)}`
                    });

                    const data = await response.json();
                    if (data.success) {
                        // Clear input
                        messageInput.value = '';
                        
                        // Add message to chat
                        const messageHtml = `
                            <div class="message admin-message" data-message-id="${data.message_id}">
                                <div class="message-avatar">👨‍💼</div>
                                <div class="message-content">
                                    <div class="message-sender">${data.sender_name}</div>
                                    <div class="message-text">${data.message}</div>
                                    <div class="message-time">${data.created_at}</div>
                                </div>
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                        scrollToBottom();
                    } else {
                        alert(data.message || 'Error sending message');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error sending message');
                } finally {
                    // Re-enable input and button
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                }
            });
        }

        // Poll for new messages
        let lastMessageId = <?php echo empty($messages) ? 0 : end($messages)['id']; ?>;
        let isPolling = false;

        async function checkNewMessages() {
            if (isPolling || !<?php echo $selectedUserId ? 'true' : 'false'; ?>) return;
            isPolling = true;

            try {
                const response = await fetch(`get_admin_messages.php?user_id=<?php echo $selectedUserId; ?>&last_id=${lastMessageId}`);
                const data = await response.json();

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        if (message.id > lastMessageId) {
                            const messageHtml = `
                                <div class="message ${!message.is_from_user ? 'admin-message' : ''}" data-message-id="${message.id}">
                                    <div class="message-avatar">
                                        ${message.is_from_user ? 
                                            (message.sender_avatar ? 
                                                `<img src="${message.sender_avatar}" alt="Avatar">` : 
                                                message.sender_initials) : 
                                            '👨‍💼'}
                                    </div>
                                    <div class="message-content">
                                        <div class="message-sender">${message.sender_name}</div>
                                        <div class="message-text">${message.message}</div>
                                        <div class="message-time">${message.created_at}</div>
                                    </div>
                                </div>
                            `;
                            chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                            lastMessageId = message.id;
                        }
                    });
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error polling messages:', error);
            } finally {
                isPolling = false;
            }
        }

        // Start polling if a user is selected
        if (<?php echo $selectedUserId ? 'true' : 'false'; ?>) {
            setInterval(checkNewMessages, 5000);
        }

        // Handle search
        const searchInput = document.getElementById('searchInput');
        const conversationsList = document.getElementById('conversationsList');

        if (searchInput && conversationsList) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const conversationItems = conversationsList.querySelectorAll('.conversation-item');

                conversationItems.forEach(item => {
                    const name = item.querySelector('.conversation-name').textContent.toLowerCase();
                    const email = item.querySelector('.conversation-email').textContent.toLowerCase();
                    const shouldShow = name.includes(searchTerm) || email.includes(searchTerm);
                    item.style.display = shouldShow ? 'flex' : 'none';
                });
            });
        }

        // Auto-resize textarea
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    </script>
</body>
</html> 