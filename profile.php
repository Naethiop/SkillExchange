
<?php
// Protect this page using the shared student security guard
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];
$message = "";
$message_type = "";

$department = "";
$level = "";

$available_levels = [
    "100 Level",
    "200 Level",
    "300 Level",
    "400 Level",
    "500 Level"
];

// Process profile submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $department = trim($_POST["department"] ?? "");
    $level = trim($_POST["level"] ?? "");

    if ($department === "" || !in_array($level, $available_levels, true)) {

        $message = "Please enter your department and select a valid level.";
        $message_type = "danger";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO student_profiles
            (user_id, department, level_of_study)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            department = VALUES(department),
            level_of_study = VALUES(level_of_study)"
        );

        $stmt->bind_param("iss", $user_id, $department, $level);

        if ($stmt->execute()) {
            $message = "Profile saved successfully!";
            $message_type = "success";
        } else {
            $message = "Unable to save your profile. Please try again.";
            $message_type = "danger";
        }

        $stmt->close();
    }
}

// Retrieve existing profile
$stmt = $conn->prepare(
    "SELECT department, level_of_study
     FROM student_profiles
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$profile = $result->fetch_assoc();

$stmt->close();

// Use submitted values if validation failed
if ($_SERVER["REQUEST_METHOD"] == "POST" && $message_type == "danger") {
    // Keep the values the student entered
} elseif ($profile) {
    $department = $profile["department"];
    $level = $profile["level_of_study"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SkillExchange</title>
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

<div class="container">

    <div class="page-header">
        <h1>My Student Profile</h1>
        <p>Manage your academic information and keep your profile updated.</p>
    </div>

    <div class="content-card">

        <h2>Academic Information</h2>

        <p>
            Provide your department and current level of study.
            This information helps other students understand your academic background.
        </p>

        <?php if ($message !== ""): ?>
            <div class="<?php echo $message_type === 'success' ? 'success-message' : 'info-message'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">

            <label for="department">Department</label>

            <input
                type="text"
                id="department"
                name="department"
                placeholder="Enter your department"
                value="<?php echo htmlspecialchars($department); ?>"
                required
            >

            <label for="level">Level of Study</label>

            <select id="level" name="level" required>

                <option value="">Select your level</option>

                <?php foreach ($available_levels as $available_level): ?>

                    <option
                        value="<?php echo htmlspecialchars($available_level); ?>"
                        <?php echo ($level === $available_level) ? "selected" : ""; ?>
                    >
                        <?php echo htmlspecialchars($available_level); ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">Save Profile</button>

        </form>

    </div>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
