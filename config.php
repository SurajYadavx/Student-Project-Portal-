<?php
// config.php
// Database settings
$host = 'sql207.infinityfree.com';
$db   = 'if0_39683668_studproj02';
$user = 'if0_39683668';
$pass = 'q3VFcEcKg1qr1aZ';
$port = 3306; // your MySQL port

// PDO connection
try {
  $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  die("DB Error: " . $e->getMessage());
}

session_start();

function isLoggedIn() { return isset($_SESSION['user_id']); }
function isStudent()  { return isset($_SESSION['role']) && $_SESSION['role']==='student'; }
function isProfessor(){ return isset($_SESSION['role']) && $_SESSION['role']==='professor'; }
function redirect($url){ header("Location: $url"); exit(); }
?>