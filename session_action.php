
<?php
require_once __DIR__ . '/student_guard.php';

$user_id = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: sessions.php");
    exit();
}

$session_id = (int)($_POST["session_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($session_id <= 0) {
    header("Location: sessions.php");
    exit();
}

if ($action === "confirm") {

    // Only the learning partner can confirm a pending session.
    $stmt = $conn->prepare(
        "UPDATE learning_sessions
         SET status = 'confirmed'
         WHERE session_id = ?
         AND partner_id = ?
         AND status = 'pending'"
    );

    $stmt->bind_param("ii", $session_id, $user_id);

} elseif ($action === "cancel") {

    // Either participant can cancel a pending or confirmed session.
    $stmt = $conn->prepare(
        "UPDATE learning_sessions
         SET status = 'cancelled'
         WHERE session_id = ?
         AND (requester_id = ? OR partner_id = ?)
         AND status IN ('pending', 'confirmed')"
    );

    $stmt->bind_param("iii", $session_id, $user_id, $user_id);

} else {

    header("Location: sessions.php");
    exit();
}

$stmt->execute();
$stmt->close();

header("Location: sessions.php");
exit();
?>
