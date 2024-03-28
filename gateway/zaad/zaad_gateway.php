<?php require "zaad_db.php"; 
/* 
*  First Created: 11 Dec, 2021
*  Author: Khalil
*  Content: Core gateway code for all systems using this api
*  Description: This file will serve as the middleware for sifalo payment gateway 
*  Gateways: Sifalo Payment Gateway
*/

// this function contains global variables of this api gateway
function gateway_globals(){
    $gateway_globals = array(

        "description" => "Transaction Processed.",
        "zaad_success_code" => "2001");

        return $gateway_globals;
    }
// perform debit txn
function debit_txn($account, $amount, $currency, $merchant_id){
    require("zaad_txn.php");
    // process txn
    return array(
        "account"=>$account,
        "amount"=>$amount,
        "currency"=>$currency,
        "data"=> json_encode(debit_payment($account, $amount, $currency, $merchant_id)));    
}
 // perfom credit txn
function credit_txn($account, $amount, $currency, $account_type, $merchant_id){
    require("zaad_txn.php");
    // process txn
    return array(
        "account"=>$account,
        "amount"=>$amount,
        "currency"=>$currency,
        "account_type"=>$account_type,
        "data"=> json_encode(credit_payment($account, $amount, $currency, $account_type, $merchant_id)));
}

