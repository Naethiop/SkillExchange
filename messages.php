 
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";

$partner_id = (int)($_GET["partner_id"] ?? $_POST["partner_id"] ?? 0);

// Check whether two students are accepted connections
// and both accounts are active.
function isConnected($conn, $user_id, $partner_id) {

    $stmt = $conn->prepare(
        "SELECT cr.request_id
         FROM connection_requests cr
         JOIN users u ON u.user_id = ?
         WHERE cr.status = 'accepted'
         AND u.role = 'student'
         AND u.is_active = 1
         AND (
             (cr.sender_id = ? AND cr.receiver_id = ?)
             OR (cr.sender_id = ? AND cr.receiver_id = ?)
         )"
    );

    $stmt->bind_param(
        "iiiii",
        $partner_id,
        $user_id,
        $partner_id,
        $partner_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $connected = $result->num_rows > 0;

    $stmt->close();

    return $connected;
}

// Send a message.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"])) {

    $content = trim($_POST["message_content"] ?? "");

    if ($partner_id == $user_id || $partner_id <= 0
        || !isConnected($conn, $user_id, $partner_id)) {

        $message = "You can only message an active, accepted connection.";

    } elseif ($content === "") {

        $message = "Please enter a message.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO messages
            (sender_id, receiver_id, message_content)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param("iis", $user_id, $partner_id, $content);

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: messages.php?partner_id=" . $partner_id);
            exit();

        } else {
            $message = "Message could not be sent.";
            $stmt->close();
        }
    }
}

// Retrieve accepted connections belonging to active students.
$stmt = $conn->prepare(
    "SELECT u.user_id, u.full_name
     FROM connection_requests cr
     JOIN users u ON u.user_id =
        CASE
            WHEN cr.sender_id = ? THEN cr.receiver_id
            ELSE cr.sender_id
        END
     WHERE (cr.sender_id = ? OR cr.receiver_id = ?)
     AND cr.status = 'accepted'
     AND u.role = 'student'
     AND u.is_active = 1"
);

$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();

$connections = $stmt->get_result();

// Retrieve the selected partner's name.
$partner_name = "";

if ($partner_id > 0 && $partner_id != $user_id
    && isConnected($conn, $user_id, $partner_id)) {

    $name_stmt = $conn->prepare(
        "SELECT full_name
         FROM users
         WHERE user_id = ?
         AND role = 'student'
         AND is_active = 1"
    );

    $name_stmt->bind_param("i", $partner_id);
    $name_stmt->execute();

    $name_result = $name_stmt->get_result();

    if ($row = $name_result->fetch_assoc()) {
        $partner_name = $row["full_name"];
    }

    $name_stmt->close();
}

// Retrieve conversation.
$conversation = null;

if ($partner_name !== "") {

    $stmt2 = $conn->prepare(
        "SELECT sender_id, message_content, sent_at
         FROM messages
         WHERE (sender_id = ? AND receiver_id = ?)
         OR (sender_id = ? AND receiver_id = ?)
         ORDER BY sent_at ASC"
    );

    $stmt2->bind_param(
        "iiii",
        $user_id,
        $partner_id,
        $partner_id,
        $user_id
    );

    $stmt2->execute();

    $conversation = $stmt2->get_result();
}

$full_name = htmlspecialchars(
    $_SESSION["full_name"] ?? "Student",
    ENT_QUOTES,
    "UTF-8"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Messages - SkillExchange</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <nav class="navbar">

        <div class="logo">SkillExchange</div>

        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>

    </nav>

    <main class="container">

        <div class="page-header">

            <h1>Student Messaging</h1>

            <p>
                Welcome, <?php echo $full_name; ?>.
                Communicate with your accepted learning partners.
            </p>

        </div>

        <?php if (!empty($message)): ?>

            <div class="success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <div class="messaging-layout">

            <!-- Connections section -->

            <section class="content-card">

                <h2>My Connections</h2>

                <p>Select a student to start or continue a conversation.</p>

                <?php if ($connections->num_rows > 0): ?>

                    <?php while ($person = $connections->fetch_assoc()): ?>

                        <div class="connection-card">

                            <h3>
                                <?php echo htmlspecialchars($person["full_name"]); ?>
                            </h3>

                            <a
                                href="messages.php?partner_id=<?php echo (int)$person["user_id"]; ?>"
                                class="secondary-button"
                            >
                                Open Conversation
                            </a>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">

                        <p>
                            You have no accepted connections yet.
                        </p>

                        <a href="connections.php">
                            View Connection Requests
                        </a>

                    </div>

                <?php endif; ?>

            </section>

            <!-- Conversation section -->

            <section class="content-card">

                <?php if ($conversation !== null): ?>

                    <h2>
                        Conversation with
                        <?php echo htmlspecialchars($partner_name); ?>
                    </h2>

                    <div class="chat-messages">

                        <?php if ($conversation->num_rows > 0): ?>

                            <?php while ($chat = $conversation->fetch_assoc()): ?>

                                <div class="chat-message">

                                    <strong>
                                        <?php echo $chat["sender_id"] == $user_id
                                            ? "You" : htmlspecialchars($partner_name); ?>
                                    </strong>

                                    <p>
                                        <?php echo nl2br(
                                            htmlspecialchars($chat["message_content"])
                                        ); ?>
                                    </p>

                                    <small>
                                        <?php echo htmlspecialchars($chat["sent_at"]); ?>
                                    </small>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="empty-state">
                                <p>No messages yet. Start the conversation!</p>
                            </div>

                        <?php endif; ?>

                    </div>

                    <form method="POST" action="messages.php?partner_id=<?php echo $partner_id; ?>">

                        <input
                            type="hidden"
                            name="partner_id"
                            value="<?php echo $partner_id; ?>"
                        >

                        <textarea
                            name="message_content"
                            placeholder="Write your message..."
                            rows="4"
                            required
                        ></textarea>

                        <button type="submit" name="send_message">
                            Send Message
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty-state">

                        <h2>Your Messages</h2>

                        <p>
                            Select one of your accepted connections to
                            view your conversation and send messages.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </div>

        <div class="card-actions">
            <a href="dashboard.php" class="back-link">
                Back to Dashboard
            </a>
        </div>

    </main>

    <footer class="footer">

        <p>
            &copy; <?php echo date("Y"); ?>
            SkillExchange. All rights reserved.
        </p>

    </footer>

</body>
</html>

<?php
$stmt->close();

if (isset($stmt2)) {
    $stmt2->close();
}

$conn->close();
?>
