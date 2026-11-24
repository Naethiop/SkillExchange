
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Administrator access only
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

// Message statistics
$totalMessages = 0;
$readMessages = 0;
$unreadMessages = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(is_read = 1), 0) AS read_count,
        COALESCE(SUM(is_read = 0), 0) AS unread_count
    FROM messages
");

if ($result) {
    $counts = $result->fetch_assoc();

    $totalMessages = $counts["total"];
    $readMessages = $counts["read_count"];
    $unreadMessages = $counts["unread_count"];
}

// Retrieve message activity
// Message contents are intentionally not displayed.
$messages = $conn->query("
    SELECT
        m.message_id,
        m.sent_at,
        m.is_read,
        sender.full_name AS sender_name,
        receiver.full_name AS receiver_name
    FROM messages m
    INNER JOIN users sender
        ON m.sender_id = sender.user_id
    INNER JOIN users receiver
        ON m.receiver_id = receiver.user_id
    ORDER BY m.sent_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Monitoring - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Message Monitoring</h1>

    <p>Monitor messaging activity between students.</p>

    <p>
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <hr>

    <h2>Messaging Statistics</h2>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Messages</h3>
            <p><?php echo $totalMessages; ?></p>
        </div>

        <div class="card">
            <h3>Read Messages</h3>
            <p><?php echo $readMessages; ?></p>
        </div>

        <div class="card">
            <h3>Unread Messages</h3>
            <p><?php echo $unreadMessages; ?></p>
        </div>

    </div>

    <hr>

    <h2>Student Messaging Activity</h2>

    <p>
        For privacy, this administrative page displays message
        activity and delivery status without exposing the actual
        contents of students' private messages.
    </p>

    <table>
        <thead>
            <tr>
                <th>Message ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Date Sent</th>
                <th>Read Status</th>
            </tr>
        </thead>

        <tbody>

            <?php if ($messages && $messages->num_rows > 0): ?>

                <?php while ($row = $messages->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($row["message_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["sender_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["receiver_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["sent_at"]); ?>
                        </td>

                        <td>
                            <?php echo ((int)$row["is_read"] === 1)
                                ? "Read"
                                : "Unread"; ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5">No messages found.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>
