<?php
session_start();

if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']); 
}


$response = [
    'message' => 'Cart cleared successfully!',
    'totalItems' => 0 
];

header('Content-Type: application/json');
echo json_encode($response);
?>
