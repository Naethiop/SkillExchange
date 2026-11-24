
<?php
// Start the session if it has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Ensure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Only students may access student pages
if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: admin_dashboard.php");
    exit();
}

// Check the student's current account status
$user_id = (int)$_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT is_active
     FROM users
     WHERE user_id = ? AND role = 'student'"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Reject deactivated accounts
if ((int)$user["is_active"] !== 1) {

    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit();
}
