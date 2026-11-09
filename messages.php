<?php

session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM messages WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: messages.php?deleted=1');
    exit();
}

// Fetch all messages
$result   = $conn->query('SELECT * FROM messages ORDER BY sent_at DESC');
$messages = $result->fetch_all(MYSQLI_ASSOC);

// Flash notice
$deleted_notice = isset($_GET['deleted']) ? 'Message deleted.' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Livestock Management System</title>
    <link rel="stylesheet" href="CSS/dashboard.css">
    <link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body>
    <div class="app">

                <aside class="sidebar">
            <div class="sidebar-brand">
                <img src="assets/favicon.png" alt="Logo" class="sidebar-logo">
                <span>Livestock</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link"><span class="nav-icon">🏠</span> Dashboard</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="messages.php" class="nav-link active"><span class="nav-icon">✉️</span> Messages</a>
                <?php endif; ?>
                <a href="animals.php" class="nav-link"><span class="nav-icon">🐄</span> Animals</a>
                <a href="health_records.php" class="nav-link"><span class="nav-icon">💉</span> Health Records</a>
                <a href="inventory.php" class="nav-link"><span class="nav-icon">📦</span> Inventory</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="reports.php" class="nav-link"><span class="nav-icon">📊</span> Reports</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link logout"><span class="nav-icon">↩</span> Sign Out</a>
            </div>
        </aside>

        <div class="main-area">

            <header class="topbar">
                <h1 class="page-title">Messages</h1>
                <div class="topbar-right">
                    <span class="badge badge-<?= htmlspecialchars($_SESSION['role']) ?>">
                        <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                    <div class="user-chip">
                        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['first_name'], 0, 1))) ?></span>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                    </div>
                </div>
            </header>

            <main class="content">

                <?php if ($deleted_notice !== ''): ?>
                    <div class="success"><?= htmlspecialchars($deleted_notice) ?></div>
                <?php endif; ?>

                <p class="intro">
                    All messages sent through the contact form. Click Reply to open your
                    email client, or Delete to remove a message.
                </p>

                <?php if (count($messages) === 0): ?>

                    <div class="empty-state">
                        <p>No messages yet. When visitors use the contact form, their
                        messages will appear here.</p>
                    </div>

                <?php else: ?>

                    <table class="messages-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Received</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td><?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?></td>
                                    <td><?= htmlspecialchars($msg['email']) ?></td>
                                    <td><?= htmlspecialchars($msg['subject'] ?: '—') ?></td>
                                    <td class="msg-preview">
                                        <?= htmlspecialchars(substr($msg['message'], 0, 60)) ?><?= strlen($msg['message']) > 60 ? '…' : '' ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($msg['sent_at']))) ?></td>
                                    <td class="actions">
                                        <a
                                            href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re: <?= htmlspecialchars(urlencode($msg['subject'] ?: 'Your message')) ?>"
                                            class="btn-reply"
                                        >Reply</a>

                                        <form method="POST" class="inline-form"
                                              onsubmit="return confirm('Delete this message from <?= htmlspecialchars(addslashes($msg['first_name'])) ?>? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>