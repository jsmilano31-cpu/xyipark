<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Require user authentication
requireUser();

// Fetch user data
$sql = "SELECT * FROM ipark_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch messages for this user
$sql = "
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
    WHERE m.user_id = ?
    ORDER BY m.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

// Mark unread admin messages as read
$sql = "
    UPDATE ipark_messages 
    SET is_read = TRUE 
    WHERE user_id = ? AND is_from_user = FALSE AND is_read = FALSE
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Admin - IPark</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
 .user-section {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-700);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--light);
            font-size: 1.25rem;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--light);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
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

        /* Include the same app-container and sidebar styles as other pages */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Chat Container Styles */
        .chat-container {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            max-width: calc(100vw - 280px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .chat-header {
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .chat-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-header p {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .chat-messages {
            flex: 1;
            background: var(--light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
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

        .message.user-message {
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

        .user-message .message-content {
            background: var(--primary);
            color: var(--light);
        }

        .message-sender {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: var(--gray-700);
        }

        .user-message .message-sender {
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

        .user-message .message-time {
            color: var(--light);
            opacity: 0.8;
        }

        .chat-input {
            background: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
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

        /* New Message Indicator */
        .new-message-indicator {
            background: var(--primary);
            color: var(--light);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            margin: 1rem auto;
            display: none;
            cursor: pointer;
            box-shadow: var(--shadow);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .chat-container {
                margin-left: 0;
                max-width: 100vw;
            }
        }

        @media (max-width: 640px) {
            .chat-container {
                padding: 1rem;
            }

            .message {
                max-width: 90%;
            }

            .chat-header {
                padding: 1rem;
            }

            .chat-messages {
                padding: 1rem;
            }

            .chat-input {
                padding: 1rem;
            }

            .message-form {
                flex-direction: column;
            }

            .send-button {
                width: 100%;
            }
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <span style="font-size: 1.5rem;">☰</span>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/images/logo.png" alt="IPark Logo" style="max-width: 80px; display: block; margin: 0 auto 1rem auto;">
                <h1>
                    IPark
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
                        <a href="reserve.php" class="nav-link">
                            <span class="nav-icon">🚗</span>
                            Reserve a Slot
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="my_reservations.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            My Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="message.php" class="nav-link active">
                            <span class="nav-icon">💬</span>
                            Message Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <span class="nav-icon">👤</span>
                            Profile Settings
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="user-section">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if($user['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Chat Container -->
        <div class="chat-container">
            <div class="chat-header">
                <h1>
                    <span style="font-size: 1.5rem;">💬</span>
                    Message Admin
                </h1>
                <p>Get in touch with our support team for any assistance</p>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if(empty($messages)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <h3>No Messages Yet</h3>
                        <p>Start a conversation with our support team</p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $message): ?>
                        <div class="message <?php echo $message['is_from_user'] ? 'user-message' : ''; ?>" 
                             data-message-id="<?php echo $message['id']; ?>">
                            <div class="message-avatar">
                                <?php if($message['is_from_user'] && $message['sender_avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($message['sender_avatar']); ?>" alt="Avatar">
                                <?php else: ?>
                                    <?php echo $message['is_from_user'] ? 
                                        strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) : 
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

            <div class="new-message-indicator" id="newMessageIndicator" style="display: none;">
                New messages received! Click to view.
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
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Initial scroll
        scrollToBottom();

        // Handle message form submission
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        const newMessageIndicator = document.getElementById('newMessageIndicator');

        messageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Disable input and button while sending
            messageInput.disabled = true;
            sendButton.disabled = true;

            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}`
                });

                const data = await response.json();
                if (data.success) {
                    // Clear input
                    messageInput.value = '';
                    
                    // Add message to chat
                    const messageHtml = `
                        <div class="message user-message" data-message-id="${data.message_id}">
                            <div class="message-avatar">
                                ${data.user_avatar ? 
                                    `<img src="${data.user_avatar}" alt="Avatar">` : 
                                    '${data.user_initials}'}
                            </div>
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

        // Poll for new messages
        let lastMessageId = <?php echo empty($messages) ? 0 : end($messages)['id']; ?>;
        let isPolling = false;
        let shouldScroll = true;

        async function checkNewMessages() {
            if (isPolling) return;
            isPolling = true;

            try {
                const response = await fetch(`get_messages.php?last_id=${lastMessageId}`);
                const data = await response.json();

                if (data.messages && data.messages.length > 0) {
                    const wasAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop === chatMessages.clientHeight;
                    
                    data.messages.forEach(message => {
                        if (message.id > lastMessageId) {
                            const messageHtml = `
                                <div class="message" data-message-id="${message.id}">
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

                    // Show new message indicator if not at bottom
                    if (!wasAtBottom) {
                        newMessageIndicator.style.display = 'block';
                    } else {
                        scrollToBottom();
                    }
                }
            } catch (error) {
                console.error('Error polling messages:', error);
            } finally {
                isPolling = false;
            }
        }

        // Start polling
        setInterval(checkNewMessages, 5000);

        // Handle new message indicator click
        newMessageIndicator.addEventListener('click', () => {
            scrollToBottom();
            newMessageIndicator.style.display = 'none';
        });

        // Handle scroll events
        chatMessages.addEventListener('scroll', () => {
            const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop === chatMessages.clientHeight;
            if (isAtBottom) {
                newMessageIndicator.style.display = 'none';
            }
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('show');
            }
        });

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html> 