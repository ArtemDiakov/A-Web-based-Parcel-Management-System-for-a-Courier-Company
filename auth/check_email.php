<?php

header('Content-Type: application/json');
require '../includes/db.php';

$email = $_POST['email'];

$query = "SELECT id FROM users WHERE email=$1 LIMIT 1";

$result = pg_query_params($conn,$query,array($email));

$user = pg_fetch_assoc($result);

echo json_encode([
"exists" => $user ? true : false
]);