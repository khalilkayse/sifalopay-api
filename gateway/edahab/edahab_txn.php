<?php require "edahab_core.php";

/* 
*  First Created: 05 Mar, 2022
*  Author: Khalil
*  Content: Core txns code for eDahab api gateway
*  Gateways: eDahab
*/

function generateGRandomString($length = 10) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function debit_payment($account, $amount, $currency, $merchant_id){
    // generate inv no
    $inv_no = generateGRandomString();

    // process transaction
    $debit_txn = debit_txn($account, $amount, $inv_no, $currency, $merchant_id);
    return $debit_txn;

}

function credit_payment($account, $amount, $currency, $merchant_id){
    // generate inv no
    $txn_id = generateGRandomString();

    // process transaction
    $credit_txn = credit_txn($account, $amount, $txn_id, $currency, $merchant_id);
    return $credit_txn;

}
