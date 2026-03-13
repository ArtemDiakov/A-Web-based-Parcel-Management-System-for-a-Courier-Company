<?php

$password = password_hash("test123", PASSWORD_DEFAULT);

echo $password;