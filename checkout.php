<?php
session_start();
include 'config.php'; // Include database connection

class Checkout {
    private $cart;

    public function __construct($cart) {
        $this->cart = $cart;
    }

    public function getCartSummary() {
        $summary = [];
    
        foreach ($this->cart as $product) {
            // Extract product information
            $productId = $product['id'];
            $quantity =  1;
            $unitPrice = (float)$product['unit_price'];
    
           
            $totalAmount = $unitPrice * $quantity;
    
            // Get promotion info (without any calculations) calculations done in next function
            $promotionType = $product['promotion_type'] ?? null;
            $promoPrice = $product['promo_price'] ?? null;
            $promoQuantity = $product['quantity'] ?? null;
            $freeQuantity = $product['free_quantity'] ?? null;
            $mealDealCombination = $product['meal_deal_combination'] ?? null;
            $validFrom = $product['valid_from'] ?? null;
            $validUntil = $product['valid_until'] ?? null;
    
            // Check if the product already exists in the summary array
            $existingIndex = null;
            foreach ($summary as $index => $item) {
                if ($item['id'] == $productId) {
                    $existingIndex = $index;
                    break;
                }
            }
    
            if ($existingIndex !== null) {
                // If the product exists, update its quantity and total amount
                $summary[$existingIndex]['quantity'] += $quantity;
                $summary[$existingIndex]['total_amount'] += $totalAmount; 
            } else {
                // If the product does not exist, add it to the summary
                $summary[] = [
                    'id' => $productId,
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'total_amount' => $totalAmount,
                    'promotion_type' => $promotionType,
                    'promo_price' => $promoPrice,
                    'promo_quantity' => $promoQuantity,
                    'free_quantity' => $freeQuantity,
                    'meal_deal_combination' => $mealDealCombination,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                ];
            }
        }
    
        return $summary;
    }



     // Calculate offers and return total price after promotions
     public function calculateOffers($cartSummary) {
        $totalPrice = 0;
        $deductions = [];
        $meal_deals_array = [];

        foreach ($cartSummary as $item) {
            $basePrice = $item['total_amount'];
            $promotionType = $item['promotion_type'];
            $quantity = $item['quantity'];

            $validFrom = new DateTime($item['valid_from']);
            $validUntil = new DateTime($item['valid_until']);
            $currentDate = new DateTime(); 

            if ($validUntil < $currentDate) {
                $promotionType = '';
            }

            
            // BASED ON PROMOTION TYPE, LOGIC ( Pricing Rules )

            switch ($promotionType) {
                case 'buy_get_free':
                    if ($quantity >= $item['promo_quantity']) {
                        $freeItems = (int)($quantity / $item['promo_quantity']) * $item['free_quantity'];
                        $deduction = $freeItems * $item['unit_price'];
                        $deductions[] = [
                            'description' => "Buy and Get Free Offer: $freeItems free items",
                            'amount' => $deduction
                        ];
                        $basePrice -= $deduction; 
                    }
                    break;

                case 'multiprice':
                    if ($quantity >= $item['promo_quantity']) {
                        $sets = (int)($quantity / $item['promo_quantity']);
                        $totalSetPrice = $sets * (float)$item['promo_price'];
                        $deduction = ($item['unit_price'] * $item['promo_quantity'] * $sets) - $totalSetPrice;
                        $deductions[] = [
                            'description' => "MultiPrice Offer: Deductions",
                            'amount' => $deduction
                        ];
                        $basePrice -= $deduction; 
                    }
                    break;

                case 'meal_deal':
                    
                    $meal_deals_array[] = [
                        'sku' => $item['sku'], 
                        'quantity' => $quantity,
                        'promo_price' => $item['promo_price'],
                        'valid_from' => $item['valid_from'],
                        'valid_until' => $item['valid_until'],
                        'unit_price' => $item['unit_price'],
                        'total_amount' => $item['total_amount'],
                        'meal_deal_combination' => str_replace(' ', '', $item['meal_deal_combination']), // Remove spaces
                    ];
                    break;   

              

                default:
                    
                    break;
            }

            $totalPrice += $basePrice; 
        }

        if (!empty($meal_deals_array)) {
            // Meal Deals promotion executed in this function below
            //Updated deductions based on mealdeals of any
           $deductions =  $this->calculateMealDealDiscounts($meal_deals_array, $deductions);
        }

     

        return [
            'total_price' => $totalPrice,
            'deductions' => $deductions,
        ];
    }


