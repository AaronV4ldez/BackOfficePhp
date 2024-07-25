<?php


declare(strict_types=1);
require __DIR__ . "/bootstrap.php";

// $stripe = new \Stripe\StripeClient("sk_test_51HsXqzBjDwMlL5QS2PJlgF0qIhgOOQNekAvhVPYZtCphg1zhN48qj7SIxyyvqqxpQxAbm6s6oTHfBSxjupmEfceS00m7jfG0Tb");
// $product = $stripe->products->create([
//    'name' => 'test product',
//    'description' => '$12/Month subscription',
//  ]);
// echo "Success! Here is your starter subscription product id: " . $product->id . "\n";

// $price = $stripe->prices->create([
//   'unit_amount' => 1200,
//   'currency' => 'mxn',
//   'recurring' => ['interval' => 'month'],
//   'product' => $product['id'],
// ]);
// echo "Success! Here is your premium subscription price id: " . $price->id . "\n";


// INTENT------ This is your test secret API key.
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_TEST']);

header('Content-Type: application/json');

try {
    // Create a PaymentIntent with amount and currency
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => 100,
        'currency' => 'mxn',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);

    $output = [
        'clientSecret' => $paymentIntent->client_secret,
    ];

    echo json_encode($output);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}


// $stripe = new \Stripe\StripeClient("sk_test_51HsXqzBjDwMlL5QS2PJlgF0qIhgOOQNekAvhVPYZtCphg1zhN48qj7SIxyyvqqxpQxAbm6s6oTHfBSxjupmEfceS00m7jfG0Tb");


