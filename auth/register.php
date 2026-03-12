<?php

session_start();
require '../includes/db.php';

$name = $_POST['full_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];

$hash = password_hash($password, PASSWORD_DEFAULT);

$query = "INSERT INTO users
(full_name, email, phone, password_hash, role, created_at, is_active)
VALUES ($1,$2,$3,$4,'customer',NOW(),true)
RETURNING id";

$result = pg_query_params($conn,$query,array(
$name,
$email,
$phone,
$hash
));

$user = pg_fetch_assoc($result);

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = 'customer';

header("Location: /index.php");
exit;