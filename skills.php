
<?php
// Protect this page using the shared student security guard
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";
$message_type = "";

$proficiency_levels = ["Beginner", "Intermediate", "Advanced"];

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Remove an offered skill
    if (isset($_POST["delete_offered"])) {

        $id = (int) $_POST["delete_offered"];

        $stmt = $conn->prepare(
            "DELETE FROM skills_offered
             WHERE offered_id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Offered skill removed successfully!";
            $message_type = "success";
        } else {
            $message = "Could not remove the skill.";
            $message_type = "danger";
        }

        $stmt->close();

    // Remove a wanted skill
    } elseif (isset($_POST["delete_wanted"])) {

        $id = (int) $_POST["delete_wanted"];

        $stmt = $conn->prepare(
            "DELETE FROM skills_wanted
             WHERE wanted_id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Wanted skill removed successfully!";
            $message_type = "success";
        } else {
            $message = "Could not remove the skill.";
            $message_type = "danger";
        }

        $stmt->close();

    // Add a skill
    } else {

        $skill_id = (int) ($_POST["skill_id"] ?? 0);
        $type = $_POST["type"] ?? "";
        $proficiency = $_POST["proficiency_level"] ?? "";

        if ($skill_id <= 0 || !in_array($type, ["offered", "wanted"], true)) {

            $message = "Please select a valid skill and skill type.";
            $message_type = "danger";

        } elseif (
            $type === "offered" &&
            !in_array($proficiency, $proficiency_levels, true)
        ) {

            $message = "Please select a valid proficiency level.";
            $message_type = "danger";

        } else {

            if ($type === "offered") {

                $stmt = $conn->prepare(
                    "INSERT INTO skills_offered
                    (user_id, skill_id, proficiency_level)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    proficiency_level = VALUES(proficiency_level)"
                );

                $stmt->bind_param(
                    "iis",
                    $user_id,
                    $skill_id,
                    $proficiency
                );

            } else {

                $stmt = $conn->prepare(
                    "INSERT IGNORE INTO skills_wanted
                    (user_id, skill_id)
                    VALUES (?, ?)"
                );

                $stmt->bind_param("ii", $user_id, $skill_id);
            }

            if ($stmt->execute()) {
                $message = "Skill saved successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to save the skill. Please try again.";
                $message_type = "danger";
            }

            $stmt->close();
        }
    }
}

// Retrieve available skills
$skills = $conn->query(
    "SELECT * FROM skills ORDER BY skill_name"
);

// Retrieve skills offered by the current student
$offered = $conn->prepare(
    "SELECT so.offered_id, s.skill_name, so.proficiency_level
     FROM skills_offered so
     JOIN skills s ON so.skill_id = s.skill_id
     WHERE so.user_id = ?
     ORDER BY s.skill_name"
);

$offered->bind_param("i", $user_id);
$offered->execute();

$offered_result = $offered->get_result();
$offered_skills = $offered_result->fetch_all(MYSQLI_ASSOC);

$offered->close();

// Retrieve skills wanted by the current student
$wanted = $conn->prepare(
    "SELECT sw.wanted_id, s.skill_name
     FROM skills_wanted sw
     JOIN skills s ON sw.skill_id = s.skill_id
     WHERE sw.user_id = ?
     ORDER BY s.skill_name"
);

$wanted->bind_param("i", $user_id);
$wanted->execute();

$wanted_result = $wanted->get_result();
$wanted_skills = $wanted_result->fetch_all(MYSQLI_ASSOC);

$wanted->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage My Skills | SkillExchange</title>

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
        <h1>Manage My Skills</h1>
        <p>
            Share the skills you can teach and select the skills
            you would like to learn from other students.
        </p>
    </div>

    <?php if ($message !== ""): ?>

        <div class="<?php echo $message_type === 'success' ? 'success-message' : 'info-message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- ADD SKILL FORM -->

    <div class="content-card">

        <h2>Add a Skill</h2>

        <p>
            Choose a skill and indicate whether you can teach it
            or want to learn it.
        </p>

        <form method="POST" action="skills.php">

            <label for="skill_id">Select Skill</label>

            <select name="skill_id" id="skill_id" required>

                <option value="">-- Choose a skill --</option>

                <?php while ($skill = $skills->fetch_assoc()): ?>

                    <option value="<?php echo (int) $skill['skill_id']; ?>">
                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                    </option>

                <?php endwhile; ?>

            </select>


            <label for="type">Skill Type</label>

            <select name="type" id="type" required>

                <option value="">-- Select type --</option>
                <option value="offered">I can teach this</option>
                <option value="wanted">I want to learn this</option>

            </select>


            <label for="proficiency_level">
                Proficiency Level (for skills offered)
            </label>

            <select name="proficiency_level" id="proficiency_level">

                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>

            </select>

            <button type="submit">Add Skill</button>

        </form>

    </div>


    <!-- SKILLS OFFERED -->

    <div class="content-card">

        <h2>Skills I Can Teach</h2>

        <p>These are the skills you can share with other students.</p>

        <?php if (empty($offered_skills)): ?>

            <div class="empty-state">
                You haven't added any skills to teach yet.
            </div>

        <?php else: ?>

            <?php foreach ($offered_skills as $skill): ?>

                <div class="skill-item">

                    <div>
                        <strong>
                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                        </strong>

                        <p>
                            Proficiency:
                            <?php echo htmlspecialchars($skill['proficiency_level']); ?>
                        </p>
                    </div>

                    <form method="POST" action="skills.php">

                        <input
                            type="hidden"
                            name="delete_offered"
                            value="<?php echo (int) $skill['offered_id']; ?>"
                        >

                        <button type="submit" class="danger-button">
                            Remove
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <!-- SKILLS WANTED -->

    <div class="content-card">

        <h2>Skills I Want to Learn</h2>

        <p>These are the skills you want to acquire from other students.</p>

        <?php if (empty($wanted_skills)): ?>

            <div class="empty-state">
                You haven't added any skills you want to learn yet.
            </div>

        <?php else: ?>

            <?php foreach ($wanted_skills as $skill): ?>

                <div class="skill-item">

                    <strong>
                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                    </strong>

                    <form method="POST" action="skills.php">

                        <input
                            type="hidden"
                            name="delete_wanted"
                            value="<?php echo (int) $skill['wanted_id']; ?>"
                        >

                        <button type="submit" class="danger-button">
                            Remove
                        </button>

                    </form>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
