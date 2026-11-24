
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Generate CSRF token if one does not exist
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $submitted_token = $_POST["csrf_token"] ?? "";

    // CSRF protection
    if (!hash_equals($_SESSION["csrf_token"], $submitted_token)) {

        $message = "Invalid form submission. Please refresh the page and try again.";

    } elseif ($email === "" || $password === "") {

        $message = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Incorrect email or password.";

    } else {

        // Retrieve user details, including role and account status
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password_hash, role, is_active
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user["password_hash"])) {

                // Check whether a student account is active
                if (
                    $user["role"] === "student" &&
                    (int)$user["is_active"] !== 1
                ) {

                    $message = "Your account has been deactivated. Please contact the administrator.";

                } else {

                    // Prevent session fixation
                    session_regenerate_id(true);

                    // Store authenticated user information
                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["role"] = $user["role"];

                    // Refresh CSRF token after login
                    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                    // Redirect according to account role
                    if ($user["role"] === "admin") {

                        header("Location: admin_dashboard.php");
                        exit();

                    } else {

                        header("Location: dashboard.php");
                        exit();
                    }
                }

            } else {

                $message = "Incorrect email or password.";
            }

        } else {

            $message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | SkillExchange</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<nav class="navbar">

    <div class="logo">SkillExchange</div>

    <div>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
    </div>

</nav>

<div class="auth-container">

    <h1>Welcome Back!</h1>

    <p class="subtitle">
        Log in to continue your learning journey.
    </p>

    <?php if ($message !== ""): ?>

        <div class="info-message">
            <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
        >

        <label for="email">Email Address</label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
            value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <div class="auth-link">
        Don't have an account?
        <a href="register.php">Create an account</a>
    </div>

</div>

</body>
</html>
