<?php
require 'includes/db.php';

$result = pg_query($conn,"SELECT email,role FROM users");

while($row = pg_fetch_assoc($result)){
    echo $row['email']." - ".$row['role']."<br>";
}