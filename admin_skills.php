
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Allow administrators only
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

// Count available skills
$skillCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM skills");
if ($result) {
    $skillCount = $result->fetch_assoc()["total"];
}

// Count skill offers
$offerCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM skills_offered");
if ($result) {
    $offerCount = $result->fetch_assoc()["total"];
}

// Count wanted skills
$wantedCount = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM skills_wanted");
if ($result) {
    $wantedCount = $result->fetch_assoc()["total"];
}

// Get skills with offer and wanted counts
$skills = $conn->query("
    SELECT
        s.skill_id,
        s.skill_name,
        s.category,
        (SELECT COUNT(*)
         FROM skills_offered so
         WHERE so.skill_id = s.skill_id) AS offer_count,
        (SELECT COUNT(*)
         FROM skills_wanted sw
         WHERE sw.skill_id = s.skill_id) AS wanted_count
    FROM skills s
    ORDER BY s.skill_name ASC
");

// Get students offering skills
$offers = $conn->query("
    SELECT
        u.full_name,
        s.skill_name,
        so.proficiency_level
    FROM skills_offered so
    INNER JOIN users u ON so.user_id = u.user_id
    INNER JOIN skills s ON so.skill_id = s.skill_id
    ORDER BY s.skill_name ASC
");

// Get students wanting to learn skills
$wanted = $conn->query("
    SELECT
        u.full_name,
        s.skill_name
    FROM skills_wanted sw
    INNER JOIN users u ON sw.user_id = u.user_id
    INNER JOIN skills s ON sw.skill_id = s.skill_id
    ORDER BY s.skill_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skills Monitoring - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <h1>Skills Monitoring</h1>
    <p>Monitor available skills and students' learning interests.</p>

    <p>
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <hr>

    <h2>Skills Overview</h2>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Available Skills</h3>
            <p><?php echo $skillCount; ?></p>
        </div>

        <div class="card">
            <h3>Skills Offered</h3>
            <p><?php echo $offerCount; ?></p>
        </div>

        <div class="card">
            <h3>Skills Wanted</h3>
            <p><?php echo $wantedCount; ?></p>
        </div>

    </div>

    <hr>

    <h2>Available Skills and Interest</h2>

    <table>
        <thead>
            <tr>
                <th>Skill ID</th>
                <th>Skill Name</th>
                <th>Category</th>
                <th>Students Offering</th>
                <th>Students Wanting</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($skills && $skills->num_rows > 0): ?>
                <?php while ($row = $skills->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["skill_id"]); ?></td>
                        <td><?php echo htmlspecialchars($row["skill_name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["category"]); ?></td>
                        <td><?php echo htmlspecialchars($row["offer_count"]); ?></td>
                        <td><?php echo htmlspecialchars($row["wanted_count"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No skills found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <h2>Students Offering Skills</h2>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Skill Offered</th>
                <th>Proficiency Level</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($offers && $offers->num_rows > 0): ?>
                <?php while ($row = $offers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["skill_name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["proficiency_level"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No students have offered skills yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <h2>Students Wanting to Learn Skills</h2>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Skill They Want to Learn</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($wanted && $wanted->num_rows > 0): ?>
                <?php while ($row = $wanted->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["skill_name"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">No students have selected wanted skills yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
