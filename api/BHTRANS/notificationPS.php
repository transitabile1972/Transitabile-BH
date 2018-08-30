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

//try {
//    $credentials = PagSeguroConfig::getAccountCredentials(); // getApplicationCredentials()
//    $response = PagSeguroNotificationService::checkTransaction(
//        $credentials,
//        $notificationCode
//    );
//
//    echo "<br><br>";
//    echo $response;
//
//} catch (PagSeguroServiceException $e) {
//    die($e->getMessage());
//}

try {

    $credentials = PagSeguroConfig::getAccountCredentials(); // getApplicationCredentials()
    $response = PagSeguroTransactionSearchService::searchByCode(
        $credentials,
        $transactionCode
    );

    echo "<br><br>";
    echo $response;

} catch (PagSeguroServiceException $e) {
    die($e->getMessage());
}