<?php

include 'config.php';

try {
    // Fetch products and their promotions
    $sql = "
        SELECT p.id, p.name, p.sku, p.unit_price, 
               pm.promotion_type, pm.quantity, pm.free_quantity, 
               pm.promo_price, pm.meal_deal_combination, 
               pm.valid_from, pm.valid_until
        FROM products p
        LEFT JOIN promotions pm ON p.id = pm.product_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    
    $productsWithPromotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error fetching products: " . $e->getMessage();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            height: 100%;
        }
    </style>
</head>
<body>

<div class="container mt-5">
 
    <div class="d-flex justify-content-between mb-4">
       
        <div>
        <?php
            session_start();
            $totalItems = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
            ?>
             <h5>Total Items Scanned: <span id="cartCount"><?= $totalItems; ?></span></h5>
            <a href="checkout.php" class="btn btn-sm btn-primary">Go to Checkout</a>
            <button onclick="clearCart()" class="btn btn-sm btn-warning">Clear Cart</button>
        </div>
    </div>
    <h2 class="text-center mb-4">Product List</h2>
    <div class="row">
        <?php 
       
function formatDateListing($date) {
    return date('jS F Y', strtotime($date)); 
}

        ?>
        <?php foreach ($productsWithPromotions as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card d-flex flex-column">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">SKU: <?php $product['sku']; ?></h6>
                        <p>Price: &pound;<?php echo $product['unit_price']; ?></p>

                     
                        <?php

$currentDate = new DateTime(); 

if ($product['promotion_type'] === 'multiprice') {

    $validFrom = new DateTime($product['valid_from']);
    $validUntil = new DateTime($product['valid_until']);

    if ($validUntil < $currentDate) {
        
        echo '<span class="badge bg-secondary me-2">No offers available</span><br>';
    } else {
        
        echo '<span class="badge bg-success me-2">';
        echo "MultiPrice offer: Buy " . $product['quantity'] . " for &pound;" . $product['promo_price']. ".";
        echo "</span><br>";
        echo "<small class='text-muted'>Valid from " . formatDateListing($product['valid_from']) . " to " . formatDateListing($product['valid_until']) . ".</small>";
    }

} elseif ($product['promotion_type'] === 'buy_get_free') {

    $validFrom = new DateTime($product['valid_from']);
    $validUntil = new DateTime($product['valid_until']);

    if ($validUntil < $currentDate) {
      
        echo '<span class="badge bg-secondary me-2">No offers available</span><br>';
    } else {
        
        echo '<span class="badge bg-info me-2">';
        echo "Buy " . $product['quantity'] . ", get " . $product['free_quantity'] . " free.";
        echo "</span><br>";
        echo "<small class='text-muted'>Valid from " . formatDateListing($product['valid_from']) . " to " . formatDateListing($product['valid_until']) . ".</small>";
    }

} elseif ($product['promotion_type'] === 'meal_deal') {

    $validFrom = new DateTime($product['valid_from']);
    $validUntil = new DateTime($product['valid_until']);

    if ($validUntil < $currentDate) {
        
        echo '<span class="badge bg-secondary me-2">No offers available</span><br>';
    } else {
        
        echo '<span class="badge bg-warning text-dark me-2">';
        echo "Meal Deal: " .$product['meal_deal_combination']. " for &pound;" . $product['promo_price'] . ".";
        echo "</span><br>";
        echo "<small class='text-muted'>Valid from " . formatDateListing($product['valid_from']) . " to " . formatDateListing($product['valid_until']) . ".</small>";
    }

} else {
    
    echo '<span class="badge bg-secondary me-2">No offers available</span><br>';
}
?>



        <button class="btn btn-sm btn-success mt-3" onclick="scanProduct(<?php echo $product['id']; ?>)">Scan Product</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// AJAX CALL TO SCAN EACH PRODUCT
function scanProduct(productId) {
    $.ajax({
        type: 'POST',
        url: 'add_to_cart.php', 
        data: { id: productId },
        dataType: 'json', 
        success: function(response) {
            if (response.error) {
                alert(response.error); 
            } else {
                alert(response.message); 
                $('#cartCount').text(response.totalItems);
            }
        }
    });
}

// AJAX CALL TO CLEAR CART
function clearCart() {
    $.ajax({
        type: 'POST',
        url: 'clear_cart.php', 
        dataType: 'json',
        success: function(response) {
            alert(response.message); 
            $('#cartCount').text(response.totalItems); 
        }
    });
}
</script>
</body>
</html>