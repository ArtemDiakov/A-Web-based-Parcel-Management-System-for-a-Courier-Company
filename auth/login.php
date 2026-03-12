<?php

session_start();
require '../includes/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT id, password_hash, role 
          FROM users 
          WHERE email = $1 
          LIMIT 1";

$result = pg_query_params($conn, $query, array($email));

$user = pg_fetch_assoc($result);

if ($user && password_verify($password, $user['password_hash'])) {

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    header("Location: /index.php");
    exit;

} else {

    echo "Invalid email or password.";

}