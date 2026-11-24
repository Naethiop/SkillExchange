
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";

// Process learning session requests.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $partner_id = (int)($_POST["partner_id"] ?? 0);
    $skill_id = (int)($_POST["skill_id"] ?? 0);
    $session_date = $_POST["session_date"] ?? "";
    $start_time = $_POST["start_time"] ?? "";
    $end_time = $_POST["end_time"] ?? "";

    // Check whether the selected student is an active, accepted connection.
    $check = $conn->prepare(
        "SELECT cr.request_id
         FROM connection_requests cr
         JOIN users u ON u.user_id =
             CASE
                 WHEN cr.sender_id = ? THEN cr.receiver_id
                 ELSE cr.sender_id
             END
         WHERE cr.status = 'accepted'
         AND (
             (cr.sender_id = ? AND cr.receiver_id = ?)
             OR (cr.sender_id = ? AND cr.receiver_id = ?)
         )
         AND u.role = 'student'
         AND u.is_active = 1"
    );

    $check->bind_param(
        "iiiii",
        $user_id,
        $user_id,
        $partner_id,
        $partner_id,
        $user_id
    );

    $check->execute();
    $connected = $check->get_result()->num_rows > 0;
    $check->close();

    // Check whether the selected partner offers the skill.
    $skillCheck = $conn->prepare(
        "SELECT offered_id
         FROM skills_offered
         WHERE user_id = ? AND skill_id = ?"
    );

    $skillCheck->bind_param("ii", $partner_id, $skill_id);
    $skillCheck->execute();

    $skillExists = $skillCheck->get_result()->num_rows > 0;
    $skillCheck->close();

    if (!$connected || $partner_id == $user_id) {

        $message = "You can only schedule with an active student who is an accepted connection.";

    } elseif (!$skillExists) {

        $message = "The selected partner does not offer this skill.";

    } elseif (
        $session_date === "" ||
        $session_date < date("Y-m-d")
    ) {

        $message = "Please select a valid future date.";

    } elseif (
        $start_time === "" ||
        $end_time === "" ||
        $start_time >= $end_time
    ) {

        $message = "End time must be later than start time.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO learning_sessions
            (requester_id, partner_id, skill_id,
             session_date, start_time, end_time)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iiisss",
            $user_id,
            $partner_id,
            $skill_id,
            $session_date,
            $start_time,
            $end_time
        );

        if ($stmt->execute()) {

            $message = "Learning session request sent successfully!";

        } else {

            $message = "Unable to schedule the learning session. Please try again.";

        }

        $stmt->close();
    }
}

// Retrieve accepted connections who have active student accounts.
$stmt = $conn->prepare(
    "SELECT DISTINCT u.user_id, u.full_name
     FROM connection_requests cr
     JOIN users u ON u.user_id =
         CASE
             WHEN cr.sender_id = ? THEN cr.receiver_id
             ELSE cr.sender_id
         END
     WHERE (cr.sender_id = ? OR cr.receiver_id = ?)
     AND cr.status = 'accepted'
     AND u.role = 'student'
     AND u.is_active = 1
     AND u.user_id != ?
     ORDER BY u.full_name"
);

$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();

$partners = $stmt->get_result();

// Retrieve available skills.
$skills = $conn->query(
    "SELECT skill_id, skill_name
     FROM skills
     ORDER BY skill_name"
);

// Retrieve learning sessions involving the current student.
// Only sessions involving active student accounts are displayed.
$stmt2 = $conn->prepare(
    "SELECT ls.*, s.skill_name,
        u.full_name AS partner_name,
        requester.full_name AS requester_name
     FROM learning_sessions ls
     JOIN skills s ON ls.skill_id = s.skill_id
     JOIN users u ON u.user_id =
         CASE
             WHEN ls.requester_id = ? THEN ls.partner_id
             ELSE ls.requester_id
         END
     JOIN users requester ON requester.user_id = ls.requester_id
     WHERE (ls.requester_id = ? OR ls.partner_id = ?)
     AND u.role = 'student'
     AND u.is_active = 1
     AND requester.role = 'student'
     AND requester.is_active = 1
     ORDER BY ls.session_date DESC, ls.start_time"
);

$stmt2->bind_param("iii", $user_id, $user_id, $user_id);
$stmt2->execute();

