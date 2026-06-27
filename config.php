<?php

$dbname = 'h390436_bot'; //  Name Database
$usernamedb = 'h390436_bot'; // Username Database
$passworddb = 'Armin_13889090a'; // Password Database
$connect = mysqli_connect("localhost", $usernamedb, $passworddb, $dbname);
if ($connect->connect_error) {
    die("The connection to the database failed:" . $connect->connect_error);
}
mysqli_set_charset($connect, "utf8mb4");

$APIKEY = "8636297617:AAF4Y9UeLXQdvHXgnD3kdjBtkDbHXjVBOFY"; // Token Bot of Botfather
$adminnumber = "979194257";// Id Number Admin
$domainhosts = "uploadesho.shop/telegrambot";// Domain Host and Path of Bot without trailing /
$usernamebot = "AriaGuard_bot"; // Username Bot without @

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $usernamedb, $passworddb, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}