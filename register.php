
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Generate CSRF token if one does not exist
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

$full_name = "";
$email = "";

// Process registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    // CSRF protection
    $submitted_token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submitted_token)) {

        $message = "Invalid form submission. Please refresh the page and try again.";
        $message_type = "danger";

    } elseif (
        $full_name === "" ||
        $email === "" ||
        $password === ""
    ) {

        $message = "Please fill in all fields.";
        $message_type = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";
        $message_type = "danger";

    } elseif (strlen($password) > 72) {

        $message = "Password is too long. Please use a shorter password.";
        $message_type = "danger";

    } else {

        // Check whether email already exists
        $check = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "This email address is already registered.";
            $message_type = "danger";

        } else {

            // Hash password before saving
            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert new user
            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, password_hash)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $full_name,
                $email,
                $password_hash
            );

            if ($stmt->execute()) {

                $message = "Registration successful! You can now log in.";
                $message_type = "success";

                // Clear form fields after successful registration
                $full_name = "";
                $email = "";

                // Refresh CSRF token
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            } else {

                // Handle a duplicate email if another registration
                // happens at the same time
                if ($conn->errno == 1062) {
                    $message = "This email address is already registered.";
                } else {
                    $message = "Registration failed. Please try again.";
                }

                $message_type = "danger";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | SkillExchange</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<nav class="navbar">
    <div class="logo">SkillExchange</div>

    <div>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<div class="auth-container">

    <h1>Create Your Account</h1>

    <p class="subtitle">
        Join other students and start exchanging digital skills.
    </p>

    <?php if ($message !== ""): ?>

        <div class="<?php echo $message_type === 'success' ? 'success-message' : 'info-message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="register.php">

        <!-- CSRF protection token -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <label for="full_name">Full Name</label>

        <input
            type="text"
            id="full_name"
            name="full_name"
            placeholder="Enter your full name"
            value="<?php echo htmlspecialchars($full_name); ?>"
            required
        >

        <label for="email">Email Address</label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
            value="<?php echo htmlspecialchars($email); ?>"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Create a password (minimum 8 characters)"
            minlength="8"
            maxlength="72"
            required
        >

        <button type="submit">Create Account</button>

    </form>

    <div class="auth-link">
        Already have an account?
        <a href="login.php">Login</a>
    </div>

</div>

</body>
</html>
