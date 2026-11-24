
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";
$message_type = "";

$days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday"
];

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Delete availability
    if (isset($_POST["delete_slot"])) {

        $id = (int) $_POST["delete_slot"];

        $stmt = $conn->prepare(
            "DELETE FROM availability_slots
             WHERE availability_id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Availability removed successfully!";
            $message_type = "success";
        } else {
            $message = "Could not remove availability.";
            $message_type = "danger";
        }

        $stmt->close();

    } else {

        // Add availability
        $day = $_POST["day_of_week"] ?? "";
        $start = $_POST["start_time"] ?? "";
        $end = $_POST["end_time"] ?? "";

        if (!in_array($day, $days, true)) {

            $message = "Please select a valid day.";
            $message_type = "danger";

        } elseif (
            !preg_match('/^\d{2}:\d{2}$/', $start) ||
            !preg_match('/^\d{2}:\d{2}$/', $end) ||
            $start >= $end
        ) {

            $message = "Please enter valid times. End time must be later than start time.";
            $message_type = "danger";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO availability_slots
                (user_id, day_of_week, start_time, end_time)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "isss",
                $user_id,
                $day,
                $start,
                $end
            );

            if ($stmt->execute()) {
                $message = "Availability saved successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to save availability. Please try again.";
                $message_type = "danger";
            }

            $stmt->close();
        }
    }
}

// Retrieve student's availability
$stmt = $conn->prepare(
    "SELECT availability_id, day_of_week, start_time, end_time
     FROM availability_slots
     WHERE user_id = ?
     ORDER BY FIELD(day_of_week,
     'Monday','Tuesday','Wednesday','Thursday',
     'Friday','Saturday','Sunday'), start_time"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$availability = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Availability | SkillExchange</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<nav class="navbar">

    <div class="logo">SkillExchange</div>

    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>

</nav>


<div class="container">

    <div class="page-header">

        <h1>My Availability</h1>

        <p>
            Let other students know when you are available
            for digital skills exchange sessions.
        </p>

    </div>


    <!-- FEEDBACK MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="<?php echo $message_type === 'success' ? 'success-message' : 'info-message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- ADD AVAILABILITY -->

    <div class="content-card">

        <h2>Add Availability</h2>

        <p>
            Select a day and specify the time you are available
            to teach or learn from another student.
        </p>

        <form method="POST" action="availability.php">

            <label for="day_of_week">Day of the Week</label>

            <select name="day_of_week" id="day_of_week" required>

                <option value="">-- Select day --</option>

                <?php foreach ($days as $day): ?>

                    <option value="<?php echo htmlspecialchars($day); ?>">
                        <?php echo htmlspecialchars($day); ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <label for="start_time">Start Time</label>

            <input
                type="time"
                name="start_time"
                id="start_time"
                required
            >


            <label for="end_time">End Time</label>

            <input
                type="time"
                name="end_time"
                id="end_time"
                required
            >

            <button type="submit">Add Availability</button>

        </form>

    </div>


    <!-- DISPLAY AVAILABILITY -->

    <div class="content-card">

        <h2>My Available Times</h2>

        <p>
            These are the times other students can use
            when planning learning sessions with you.
        </p>

        <?php if ($availability->num_rows === 0): ?>

            <div class="empty-state">
                You haven't added your available times yet.
            </div>

        <?php else: ?>

            <?php while ($slot = $availability->fetch_assoc()): ?>

                <div class="skill-item">

                    <div>

                        <strong>
                            <?php echo htmlspecialchars($slot["day_of_week"]); ?>
                        </strong>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                date("h:i A", strtotime($slot["start_time"]))
                            );
                            ?>

                            –

                            <?php
                            echo htmlspecialchars(
                                date("h:i A", strtotime($slot["end_time"]))
                            );
                            ?>
                        </p>

                    </div>


                    <form method="POST" action="availability.php">

                        <input
                            type="hidden"
                            name="delete_slot"
                            value="<?php echo (int) $slot["availability_id"]; ?>"
                        >

                        <button type="submit" class="danger-button">
                            Remove
                        </button>

                    </form>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>


    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
