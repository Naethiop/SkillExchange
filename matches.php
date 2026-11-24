 
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];

function getSkills($conn, $user_id, $table) {

    // Only allow the two skill tables used by this function.
    $allowed_tables = ["skills_wanted", "skills_offered"];

    if (!in_array($table, $allowed_tables, true)) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT skill_id FROM $table WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $skills = [];

    while ($row = $result->fetch_assoc()) {
        $skills[] = (int)$row["skill_id"];
    }

    $stmt->close();

    return $skills;
}

function getAvailability($conn, $user_id) {

    $stmt = $conn->prepare(
        "SELECT day_of_week, start_time, end_time
         FROM availability_slots
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $slots = [];

    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }

    $stmt->close();

    return $slots;
}

function getAvailabilityScore($a, $b) {

    $totalA = 0;
    $totalB = 0;
    $overlap = 0;

    foreach ($a as $slot) {
        $totalA += strtotime($slot["end_time"])
                 - strtotime($slot["start_time"]);
    }

    foreach ($b as $slot) {
        $totalB += strtotime($slot["end_time"])
                 - strtotime($slot["start_time"]);
    }

    foreach ($a as $slotA) {
        foreach ($b as $slotB) {

            if ($slotA["day_of_week"] == $slotB["day_of_week"]) {

                $start = max(
                    strtotime($slotA["start_time"]),
                    strtotime($slotB["start_time"])
                );

                $end = min(
                    strtotime($slotA["end_time"]),
                    strtotime($slotB["end_time"])
                );

                if ($end > $start) {
                    $overlap += $end - $start;
                }
            }
        }
    }

    if (($totalA + $totalB) == 0) {
        return 0;
    }

    return min(1, (2 * $overlap) / ($totalA + $totalB));
}

// Get the current student's skills and availability.
$wantedA = getSkills($conn, $user_id, "skills_wanted");
$offeredA = getSkills($conn, $user_id, "skills_offered");
$availabilityA = getAvailability($conn, $user_id);

// Retrieve only other active student accounts.
$users = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE user_id != ?
     AND role = 'student'
     AND is_active = 1"
);

$users->bind_param("i", $user_id);
$users->execute();

$otherUsers = $users->get_result();

// Prepare the recommendation database operation.
$save = $conn->prepare(
    "INSERT INTO match_recommendations
    (user_id, matched_user_id, compatibility_score, match_type)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    compatibility_score = VALUES(compatibility_score),
    match_type = VALUES(match_type)"
);

$match_count = 0;

while ($other = $otherUsers->fetch_assoc()) {

    $other_id = (int)$other["user_id"];

    $wantedB = getSkills($conn, $other_id, "skills_wanted");
    $offeredB = getSkills($conn, $other_id, "skills_offered");
    $availabilityB = getAvailability($conn, $other_id);

    // Compare the current student's wanted skills
    // with the other student's offered skills.
    $forward = count($wantedA) > 0
        ? count(array_intersect($wantedA, $offeredB))
          / count($wantedA)
        : 0;

    // Compare the other student's wanted skills
    // with the current student's offered skills.
    $reverse = count($wantedB) > 0
        ? count(array_intersect($wantedB, $offeredA))
          / count($wantedB)
        : 0;

    // Calculate shared availability.
    $availability = getAvailabilityScore(
        $availabilityA,
        $availabilityB
    );

    // Calculate the weighted compatibility score.
    $score = 100 * (
        0.4 * $forward +
        0.4 * $reverse +
        0.2 * $availability
    );

    // Save students who have at least one skill match.
    if ($forward > 0 || $reverse > 0) {

        $match_type = ($forward > 0 && $reverse > 0)
            ? "Reciprocal"
            : "One-way";

        $save->bind_param(
            "iids",
            $user_id,
            $other_id,
            $score,
            $match_type
        );

        $save->execute();

        $match_count++;
    }
}

$save->close();
$users->close();

$conn->close();

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

    <title>Find Learning Partners - SkillExchange</title>

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
            <h1>Find Learning Partners</h1>
            <p>Discover students whose skills and learning interests match yours.</p>
        </div>

        <section class="content-card">

            <div class="success-message">
                <h2>Skill Matching Completed!</h2>

                <p>
                    Hello, <?php echo $full_name; ?>.
                    Your skill compatibility assessment has been completed.
                </p>
            </div>

            <div class="empty-state">

                <?php if ($match_count > 0): ?>

                    <h3>Your learning partners are ready!</h3>

                    <p>
                        We found <?php echo $match_count; ?>
                        compatible student(s) based on your skills and learning interests.
                    </p>

                <?php else: ?>

                    <h3>No compatible partners found yet.</h3>

                    <p>
                        Try adding more skills you want to learn,
                        skills you can teach, or your availability.
                        This may help us find suitable learning partners.
                    </p>

                <?php endif; ?>

                <a href="recommendations.php" class="secondary-button">
                    View Recommendations
                </a>

                <br><br>

                <a href="dashboard.php" class="back-link">
                    Back to Dashboard
                </a>

            </div>

        </section>

    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> SkillExchange. All rights reserved.</p>
    </footer>

</body>
</html>
