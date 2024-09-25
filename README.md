
CheckoutKata ReadMe
Introduction
This ReadMe file provides a step-by-step guide to set up and use the CheckoutKata project, including database configuration, product management, and the checkout process.

Step 1: Database Configuration
To begin, you need to configure the database connection in the config.php file. This file contains the following details for connecting to your MySQL database:

$host: Set this to "localhost" if you're working on a local machine. If your database is hosted remotely, replace it with the correct server address.
$dbname: Replace 'checkoutkata' with the name of your actual database.
$username: Provide your MySQL username.
$password: Set your MySQL password here.
Ensure that the database is properly configured with these details before moving forward.

Step 2: Running the Application
Viewing Products
Once the database is configured, navigate to your application's domain or directory root (e.g., https://checkoutkata.getmakedigital.com/). Here, you will see a list of available products. These products are listed based on their prices and any applicable promotional offers.

Managing Products
To add new products, go to the admin.php file (e.g., https://checkoutkata.getmakedigital.com/admin.php). This admin page allows you to add new products and set their pricing. Note: There is currently no option to edit existing products.

Adding Products to the Cart
You can add products to the cart by clicking on individual product entries. Since there's no option to select a quantity, each click adds one unit of the product to the cart. You will receive an alert each time a product is added. To increase the quantity of a product, simply click on it multiple times.

Cart Summary
At the top of the page, the header will display:

The Total Items Scanned
A Go to Checkout button
A Clear Cart button
Checkout Process
To complete your purchase, click on the Go to Checkout button. This will take you to the checkout page, where you will see:

A list of products in your cart, along with their quantities and total price.
An Offers section displaying any promotions applied.
A breakdown of the Sub Total, Deductions, and the final total Cost.
The main checkout logic is implemented in the Checkout.php file. This file contains the calculations for pricing, offers, and final cost.