<?php

$host = 'localhost';
$db = 'parcel_system';
$user = 'artemdiakov';
$pass = '';
$port = 5432;

$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass");

if (!$conn) {
    die("Database connection failed.");
}