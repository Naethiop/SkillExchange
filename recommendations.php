 
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT
        u.user_id,
        u.full_name,
        sp.department,
        sp.level_of_study,
        mr.compatibility_score,
        mr.match_type
     FROM match_recommendations mr
     JOIN users u ON mr.matched_user_id = u.user_id
     LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
     WHERE mr.user_id = ?
       AND u.role = 'student'
       AND u.is_active = 1
     ORDER BY mr.compatibility_score DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$recommendations = $stmt->get_result();

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

    <title>Learning Partner Recommendations - SkillExchange</title>

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
            <h1>Recommended Learning Partners</h1>

            <p>
                Welcome, <?php echo $full_name; ?>.
                Discover students who can exchange digital skills with you.
            </p>
        </div>

        <section class="content-card">

            <h2>Your Matches</h2>

            <p>
                These recommendations are arranged according to
                their compatibility scores.
            </p>

            <?php if ($recommendations->num_rows > 0): ?>

                <div class="recommendation-list">

                    <?php while ($person = $recommendations->fetch_assoc()): ?>

                        <div class="recommendation-card">

                            <h3>
                                <?php echo htmlspecialchars($person["full_name"]); ?>
                            </h3>

                            <p>
                                <strong>Department:</strong>
                                <?php echo htmlspecialchars(
                                    $person["department"] ?? "Not provided"
                                ); ?>
                            </p>

                            <p>
                                <strong>Level:</strong>
                                <?php echo htmlspecialchars(
                                    $person["level_of_study"] ?? "Not provided"
                                ); ?>
                            </p>

                            <div class="match-score">
                                <strong>Compatibility Score:</strong>

                                <?php echo number_format(
                                    (float)$person["compatibility_score"],
                                    2
                                ); ?>%
                            </div>

                            <p>
                                <strong>Match Type:</strong>

                                <span class="status-badge status-accepted">
                                    <?php echo htmlspecialchars($person["match_type"]); ?>
                                </span>
                            </p>

                            <div class="card-actions">

                                <form method="POST" action="connections.php">

                                    <input
                                        type="hidden"
                                        name="receiver_id"
                                        value="<?php echo (int)$person["user_id"]; ?>"
                                    >

                                    <button type="submit">
                                        Send Connection Request
                                    </button>

                                </form>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <h3>No Recommendations Available Yet</h3>

                    <p>
                        We haven't found compatible learning partners for you yet.
                        Make sure other students have registered and added their
                        offered skills, wanted skills and availability.
                    </p>

                    <p>
                        You can also update your own skills and availability
                        to improve your chances of finding a match.
                    </p>

                </div>

            <?php endif; ?>

            <div class="card-actions">

                <a href="matches.php" class="secondary-button">
                    Refresh Recommendations
                </a>

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

<?php
$stmt->close();
$conn->close();
?>
