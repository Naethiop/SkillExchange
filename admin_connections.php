
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

// Count connection requests
$totalRequests = 0;
$pendingRequests = 0;
$acceptedRequests = 0;
$rejectedRequests = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'accepted') AS accepted,
        SUM(status = 'rejected') AS rejected
    FROM connection_requests
");

if ($result) {
    $counts = $result->fetch_assoc();

    $totalRequests = $counts["total"] ?? 0;
    $pendingRequests = $counts["pending"] ?? 0;
    $acceptedRequests = $counts["accepted"] ?? 0;
    $rejectedRequests = $counts["rejected"] ?? 0;
}

// Retrieve all connection requests
$requests = $conn->query("
    SELECT
        cr.request_id,
        cr.status,
        cr.created_at,
        sender.full_name AS sender_name,
        receiver.full_name AS receiver_name
    FROM connection_requests cr
    INNER JOIN users sender
        ON cr.sender_id = sender.user_id
    INNER JOIN users receiver
        ON cr.receiver_id = receiver.user_id
    ORDER BY cr.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Monitoring - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Connection Monitoring</h1>

    <p>Monitor connection requests between students.</p>

    <p>
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <hr>

    <h2>Connection Statistics</h2>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Total Requests</h3>
            <p><?php echo $totalRequests; ?></p>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $pendingRequests; ?></p>
        </div>

        <div class="card">
            <h3>Accepted</h3>
            <p><?php echo $acceptedRequests; ?></p>
        </div>

        <div class="card">
            <h3>Rejected</h3>
            <p><?php echo $rejectedRequests; ?></p>
        </div>

    </div>

    <hr>

    <h2>All Connection Requests</h2>

    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Status</th>
                <th>Date Created</th>
            </tr>
        </thead>

        <tbody>

            <?php if ($requests && $requests->num_rows > 0): ?>

                <?php while ($row = $requests->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($row["request_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["sender_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["receiver_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(ucfirst($row["status"])); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["created_at"]); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5">No connection requests found.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>
