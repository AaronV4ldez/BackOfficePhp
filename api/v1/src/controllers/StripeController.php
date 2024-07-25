<?php

// StripeController.php
// Funciones para manejo de pagos con Stripe
// LineaExpressApp

namespace Controllers;

class StripeController extends BaseController
{
    public function createIntent()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(['amount'], $params);

        $amount = \is_numeric($params['amount']) ? $params['amount'] : \floatval($params['amount']);

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET']);

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'mxn',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'client_secret' => $paymentIntent->client_secret,
                'pi' => $paymentIntent->id,
                'publishableKey' => $_ENV['STRIPE_PUB'],
            ];

            echo json_encode($output);
        } catch (Error $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function confirmPayment()
    {
        $ref = $this->f3->get('PARAMS.ref');

        var_dump($_REQUEST);

        $payment_intent = $_REQUEST["payment_intent"];
        $payment_intent_client_secret = $_REQUEST["payment_intent_client_secret"];
        $redirect_status = $_REQUEST["redirect_status"];

        if (empty($payment_intent) || empty($payment_intent_client_secret) || empty($redirect_status)) {
            $this->f3->reroute('https://panelweb.fpfch.gob.mx/#/payment/error/' . $payment_intent);
        }

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET']);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent);
        $responseStr = \json_encode($paymentIntent);

        $type = substr($ref, 0, 2);
        if ($type == 'TP' || $type == 'LE') {
            $ref = substr($ref, 2);
        }

        try {
            // $pm = $payment_intent["charges"]["data"][0]["payment_method_details"]["card"]["funding"];
            // \Util\Logger::log("metodo de pago: $pm");
        } catch (Exception $e) {
            // \Util\Logger::log("no pude sacar metodo de pago: $e->getMessage()");
        }

        // primer caracter de referencia es tipo de pago, L=linea express, T=telepeaje
        $stripeRow = new \Data\StripeModel();
        $stripeRow->ref = $ref;
        $stripeRow["pi"] = $payment_intent;
        $stripeRow["pi_client_secret"] = $payment_intent_client_secret;
        $stripeRow["redirect_status"] = $redirect_status;
        $stripeRow["amount"] = $paymentIntent["amount"] / 100;
        $stripeRow["amount_received"] = $paymentIntent["amount_received"] / 100;
        $stripeRow["canceled_at"] = $paymentIntent["canceled_at"];
        $stripeRow["cancellation_reason"] = $paymentIntent["cancellation_reason"];
        $stripeRow->save();

        $stripeLog = new \Data\StripeLogModel();
        $stripeLog["pi"] = $payment_intent;
        $stripeLog["response_text"] = $responseStr;
        $stripeLog->save();

        $q = $paymentIntent["amount_received"] / 100;
        \Util\Logger::log("Recarga de saldo de $ref por $q");

        if ($type == 'TP' && strtolower($redirect_status) == 'succeeded') {
            \Util\TPApi::recargaSaldo($ref, $q, $payment_intent);
        }
        if ($type == "LE" && strtolower($redirect_status) == 'succeeded') {
            // update linea express
            $res = \Util\LEApi::recargaSaldo($ref, $q);
        }

        $tag = new \Data\VehiclesModel();
        $tag->load(["tag = ?", $ref]);
        $tag["saldo"] = $tag["saldo"] + $paymentIntent["amount_received"];
        $tag->save();
        //Posible error
        $this->f3->reroute('https://panelweb.fpfch.gob.mx/#/payment/confirm/' . $payment_intent);
    }

    public function paymentDetails()
    {
        $this->hasAuthOrDie();

        $ref = $this->f3->get('PARAMS.ref');

        $stripeRow = new \Data\StripeModel();
        $stripeRow->load(["pi = ?", $ref]);

        if ($stripeRow->dry()) {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontró el pago']);
        } else {
            echo json_encode($stripeRow->cast());
        }
    }
}
