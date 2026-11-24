 
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";

// Handle connection requests and responses.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Send a connection request.
    if (isset($_POST["receiver_id"])) {

        $receiver_id = (int) $_POST["receiver_id"];

        if ($receiver_id != $user_id && $receiver_id > 0) {

            // Verify that the receiver is an active student.
            $check = $conn->prepare(
                "SELECT user_id
                 FROM users
                 WHERE user_id = ?
                 AND role = 'student'
                 AND is_active = 1"
            );

            $check->bind_param("i", $receiver_id);
            $check->execute();

            $valid_receiver = $check->get_result()->num_rows === 1;
            $check->close();

            if ($valid_receiver) {

                $stmt = $conn->prepare(
                    "INSERT INTO connection_requests
                    (sender_id, receiver_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE
                    status = IF(status = 'rejected',
                    'pending', status)"
                );

                $stmt->bind_param("ii", $user_id, $receiver_id);
                $stmt->execute();
                $stmt->close();

                $message = "Connection request sent!";

            } else {

                $message = "This student account is unavailable.";
            }

        } else {

            $message = "Invalid connection request.";
        }

    // Accept or reject an incoming request.
    } elseif (isset($_POST["request_id"], $_POST["action"])) {

        $request_id = (int) $_POST["request_id"];
        $action = $_POST["action"];

        if ($action == "accept" || $action == "reject") {

            $status = ($action == "accept") ? "accepted" : "rejected";

            $stmt = $conn->prepare(
                "UPDATE connection_requests
                 SET status = ?
                 WHERE request_id = ?
                 AND receiver_id = ?
                 AND status = 'pending'"
            );

            $stmt->bind_param("sii", $status, $request_id, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = ($action == "accept")
                    ? "Connection request accepted!"
                    : "Connection request rejected!";
            } else {
                $message = "This request is no longer available.";
            }

            $stmt->close();
        }
    }
}

// Retrieve incoming pending requests from active students.
$stmt = $conn->prepare(
    "SELECT cr.request_id, u.full_name, u.email
     FROM connection_requests cr
     JOIN users u ON cr.sender_id = u.user_id
     WHERE cr.receiver_id = ?
     AND cr.status = 'pending'
     AND u.role = 'student'
     AND u.is_active = 1"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$incoming = $stmt->get_result();

// Retrieve accepted connections that are still active students.
$stmt2 = $conn->prepare(
    "SELECT u.full_name, u.email
     FROM connection_requests cr
     JOIN users u ON
        u.user_id = CASE
            WHEN cr.sender_id = ? THEN cr.receiver_id
            ELSE cr.sender_id
        END
     WHERE (cr.sender_id = ? OR cr.receiver_id = ?)
     AND cr.status = 'accepted'
     AND u.role = 'student'
     AND u.is_active = 1"
);

$stmt2->bind_param("iii", $user_id, $user_id, $user_id);
$stmt2->execute();

$accepted = $stmt2->get_result();

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

    <title>My Connections - SkillExchange</title>

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

            <h1>My Connections</h1>

            <p>
                Welcome, <?php echo $full_name; ?>.
                Manage your learning partnerships here.
            </p>

        </div>

        <?php if (!empty($message)): ?>

            <div class="success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <section class="content-card">

            <h2>Incoming Connection Requests</h2>

            <p>
                Students who want to connect and exchange digital skills with you.
            </p>

            <?php if ($incoming->num_rows > 0): ?>

                <div class="connection-list">

                    <?php while ($request = $incoming->fetch_assoc()): ?>

                        <div class="connection-card">

                            <h3>
                                <?php echo htmlspecialchars($request["full_name"]); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($request["email"]); ?>
                            </p>

                            <span class="status-badge status-pending">
                                Pending Request
                            </span>

                            <div class="card-actions">

                                <form method="POST" action="connections.php">

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?php echo (int)$request["request_id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="action"
                                        value="accept"
                                    >
                                        Accept
                                    </button>

                                    <button
                                        type="submit"
                                        name="action"
                                        value="reject"
                                        class="danger-button"
                                    >
                                        Reject
                                    </button>

                                </form>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No Pending Requests</h3>

                    <p>
                        You don't have any incoming connection requests at the moment.
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <section class="content-card">

            <h2>My Accepted Connections</h2>

            <p>
                These are the students you have successfully connected with.
            </p>

            <?php if ($accepted->num_rows > 0): ?>

                <div class="connection-list">

                    <?php while ($person = $accepted->fetch_assoc()): ?>

                        <div class="connection-card">

                            <h3>
                                <?php echo htmlspecialchars($person["full_name"]); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($person["email"]); ?>
                            </p>

                            <span class="status-badge status-accepted">
                                Connected
                            </span>

                            <div class="card-actions">

                                <a href="messages.php" class="secondary-button">
                                    Open Messages
                                </a>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No Accepted Connections Yet</h3>

                    <p>
                        Once you accept a connection request, your learning
                        partners will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <div class="card-actions">

            <a href="recommendations.php" class="secondary-button">
                Find Learning Partners
            </a>

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
$stmt2->close();
$conn->close();
?>
