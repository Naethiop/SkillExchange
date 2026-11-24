
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Protect the page
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

// Search functionality
$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $stmt = $conn->prepare(
        "SELECT user_id, full_name, email, role
         FROM users
         WHERE role = 'student'
         AND (full_name LIKE ? OR email LIKE ?)
         ORDER BY user_id DESC"
    );

    $search_term = "%" . $search . "%";
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $students = $stmt->get_result();

} else {

    $stmt = $conn->prepare(
        "SELECT user_id, full_name, email, role
         FROM users
         WHERE role = 'student'
         ORDER BY user_id DESC"
    );

    $stmt->execute();
    $students = $stmt->get_result();
}

// Total students
$count_result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role = 'student'"
);

$total_students = $count_result->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | SkillExchange</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">SkillExchange Admin</div>

    <div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_students.php">Students</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container dashboard">

    <div class="dashboard-welcome">
        <h1>Student Management 👨‍🎓</h1>
        <p>View and monitor registered students on SkillExchange.</p>
    </div>

    <div class="dashboard-card">
        <h3>Total Registered Students</h3>
        <p><?php echo (int)$total_students; ?></p>
    </div>

    <h2>Registered Students</h2>

    <form method="GET" action="admin_students.php" style="margin-bottom: 20px;">

        <input
            type="text"
            name="search"
            placeholder="Search by name or email..."
            value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
        >

        <button type="submit">Search</button>

        <a href="admin_students.php">Clear</a>

    </form>

    <div style="overflow-x: auto;">

        <table style="width:100%; border-collapse:collapse;">

            <thead>
                <tr>
                    <th style="padding:12px; text-align:left;">Student ID</th>
                    <th style="padding:12px; text-align:left;">Full Name</th>
                    <th style="padding:12px; text-align:left;">Email</th>
                    <th style="padding:12px; text-align:left;">Role</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($students->num_rows > 0): ?>

                <?php while ($student = $students->fetch_assoc()): ?>

                    <tr>
                        <td style="padding:12px;">
                            <?php echo (int)$student["user_id"]; ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($student["full_name"], ENT_QUOTES, "UTF-8"); ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8"); ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($student["role"], ENT_QUOTES, "UTF-8"); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" style="padding:15px; text-align:center;">
                        No students found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> SkillExchange Admin Panel.</p>
</footer>

</body>
</html>

<?php
$stmt->close();
?>
