
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Protect the dashboard from users who are not logged in
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["full_name"])) {
    header("Location: login.php");
    exit();
}

// Only students may access this dashboard
if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: admin_dashboard.php");
    exit();
}

// Check whether the student's account is still active
$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT is_active FROM users WHERE user_id = ? AND role = 'student'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    // End the session if the account no longer exists
    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ((int)$user["is_active"] !== 1) {

    // End the session if the administrator deactivated the account
    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}

// Safely display the logged-in user's name
$full_name = htmlspecialchars(
    $_SESSION["full_name"],
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SkillExchange</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">SkillExchange</div>
    <div>
        <a href="dashboard.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container dashboard">

    <div class="dashboard-welcome">
        <h1>Welcome, <?php echo $full_name; ?> 👋</h1>
        <p>Exchange skills, connect with students, and grow together.</p>
    </div>

    <h2>Your Learning Hub</h2>
    <p>Choose what you want to do today.</p>

    <div class="dashboard-grid">

        <a href="profile.php" class="dashboard-card">
            <span class="card-icon">👤</span>
            <h3>My Student Profile</h3>
            <p>Manage your personal and academic information.</p>
        </a>

        <a href="skills.php" class="dashboard-card">
            <span class="card-icon">🎯</span>
            <h3>Manage My Skills</h3>
            <p>Add skills you can teach and skills you want to learn.</p>
        </a>

        <a href="availability.php" class="dashboard-card">
            <span class="card-icon">🕒</span>
            <h3>My Availability</h3>
            <p>Set the days and times you're available to learn.</p>
        </a>

        <a href="matches.php" class="dashboard-card">
            <span class="card-icon">🤝</span>
            <h3>Find Skill Partners</h3>
            <p>Discover students with compatible skills.</p>
        </a>

        <a href="connections.php" class="dashboard-card">
            <span class="card-icon">🔗</span>
            <h3>My Connections</h3>
            <p>Manage connection requests and learning partners.</p>
        </a>

        <a href="messages.php" class="dashboard-card">
            <span class="card-icon">💬</span>
            <h3>My Messages</h3>
            <p>Communicate with your connected students.</p>
        </a>

        <a href="sessions.php" class="dashboard-card">
            <span class="card-icon">📚</span>
            <h3>Learning Sessions</h3>
            <p>Schedule, confirm, and manage skill-exchange sessions.</p>
        </a>

    </div>

</div>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> SkillExchange. Learn. Teach. Grow.</p>
</footer>

</body>
</html>
