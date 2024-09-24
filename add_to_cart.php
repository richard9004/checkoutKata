<?php
session_start();
include 'config.php';
header('Content-Type: application/json'); 

$response = []; 

if (isset($_POST['id'])) {
    $productId = $_POST['id'];
    
    // Add product ID to session cart directly
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = []; 
    }

  
        $_SESSION['cart'][] = $productId; 
        $response['message'] = "Product added to cart!";
   
    
    $response['totalItems'] = count($_SESSION['cart']); 
    $response['cart'] = $_SESSION['cart']; 
} else {
    $response['error'] = "No product ID provided.";
}


echo json_encode($response);
?>
