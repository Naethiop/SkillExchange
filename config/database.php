
<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$username = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'skill_exchange_db';
$port = (int) (getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);

$conn = @new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(503);
    exit('The application database is unavailable. Check the database service configuration.');
}

$conn->set_charset('utf8mb4');
