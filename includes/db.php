<?php

$host = 'db.dcs.aber.ac.uk';
$db = 'cs27020_24_25_ard38';
$user = 'ard38';
$pass = '**********';
$port = 5432;

$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass");

if (!$conn) {
    die("Database connection failed.");
}