    private function calculateMealDealDiscounts($mealDeals, $deductions) {
       
        
        $mealDealCombos = [];
    
       
        foreach ($mealDeals as $deal) {
            $combination = $deal['meal_deal_combination'];
            $sku = $deal['sku'];
            $quantity = $deal['quantity'];
            $promoPrice = $deal['promo_price'];
    
            
            if (!isset($mealDealCombos[$combination])) {
                $mealDealCombos[$combination] = [
                    'total_price' => 0,
                    'expected_quantity' => 0,
                    'promo_price' => $promoPrice,
                ];
            }
    
            
            $mealDealCombos[$combination]['total_price'] += $deal['unit_price'] * $quantity;
            $mealDealCombos[$combination]['expected_quantity'] += $quantity;
        }

       
    
        
        foreach ($mealDealCombos as $combination => $info) {
           
            $items = explode(', ', $combination);
            $requiredQuantity = count($items);
    
           
            if ($info['expected_quantity'] >= $requiredQuantity) {
                $discount = $info['total_price'] - $info['promo_price'];
                if ($discount > 0) {
                    $deductions[] = [
                        'description' => "Meal Deal Discount for combination '$combination'",
                        'amount' => $discount,
                    ];
                } 
            }
        }

        return $deductions;
    }
    
    
    
}


$cartIds = $_SESSION['cart'] ?? []; // Get cart IDs (array of product IDs)

foreach ($cartIds as $productId) {
   
    $sql = "SELECT p.*, pm.promotion_type, pm.promo_price, pm.quantity, 
                   pm.free_quantity, pm.meal_deal_combination, 
                   pm.valid_from, pm.valid_until
            FROM products p
            LEFT JOIN promotions pm ON p.id = pm.product_id
            WHERE p.id = :id"; 

   
    $stmt = $conn->prepare($sql);

    
    $stmt->execute(['id' => $productId]);

    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($product) {
        $products[] = $product; 
    } else {
        echo "Product ID $productId not found.";
    }
}

// Ensure products are available for checkout
if (empty($products)) {
    echo "No products found in the cart.";
    exit;
}



$checkout = new Checkout($products);

// Get cart summary function, initial function 
$cartSummary = $checkout->getCartSummary();

//Passed returned cartSummary array to calculate offers/ promotions
$result = $checkout->calculateOffers($cartSummary); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1 class="mb-4">Checkout</h1>

    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>Product Name</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalAmount = 0; 
            $totalDiscount = 0; 

            foreach ($cartSummary as $item): 
                $totalAmount += $item['total_amount']; 
            ?>
            <tr>
                <td><?php echo $item['name']; ?></td>
                <td>&pound;<?php echo number_format($item['unit_price'], 2); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>&pound;<?php echo number_format($item['total_amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (!empty($result['deductions'])): ?>
                <?php foreach ($result['deductions'] as $deduction): ?>
                <tr class="table-success">
                    <td><span class="badge bg-success me-2">
                    <?php echo $deduction['description']; ?></span></td>
                    <td colspan="3">&pound;-<?php echo number_format($deduction['amount'], 2); ?></td>
                    <?php $totalDiscount += $deduction['amount'];  ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="4">No offers applied.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="3">Subtotal (Before Discounts)</td>
                <td>&pound;<?php echo number_format($totalAmount, 2); ?></td>
            </tr>
            <tr class="fw-bold">
                <td colspan="3">Total Discounts</td>
                <td>&pound;-<?php echo number_format($totalDiscount, 2); ?></td>
            </tr>
            <tr class="fw-bold">
                <td colspan="3">Final Total Cost</td>
                <td>&pound;<?php echo number_format($result['total_price'], 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
