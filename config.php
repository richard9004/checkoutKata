<?php
$host = 'localhost';      
$dbname = 'checkoutkata';   
$username = 'root';       
$password = '';   

try {
   //CREATE CONNECTION
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection errors
    die("Connection failed: " . $e->getMessage());
}
?>