$sessions = $stmt2->get_result();

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

    <title>Learning Sessions - SkillExchange</title>

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

            <h1>Learning Session Management</h1>

            <p>
                Welcome, <?php echo $full_name; ?>.
                Schedule and manage your digital skills exchange sessions.
            </p>

        </div>

        <?php if (!empty($message)): ?>

            <div class="info-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <!-- Schedule a session -->

        <section class="content-card">

            <h2>Schedule a Learning Session</h2>

            <p>
                Arrange a learning session with one of your accepted connections.
            </p>

            <?php if ($partners->num_rows > 0): ?>

                <form method="POST" action="sessions.php">

                    <label>Select Learning Partner</label>

                    <select name="partner_id" required>

                        <option value="">Choose a partner</option>

                        <?php while ($partner = $partners->fetch_assoc()): ?>

                            <option value="<?php echo (int)$partner["user_id"]; ?>">

                                <?php echo htmlspecialchars($partner["full_name"]); ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                    <label>Select Skill</label>

                    <select name="skill_id" required>

                        <option value="">Choose a skill</option>

                        <?php while ($skill = $skills->fetch_assoc()): ?>

                            <option value="<?php echo (int)$skill["skill_id"]; ?>">

                                <?php echo htmlspecialchars($skill["skill_name"]); ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                    <label>Session Date</label>

                    <input
                        type="date"
                        name="session_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                    <label>Start Time</label>

                    <input
                        type="time"
                        name="start_time"
                        required
                    >

                    <label>End Time</label>

                    <input
                        type="time"
                        name="end_time"
                        required
                    >

                    <button type="submit">
                        Request Learning Session
                    </button>

                </form>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No Learning Partners Available</h3>

                    <p>
                        You need an accepted connection before you can
                        schedule a learning session.
                    </p>

                    <a href="connections.php" class="secondary-button">
                        View My Connections
                    </a>

                </div>

            <?php endif; ?>

        </section>

        <!-- Display scheduled sessions -->

        <section class="content-card">

            <h2>My Learning Sessions</h2>

            <p>
                View the learning sessions you have requested or received.
            </p>

            <?php if ($sessions->num_rows > 0): ?>

                <div class="session-list">

                    <?php while ($session = $sessions->fetch_assoc()): ?>

                        <?php
                        $status = strtolower($session["status"]);

                        $status_class = in_array(
                            $status,
                            ["pending", "confirmed", "cancelled"],
                            true
                        ) ? "status-" . $status : "";
                        ?>

                        <div class="connection-card">

                            <h3>
                                <?php echo htmlspecialchars($session["skill_name"]); ?>
                            </h3>

                            <p>
                                <strong>Learning Partner:</strong>

                                <?php echo htmlspecialchars($session["partner_name"]); ?>
                            </p>

                            <p>
                                <strong>Requested By:</strong>

                                <?php echo htmlspecialchars($session["requester_name"]); ?>
                            </p>

                            <p>
                                <strong>Date:</strong>

                                <?php echo htmlspecialchars($session["session_date"]); ?>
                            </p>

                            <p>
                                <strong>Time:</strong>

                                <?php echo htmlspecialchars($session["start_time"]); ?>
                                -
                                <?php echo htmlspecialchars($session["end_time"]); ?>
                            </p>

                            <p>
                                <strong>Status:</strong>

                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                </span>
                            </p>

                            <div class="card-actions">

                                <?php if (
                                    $status == "pending" &&
                                    $session["partner_id"] == $user_id
                                ): ?>

                                    <form method="POST" action="session_action.php">

                                        <input
                                            type="hidden"
                                            name="session_id"
                                            value="<?php echo (int)$session["session_id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="confirm"
                                        >
                                            Confirm Session
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <?php if (
                                    $status == "pending" ||
                                    $status == "confirmed"
                                ): ?>

                                    <form method="POST" action="session_action.php">

                                        <input
                                            type="hidden"
                                            name="session_id"
                                            value="<?php echo (int)$session["session_id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="cancel"
                                            class="danger-button"
                                        >
                                            Cancel Session
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No Learning Sessions Yet</h3>

                    <p>
                        You haven't scheduled or received any learning
                        session requests yet.
                    </p>

                </div>

            <?php endif; ?>

        </section>

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
$stmt2->close();
$conn->close();
?>
