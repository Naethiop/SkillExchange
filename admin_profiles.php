
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

// Retrieve student profile information
$stmt = $conn->prepare(
    "SELECT
        u.user_id,
        u.full_name,
        u.email,
        p.department,
        p.level_of_study
     FROM users u
     LEFT JOIN student_profiles p
        ON u.user_id = p.user_id
     WHERE u.role = 'student'
     ORDER BY u.user_id DESC"
);

$stmt->execute();
$profiles = $stmt->get_result();

// Count completed profiles
$count_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM student_profiles p
     INNER JOIN users u ON p.user_id = u.user_id
     WHERE u.role = 'student'"
);

$total_profiles = (int)$count_result->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profiles | SkillExchange</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">SkillExchange Admin</div>

    <div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_students.php">Students</a>
        <a href="admin_profiles.php">Profiles</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container dashboard">

    <div class="dashboard-welcome">
        <h1>Student Profile Monitoring 👤</h1>
        <p>Monitor students' academic information.</p>
    </div>

    <div class="dashboard-card">
        <h3>Profiles Created</h3>
        <p><?php echo $total_profiles; ?></p>
    </div>

    <h2>Student Profiles</h2>

    <div style="overflow-x:auto;">

        <table style="width:100%; border-collapse:collapse;">

            <thead>
                <tr>
                    <th style="padding:12px; text-align:left;">Student ID</th>
                    <th style="padding:12px; text-align:left;">Full Name</th>
                    <th style="padding:12px; text-align:left;">Email</th>
                    <th style="padding:12px; text-align:left;">Department</th>
                    <th style="padding:12px; text-align:left;">Level of Study</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($profiles->num_rows > 0): ?>

                <?php while ($profile = $profiles->fetch_assoc()): ?>

                    <tr>
                        <td style="padding:12px;">
                            <?php echo (int)$profile["user_id"]; ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($profile["full_name"], ENT_QUOTES, "UTF-8"); ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($profile["email"], ENT_QUOTES, "UTF-8"); ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($profile["department"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?>
                        </td>

                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($profile["level_of_study"] ?? "Not provided", ENT_QUOTES, "UTF-8"); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" style="padding:15px; text-align:center;">
                        No student profiles found.
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
