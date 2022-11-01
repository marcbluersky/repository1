<?php
// webhook.php
//
// Use this sample code to handle webhook events in your integration.
//
// 1) Paste this code into a new file (webhook.php)
//
// 2) Install dependencies
//   composer require stripe/stripe-php
//
// 3) Run the server on http://localhost:4242
//   php -S localhost:4242




require 'vendor/autoload.php';
include 'class.php';

Logs::addM(" hit -> stripe_webhooks.php");

// This is your Stripe CLI webhook secret for testing your endpoint locally.
$endpoint_secret = '<your_end_point_secret>';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
  $event = \Stripe\Webhook::constructEvent(
    $payload, $sig_header, $endpoint_secret
  );
  Logs::addM("create event");
} catch(\UnexpectedValueException $e) {
  // Invalid payload
  Logs::addM("UnexpectedValueException");
  http_response_code(400);
  exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
  // Invalid signature
  Logs::addM("SignatureVerificationException");
  http_response_code(400);
  exit();
}

Logs::addM("event type: ".$event->type);
// Handle the event
switch ($event->type) {
  case 'checkout.session.async_payment_succeeded':
    $session = $event->data->object;
  case 'checkout.session.completed':
    $session = $event->data->object;
  case 'checkout.session.async_payment_failed':
    $session = $event->data->object;
  case 'payment_intent.payment_failed':
    $session = $event->data->object;	
  case 'payout.failed':
    $session = $event->data->object;	
  // ... handle other event types
  default:
    echo 'Received unknown event type ' . $event->type;
}

$id = $event->data->object->id;
Logs::addM("id:".$id);
$tx = $id;


if ( // failed workflow
($event->type == "checkout.session.async_payment_failed")
or ($event->type == "payment_intent.payment_failed")
or ($event->type == "payout.failed")
)

{
	Logs::addM("payment failed");
	$amount = $event->data->object->amount;
	$amt = substr($amount,0,strlen($amoun)-2).".".substr($amount,-2);
	Logs::addM("amount:".$amount);
	Logs::addM("amt:".$amt);
	$email= $event->data->object->charges->data[0]->billing_details->email;
	//$email = "marc.bluersky@gmail.com";
	Logs::addM("email:".$email);
	$phone= $event->data->object->charges->data[0]->billing_details->phone;
	Logs::addM("phone:".$phone);	
	$failure_message = $event->data->object->charges->data[0]->failure_message;
	Logs::addM("failure_message:".$failure_message);
	$message ="Bonjour,<br> le paiement que vous avez essayé d'effectuer pour un montant de $amt euros n'est pas passé.
	Il a retourné le message d'erreur suivant : $failure_message
	<br>Vous pouvez réessayer avec une autre carte ou bien faire un virement sur le compte suivant. 
	<br>Si vous effectuez un virement, merci d'indiquer votre email en commentaire du virement pour que je fasse le nécessaire dès réception.
	<br>Voici l'IBAN:<br>".Properties::$iban."<br>";
	Mail::generic("Paiement rejeté",$message,$email);
	
	
}
else{ // success workflow
	$amount = $event->data->object->amount_total;
	$amt = substr($amount,0,strlen($amoun)-2).".".substr($amount,-2);
	Logs::addM("amount:".$amount);
	Logs::addM("amt:".$amt);
	$email= $event->data->object->customer_details->email;
	Logs::addM("email:".$email);
	$phone= $event->data->object->customer_details->phone;
	Logs::addM("phone:".$phone);
	$client_name= $event->data->object->customer_details->name;
	Logs::addM("client_name:".$client_name);
	$source = "stripe";
	$product_obj = new Product($amt);
	$product_key = $product_obj->product_key;
	$product_name = $product_obj->name;
	$product_page = $product_obj->page;
	$server = $product_obj->server;
	$product_type = $product_obj->type;
	$url = $server."/your_stripe_confirmation.php?source=$source&tx=$tx&amt=$amt&email=$email&client_name=".urlencode($client_name)."&phone=".urlencode($phone)."&product_key=$product_key&product_name=".urlencode($product_name)."&product_page=$product_page&product_type=$product_type";
	curlIt($url);
}

http_response_code(200);

?>