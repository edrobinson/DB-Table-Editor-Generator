<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sddb";

use ezsql\Database;

$db = Database::initialize('pdo', ["mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password]);

if(!$db)
{
  echo 'Database setup failed';
  exit();
}  