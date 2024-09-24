<?php

include 'config.php';

$name = "";
$sku = "";
$price = "";
$promotion_type = "";
$quantity = "";
$promo_price = "";
$meal_deal_combination = "";
$valid_from = NULL;
$valid_until = NULL;
$successMessage = "";
$free_quantity="";
$errorMessage = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from form
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $price = $_POST['price'];
    $promotion_type = $_POST['promotion_type'];
    if($promotion_type!=''){
        $quantity = $_POST['quantity'];
        $free_quantity =  $_POST['free_quantity'];
        $promo_price = $_POST['promo_price'];
        $meal_deal_combination = $_POST['meal_deal_combination'];
        $valid_from = $_POST['valid_from'];
        $valid_until = $_POST['valid_until'];
    }
    //echo $valid_from.' '.$valid_until;exit;
  

    
    if (!empty($name) && !empty($sku) && !empty($price)) {
        try {
            // Insert product into database
            $sql = "INSERT INTO products (name, sku, unit_price) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $sku, $price]);

            // Get the last inserted product ID for promotions
            $product_id = $conn->lastInsertId();

           
                $sql = "INSERT INTO promotions (product_id, promotion_type, quantity, free_quantity, promo_price, meal_deal_combination, valid_from, valid_until) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$product_id, $promotion_type, $quantity, $free_quantity, $promo_price, $meal_deal_combination, $valid_from, $valid_until]);
            

            $successMessage = "Product added successfully!";
        } catch (PDOException $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<div class="container mt-5">
    <h2 class="text-center mb-4">Admin - Add New Product</h2>

   
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= $successMessage; ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= $errorMessage; ?></div>
    <?php endif; ?>

   
    <button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
        Add New Product
    </button>

    <!-- Product List -->
    <h3 class="mt-4">Product List</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
              
                <th>Name</th>
                <th>SKU</th>
                <th>Unit Price</th>
                <th>Promotion Type</th>
            <th>Promotional Quantity</th>
            <th>Free Quantity</th>
            <th>Promo Price</th>
            <th>Valid From</th>
            <th>Valid Until</th>
            <th>Promotion Status</th>
            
            </tr>
        </thead>
        <tbody>
            <?php
           // Fetch all products with their promotional data
$sql = "SELECT p.id, p.name, p.sku, p.unit_price, 
pr.promotion_type, pr.quantity, pr.free_quantity, pr.promo_price, 
pr.meal_deal_combination, pr.valid_from, pr.valid_until
FROM products p
LEFT JOIN promotions pr ON p.id = pr.product_id"; 

$result = $conn->query($sql);

if ($result->rowCount() > 0) {
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
echo "<tr>";

echo "<td>" . $row['name'] . "</td>";
echo "<td>" . $row['sku'] . "</td>";
echo "<td>&pound;" . $row['unit_price'] . "</td>";
// Fetch promotion type
$promotionType = $row['promotion_type'] ?: 'No Promotion'; 


if ($promotionType === 'multiprice') {
    $promotionTypeReadable = 'Multi Price Offer';
} elseif ($promotionType === 'buy_get_free') {
    $promotionTypeReadable = 'Buy Get Free';
} elseif ($promotionType === 'meal_deal') {
    $promotionTypeReadable = 'Meal Deal'.' ('.$row['meal_deal_combination'].')';
} else {
    $promotionTypeReadable = 'No Promotion'; 
}


echo "<td>" . $promotionTypeReadable . "</td>";
echo "<td>" . ($row['quantity'] ?: '<span class="badge bg-secondary">N/A</span>') . "</td>"; 
echo "<td>" . ($row['free_quantity'] ?: '<span class="badge bg-secondary">N/A</span>') . "</td>"; 
echo "<td>" . (!empty($row['promo_price']) ? '&pound;' . $row['promo_price'] : '<span class="badge bg-secondary">N/A</span>') . "</td>";

 // Check for null values and format valid dates
if (!empty($row['valid_from'])) {
    $validFromFormatted = (new DateTime($row['valid_from']))->format('d/m/Y');
} else {
    $validFromFormatted = "<span class='badge bg-secondary'>N/A</span>";
}

if (!empty($row['valid_until'])) {
    $validUntilFormatted = (new DateTime($row['valid_until']))->format('d/m/Y');
} else {
    $validUntilFormatted = "<span class='badge bg-secondary'>N/A</span>";
}

// Output the formatted dates
echo "<td>" . $validFromFormatted. "</td>";
echo "<td>" . $validUntilFormatted. "</td>";

// Check if the promotion is active
$currentDate = new DateTime();

if ($validUntilFormatted === 'N/A' || $validFromFormatted === 'N/A') {
    echo "<td><span class='badge bg-secondary'>N/A</span></td>"; 
} else {
    $validFrom = new DateTime($row['valid_from']);
    $validUntil = new DateTime($row['valid_until']);

    if ($validUntil < $currentDate) {
        echo "<td><span class='badge bg-danger'>Expired</span></td>"; 
    } else {
        echo "<td><span class='badge bg-success'>Active</span></td>"; 
    }
}
echo "</tr>";
}
} else {
echo "<tr><td colspan='7'>No products found.</td></tr>";
}

            ?>
        </tbody>
    </table>
</div>

<!-- MODAL ADD PRODUCT -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               
                <form method="POST" action="admin.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sku" class="form-label">Product SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" placeholder="Enter SKU (A, B, C, etc.)" required>
                        </div>
                    </div>
                    
                    <div class="row">
                    <div class="col-md-6 mb-3">
    <label for="price" class="form-label">Price (in pounds)</label>
    <input type="number" class="form-control" id="price" name="price" placeholder="Enter price in pounds" step="0.01" required>
</div>
                        <div class="col-md-6 mb-3">
                         <label for="promotion_type" class="form-label">Promotion Type</label>
    <select class="form-select" id="promotion_type" name="promotion_type">
        <option value="multiprice">MultiPrice</option>
        <option value="buy_get_free">Buy n Get n Free</option>
        <option value="meal_deal">Meal Deal</option>
        <option value="" selected>None</option>
    </select>
</div>
                    </div>

                     <div class="promotional_block" style="display: none;">
                     <h5>Promotion Details</h5>


<div class="row">
<div class="col-md-6 mb-3 show_buy_n_get_free show_multiprice" style="display: none;">
<label for="quantity" class="form-label">Promotion Quantity</label>
<input type="number" class="form-control" id="quantity" name="quantity" placeholder="e.g., 3">
</div>
<div class="col-md-6 mb-3 show_buy_n_get_free" style="display: none;">
<label for="free_quantity" class="form-label">Free Quantity</label>
<input type="number" class="form-control" id="free_quantity" name="free_quantity" placeholder="e.g., 1">
</div>
<div class="col-md-6 mb-3 show_multiprice show_meal_deal" style="display: none;">
    <label for="promo_price" class="form-label">Promotion Price (in pounds)</label>
    <input type="number" class="form-control" id="promo_price" name="promo_price" placeholder="Enter promotion price in pounds" step="0.01">
</div>

<div class="col-md-6 mb-3 show_meal_deal" style="display: none;">
        <label for="meal_deal_combination" class="form-label">Meal Deal Combination</label>
        <input type="text" class="form-control" id="meal_deal_combination" name="meal_deal_combination" placeholder="e.g., D, E">
    </div>

    
</div>



<div class="row">
   
    <div class="col-md-6 mb-3" >
        <label for="valid_from" class="form-label">Valid From</label>
        <input type="date" class="form-control" id="valid_from" name="valid_from">
    </div>
    <div class="col-md-6 mb-3">
        <label for="valid_until" class="form-label">Valid Until</label>
        <input type="date" class="form-control" id="valid_until" name="valid_until">
    </div>
</div>
                     </div>


                   
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#promotion_type').change(function() {
            var promotionType = $(this).val();
            if(promotionType==''){
                $('.promotional_block').hide();
            }else{
                $('.promotional_block').show();
            }
            if (promotionType === "buy_get_free") {
                $('.show_buy_n_get_free').show();
            } else {
                $('.show_buy_n_get_free').hide();
            }

            if (promotionType === "multiprice") {
                $('.show_multiprice').show();
            } else {
                
                $('.show_multiprice').hide();

                if(promotionType==="buy_get_free"){
                    $('.show_buy_n_get_free').show();
                }
            }

            if (promotionType === "meal_deal") {
                $('.show_meal_deal').show();
            } else {

                
                $('.show_meal_deal').hide();

                if(promotionType==="multiprice"){
                    $('.show_multiprice').show();
                }

               
            }

            
        });
    });
</script>
</body>
</html>
