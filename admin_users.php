
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Administrator access only
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$search = "";

// Process account activation/deactivation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $target_id = (int)($_POST["user_id"] ?? 0);
    $new_status = (int)($_POST["is_active"] ?? -1);

    if ($target_id > 0 && in_array($new_status, [0, 1], true)) {

        $stmt = $conn->prepare("
            UPDATE users
            SET is_active = ?
            WHERE user_id = ?
            AND role = 'student'
        ");

        $stmt->bind_param("ii", $new_status, $target_id);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $message = $new_status === 1
                ? "Student account activated successfully."
                : "Student account deactivated successfully.";
        } else {
            $message = "Unable to update student account.";
        }

        $stmt->close();
    } else {
        $message = "Invalid account information.";
    }
}

// Search students
$search = trim($_GET["search"] ?? "");

if ($search !== "") {

    $searchTerm = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT user_id, full_name, email, is_active
        FROM users
        WHERE role = 'student'
        AND (full_name LIKE ? OR email LIKE ?)
        ORDER BY user_id DESC
    ");

    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();

    $students = $stmt->get_result();

} else {

    $students = $conn->query("
        SELECT user_id, full_name, email, is_active
        FROM users
        WHERE role = 'student'
        ORDER BY user_id DESC
    ");
}

// Account statistics
$activeCount = 0;
$inactiveCount = 0;

$result = $conn->query("
    SELECT
        COALESCE(SUM(is_active = 1), 0) AS active_count,
        COALESCE(SUM(is_active = 0), 0) AS inactive_count
    FROM users
    WHERE role = 'student'
");

if ($result) {
    $stats = $result->fetch_assoc();
    $activeCount = $stats["active_count"];
    $inactiveCount = $stats["inactive_count"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SkillExchange Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">

    <h1>Student User Management</h1>

    <p>View and manage student accounts.</p>

    <p>
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </p>

    <hr>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h2>Account Statistics</h2>

    <div class="dashboard-cards">

        <div class="card">
            <h3>Active Accounts</h3>
            <p><?php echo $activeCount; ?></p>
        </div>

        <div class="card">
            <h3>Deactivated Accounts</h3>
            <p><?php echo $inactiveCount; ?></p>
        </div>

    </div>

    <hr>

    <h2>Search Students</h2>

    <form method="GET" action="admin_users.php">

        <input
            type="text"
            name="search"
            placeholder="Search by name or email"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">Search</button>

        <a href="admin_users.php">Clear Search</a>

    </form>

    <hr>

    <h2>Registered Student Accounts</h2>

    <table>

        <thead>
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Account Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($students && $students->num_rows > 0): ?>

            <?php while ($student = $students->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($student["user_id"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($student["full_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($student["email"]); ?>
                    </td>

                    <td>
                        <?php echo ((int)$student["is_active"] === 1)
                            ? "Active"
                            : "Deactivated"; ?>
                    </td>

                    <td>

                        <form method="POST" action="admin_users.php">

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?php echo (int)$student["user_id"]; ?>"
                            >

                            <?php if ((int)$student["is_active"] === 1): ?>

                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                >

                                <button type="submit">
                                    Deactivate
                                </button>

                            <?php else: ?>

                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="1"
                                >

                                <button type="submit">
                                    Activate
                                </button>

                            <?php endif; ?>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="5">No student accounts found.</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>

</body>
</html>
