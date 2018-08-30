<?php
require __DIR__ . '/vendor/autoload.php';
/**
 * Created by IntelliJ IDEA.
 * User: Thalys
 * Date: 7/28/2018
 * Time: 8:13 PM
 */

// Allow Cross Origin:
header("Access-Control-Allow-Origin: *");

// UTF8:
header("Content-type: text/html; charset=utf-8");

// Display Errors:
error_reporting(E_ALL);

PagSeguroLibrary::init();

$paymentRequest = new PagSeguroPaymentRequest();
$paymentRequest->addItem('0001', 'CAD', 1, 5.00);
$paymentRequest->setCurrency("BRL");

$paymentRequest->setReference("REF123");//todo look this and fix after test
try {
    $credentials = PagSeguroConfig::getAccountCredentials(); // getApplicationCredentials()
    $checkoutUrl = $paymentRequest->register($credentials);

    $parts = parse_url($checkoutUrl);
    parse_str($parts['query'], $query);
//    echo $query['code'];
    echo $checkoutUrl;

} catch (PagSeguroServiceException $e) {
    die($e->getMessage());
}