// call this function to run txns and register on DB
function run_txn($txn_type, $account, $amount, $token, $currency, $sid, $account_type){
    // verify token
    if(verify_token($token[0], $token[1]) == 1){

        // get merchant_id
        $merchant_id = get_merchant_id($token[1]);

        // run txn
        switch ($txn_type){
            // if the txn is a debit transaction.
            case "debit":
    
                $txn = debit_txn($account, $amount, $currency, $merchant_id);
                $txn_data = json_decode($txn['data'], true);
    
                // if txn success with no errors process it
                if($txn_data['responseCode'] == gateway_globals()['zaad_success_code']){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = gateway_commission("zaad", $merchant_id); // commission is retrived from core file
                        $commission = round(($commission_percentage / 100) * $txn['amount'], 2); // round to 2 decimal places
						if($commission == 0){ $commission = 0.01; } // default commission fee
                        $total_amount = $txn['amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                                        "txn_date" => time(),
                                        "sid" => $sid,
                                        "txn_id" => $txn_data['params']['transactionId'],
                                        "amount" => $txn['amount'],
                                        "commission_perc" => $commission_percentage,
                                        "commission" => $commission,
                                        "total_amount" => $total_amount,
                                        "payment_type" => "ZAAD",
                                        "currency_type" => $txn['currency'],
                                        "txn_type" => "DEBIT",
                                        "txn_detail" => gateway_globals()['description'],
                                        "txn_status" => strtolower($txn_data['params']['state']),
                                        "merchant_id" => $merchant_id
                                        );
                            // zaad table values
                        $zaad_values = array(
                                            "txn_date" =>$txn_data['timestamp'],
                                            "req_id" => $txn_data['requestId'],
                                            "txn_id" => $txn_data['params']['transactionId'],
                                            "ref_no" => $txn_data['params']['referenceId'],
                                            "account"=> $txn['account'],
                                            "amount" => $txn['amount'],
                                            "currency_type" => $txn['currency'],
                                            "txn_type" => "DEBIT",
                                            "txn_detail" => $txn_data['params']['description']
                                        );

                        // send telegram alert
                        notify($txn['amount'], "debited", $sid, $txn_data['timestamp'], "ZAAD", $txn['account'], $txn['currency']);

                        // return msg
                        $txn_msg = array(1, $txn_data['responseCode'], $txn_data['responseMsg']);
                        
                } else {
                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "amount" => $txn['amount'],
							"account"=> $txn['account'],
                            "total_amount" => $txn['amount'],
                            "payment_type" => "ZAAD",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "DEBIT",
                            "txn_detail" => $txn_data['responseMsg'],
                            "txn_status" => "failed",
                            "merchant_id" => $merchant_id
                            );
                        // set zaad table values to zero to avoid saving it to db
                        $zaad_values = 0;

                        // return msg
                        $txn_msg = array(0, $txn_data['responseCode'], $txn_data['responseMsg']);
                }         
                
                // submit data to DB
                register_payment($txn_values, $zaad_values, "zaad", "debit");

                break;
    
            // if the txn is a credit transaction
            case "credit":
    
                $txn = credit_txn($account, $amount, $currency, $account_type, $merchant_id);
                $txn_data = json_decode($txn['data'], true);
                
                // if txn success with no errors process it
                if($txn_data['responseCode'] == gateway_globals()['zaad_success_code']){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = 0; // actuall commission must be retrived from DB
                        $commission = ($commission_percentage / 100) * $txn['amount'];
                        $total_amount = $txn['amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "txn_id" => $txn_data['params']['transactionId'],
                            "amount" => $txn['amount'],
                            "commission_perc" => $commission_percentage,
                            "commission" => $commission,
                            "total_amount" => $total_amount,
                            "payment_type" => "ZAAD",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "txn_detail" => gateway_globals()['description'],
                            "txn_status" => strtolower($txn_data['params']['state']),
                            "merchant_id" => $merchant_id
                            );
                        // Zaad table values
                    $zaad_values = array(
                            "txn_date" =>$txn_data['timestamp'],
                            "req_id" => $txn_data['requestId'],
                            "txn_id" => $txn_data['params']['transactionId'],
                            "issuer_txn_id" => $txn_data['params']['issuerTransactionId'],
                            "ref_no" => $txn_data['params']['referenceId'],
                            "account"=> $txn['account'],
                            "amount" => $txn['amount'],
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "account_type" => "personal",
                            "account_holder" => "",
                            "account_exp_date" => $txn_data['params']['accountExpDate'],
                            "issuer_approval_code" => $txn_data['params']['issuerApprovalCode'] ?? null,
                            "merchant_charges" => $txn_data['params']['merchantCharges'],
                            "customer_charges" => $txn_data['params']['customerCharges']
                            );

                        // send alert on telegram
                        notify($txn['amount'], "credited", $sid, $txn_data['timestamp'], "ZAAD", $txn['account'], $txn['currency']);

                        // save data on DB
                        register_payment($txn_values, $zaad_values, "zaad", "credit");

                        // return msg
                        $txn_msg = array(1, $txn_data['responseCode'], $txn_data['responseMsg']);

                }else{
                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "amount" => $txn['amount'],
							"account"=> $txn['account'],
                            "total_amount" => $txn['amount'],
                            "payment_type" => "ZAAD",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "txn_detail" => $txn_data['responseMsg'],
                            "txn_status" => "failed",
                            "merchant_id" => $merchant_id
                            );
                        // set zaad table values to zero to avoid saving it to db
                        $zaad_values = 0;

                        // save data on DB
                        register_payment($txn_values, $zaad_values, "zaad", "credit");

                        // return msg
                        $txn_msg = array(0, $txn_data['responseCode'], $txn_data['responseMsg']); 
                }                        

                break;
        
        }

        // save txn on the logs
        log_this(
            array(
                "sid"=>$sid,
                "account"=>$txn['account'],
                "amount"=>$txn['amount'],
                "currency"=>$txn['currency'],
                "gateway"=>$txn_values['payment_type'],
                "txn_type"=>$txn_values['txn_type'],
                "merchant_id"=>$txn_values['merchant_id'],
                "txn_detail"=>$txn_values['txn_detail']
            ), "txn" // log type
        );

        return $txn_msg;

    } else {
        // if token is invalid
        return array(0, 00, "invalid token");
    }
}
// this is goning to be the main api the applications have to communicate with 
// run_txn("debit");

/*
*
* It expects the following values:
* - txn type
* - txn amount
* - currency
* - account number
* - usr id
* - usr token
* - POST request only
*
*/

?>