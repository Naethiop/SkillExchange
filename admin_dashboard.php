
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Protect the administrator dashboard
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

// Only administrators may access this page
if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$admin_name = htmlspecialchars(
    $_SESSION["full_name"] ?? "Administrator",
    ENT_QUOTES,
    "UTF-8"
);

// Helper function to get database counts
function getCount($conn, $query) {
    $result = $conn->query($query);

    if ($result) {
        $row = $result->fetch_assoc();
        return (int)$row["total"];
    }

    return 0;
}

// Dashboard statistics
$total_students = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'student'"
);

$total_profiles = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM student_profiles"
);

$total_skills = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM skills"
);

$total_connections = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM connection_requests"
);

$total_sessions = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM learning_sessions"
);

$total_messages = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM messages"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard | SkillExchange</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">SkillExchange Admin</div>

    <div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container dashboard">

    <div class="dashboard-welcome">
        <h1>Welcome, <?php echo $admin_name; ?> 👋</h1>
        <p>Administrator Control Panel</p>
        <p>Monitor the activities and performance of the SkillExchange platform.</p>
    </div>

    <h2>System Overview</h2>
    <p>Here is a summary of your platform.</p>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <span class="card-icon">👨‍🎓</span>
            <h3>Total Students</h3>
            <p><?php echo $total_students; ?></p>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">👤</span>
            <h3>Student Profiles</h3>
            <p><?php echo $total_profiles; ?></p>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">🎯</span>
            <h3>Available Skills</h3>
            <p><?php echo $total_skills; ?></p>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">🤝</span>
            <h3>Connection Requests</h3>
            <p><?php echo $total_connections; ?></p>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">📚</span>
            <h3>Learning Sessions</h3>
            <p><?php echo $total_sessions; ?></p>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">💬</span>
            <h3>Total Messages</h3>
            <p><?php echo $total_messages; ?></p>
        </div>

    </div>

    <hr>

    <h2>Administrative Monitoring</h2>

    <p>Select a section below to monitor platform activities.</p>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <span class="card-icon">👨‍🎓</span>
            <h3>Student Management</h3>
            <p>View registered students.</p>
            <a href="admin_students.php">View Students</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">👤</span>
            <h3>Student Profiles</h3>
            <p>Monitor student profile information.</p>
            <a href="admin_profiles.php">View Profiles</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">🎯</span>
            <h3>Skills Monitoring</h3>
            <p>View skills offered and wanted.</p>
            <a href="admin_skills.php">View Skills</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">🤝</span>
            <h3>Connection Monitoring</h3>
            <p>Monitor student connection requests.</p>
            <a href="admin_connections.php">View Connections</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">💬</span>
            <h3>Message Monitoring</h3>
            <p>Monitor messaging activity.</p>
            <a href="admin_messages.php">View Messages</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">📚</span>
            <h3>Session Monitoring</h3>
            <p>Monitor scheduled learning sessions.</p>
            <a href="admin_sessions.php">View Sessions</a>
        </div>

        <div class="dashboard-card">
            <span class="card-icon">🔐</span>
            <h3>User Management</h3>
            <p>Manage student accounts and control account activation.</p>
            <a href="admin_users.php">Manage Users</a>
        </div>

    </div>

</div>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> SkillExchange Administrator Panel.</p>
</footer>

</body>
</html>
