
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

// Session statistics
$totalSessions = 0;
$pendingSessions = 0;
$confirmedSessions = 0;
$completedSessions = 0;
$cancelledSessions = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'pending'), 0) AS pending,
        COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
        COALESCE(SUM(status = 'completed'), 0) AS completed,
        COALESCE(SUM(status = 'cancelled'), 0) AS cancelled
    FROM learning_sessions
");

if ($result) {
    $counts = $result->fetch_assoc();

    $totalSessions = $counts["total"];
    $pendingSessions = $counts["pending"];
    $confirmedSessions = $counts["confirmed"];
    $completedSessions = $counts["completed"];
    $cancelledSessions = $counts["cancelled"];
}

// Retrieve all learning sessions
$sessions = $conn->query("
    SELECT
        ls.session_id,
        ls.session_date,
        ls.start_time,
        ls.end_time,
        ls.status,
        requester.full_name AS requester_name,
        partner.full_name AS partner_name,
        s.skill_name
    FROM learning_sessions ls
    INNER JOIN users requester
        ON ls.requester_id = requester.user_id
    INNER JOIN users partner
        ON ls.partner_id = partner.user_id
    INNER JOIN skills s
        ON ls.skill_id = s.skill_id
    ORDER BY ls.session_date DESC, ls.start_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Session Monitoring - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Learning Session Monitoring</h1>

    <p>Monitor learning sessions scheduled between students.</p>

    <p>
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <hr>

    <h2>Session Statistics</h2>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Sessions</h3>
            <p><?php echo $totalSessions; ?></p>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $pendingSessions; ?></p>
        </div>

        <div class="card">
            <h3>Confirmed</h3>
            <p><?php echo $confirmedSessions; ?></p>
        </div>

        <div class="card">
            <h3>Completed</h3>
            <p><?php echo $completedSessions; ?></p>
        </div>

        <div class="card">
            <h3>Cancelled</h3>
            <p><?php echo $cancelledSessions; ?></p>
        </div>

    </div>

    <hr>

    <h2>All Learning Sessions</h2>

    <table>
        <thead>
            <tr>
                <th>Session ID</th>
                <th>Requester</th>
                <th>Partner</th>
                <th>Skill</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            <?php if ($sessions && $sessions->num_rows > 0): ?>

                <?php while ($row = $sessions->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($row["session_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["requester_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["partner_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["skill_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["session_date"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["start_time"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["end_time"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(ucfirst($row["status"])); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">No learning sessions found.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>
