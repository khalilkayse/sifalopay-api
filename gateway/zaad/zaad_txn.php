<?php require "zaad_core.php";

/* 
*  First Created: 11 Dec, 2021
*  Author: Khalil
*  Content: Core txns code for zaad api gateway
*  Gateways: Zaad
*/
function zaad_globals(){
    $zaad_globals = array(
        "succ_code" => "2001");
        return $zaad_globals;
    }

function debit_payment($account, $amount, $currency){
    // generate id's
    $ref_id = generateGRandomString();
    $req_id = generateGRandomString();
    $inv_id = generateGRandomString();

    // process transaction
    $preAuth_response = json_decode(
        preAuth_pay(
        $req_id, // req id
        $account, // account no
        $ref_id, // ref id
        $inv_id, // inv id
        $amount, // amount
        $currency), true);
        
     $re_code = $preAuth_response['responseCode'];
     $re_msg = $preAuth_response['responseMsg']; 

     if($re_code == zaad_globals()['succ_code']){
         // commit transaction
        $txn_id = $preAuth_response['params']['transactionId'];
        $commit_response = json_decode(preAuth_commit(
            $req_id, // req id
            $txn_id, // txn id
            $ref_id), true);
            // return commit response from api
            return $commit_response;
     } else {
         // return full response from api
         return $preAuth_response;
     }

}

function credit_payment($account, $amount, $currency, $account_type){
    // generate id's
    $ref_id = generateGRandomString();
    $req_id = generateGRandomString();
    $inv_id = generateGRandomString();

     // process transaction
     $credit_account = json_decode(
        credit_account(
        $req_id, // req id
        $account, // account no
        $ref_id, // ref id
        $inv_id, // inv id
        $amount, // amount
        $currency,
        $account_type
    ), true);
        
     $re_code = $credit_account['responseCode'];
     $re_msg = $credit_account['responseMsg']; 

     if($re_code == zaad_globals()['succ_code']){
         // return response
         return $credit_account;
     } else {
         // display response msg
        return $credit_account;
     }
}

