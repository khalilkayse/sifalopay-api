<?php include "edahab_db.php";
/* 
*  First Created: 05 March, 2022
*  Author: Khalil
*  Content: Core gateway code for all systems using this api
*  Description: This file will serve as the middleware for sifalo payment gateway 
*  Gateways: Sifalo Payment Gateway
*/

// this function contains global variables of this api gateway
function gateway_globals(){
    $gateway_globals = array(
       
        "description" => "Transaction Processed.",
        "edahab_success_code" => "0",
        "succ_status" => "Paid",
        "credit_succ_status" => "Approved");

        return $gateway_globals;
}

// perform debit txn
function run_debit_txn($account, $amount, $currency, $merchant_id){
    require("edahab_txn.php");
    // process txn
    return array(
        "account"=>$account,
        "amount"=>$amount,
        "currency"=>$currency,
        "data"=> json_encode(debit_payment($account, $amount, $currency, $merchant_id)));
}

// perform credit txn
function run_credit_txn($account, $amount, $currency, $merchant_id){
    require("edahab_txn.php");
    // process txn
    return array(
        "account"=>$account,
        "amount"=>$amount,
        "currency"=>$currency,
        "data"=> json_encode(credit_payment($account, $amount, $currency, $merchant_id)));
}

// call this function to run txns and register on DB
function run_txn($txn_type, $account, $amount, $token, $currency, $sid){
    // verify token
    if(verify_token($token[0], $token[1]) == 1){

        // get merchant_id
        $merchant_id = get_merchant_id($token[1]);

        // run txn
        switch ($txn_type){
            // if the txn is a debit transaction.
            case "debit":
    
                $txn = run_debit_txn($account, $amount, $currency, $merchant_id);
                $txn_data = json_decode($txn['data'], true)[1];
    
                // if txn success with no errors process it
                if($txn_data['StatusCode'] == gateway_globals()['edahab_success_code'] && $txn_data['InvoiceStatus'] == gateway_globals()['succ_status']){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = gateway_commission("edahab", $merchant_id);; // commission is retrived from core file
                        $commission = round(($commission_percentage / 100) * $txn['amount'], 2); // round to 2 decimal places
						if($commission == 0){ $commission = 0.01; } // default commission fee
                        $total_amount = $txn['amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                                        "txn_date" => time(),
                                        "sid" => $sid,
                                        "txn_id" => $txn_data['TransactionId'],
                                        "amount" => $txn['amount'],
                                        "commission_perc" => $commission_percentage,
                                        "commission" => $commission,
                                        "total_amount" => $total_amount,
                                        "payment_type" => "EDAHAB",
                                        "currency_type" => $txn['currency'],
                                        "txn_type" => "DEBIT",
                                        "txn_detail" => $txn_data['StatusDescription'],
                                        "txn_status" => strtolower($txn_data['InvoiceStatus']),
                                        "merchant_id" => $merchant_id
                                        );
                        // edahab table values
                        $edahab_values = array(
                                            "txn_date" =>time(),
                                            "req_id" => $txn_data['RequestId'],
                                            "txn_id" => $txn_data['TransactionId'],
                                            "inv_id" => $txn_data['InvoiceId'],
                                            "account"=> $txn['account'],
                                            "amount" => $txn['amount'],
                                            "currency_type" => $txn['currency'],
                                            "txn_type" => "DEBIT",
                                            "status" => strtolower($txn_data['InvoiceStatus']),
                                            "txn_detail" => gateway_globals()['description']
                                        );

                        // send telegram alert
                        notify($txn['amount'], "debited", $sid, convert_date(time()), "eDahab", $txn['account'], $txn['currency']);

                        // return msg
                        $txn_msg = array(1, $txn_data['InvoiceStatus'], $txn_data['StatusDescription']);
                        
                } else {
                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "amount" => $txn['amount'],
							"account"=> $txn['account'],
                            "total_amount" => $txn['amount'],
                            "payment_type" => "EDAHAB",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "DEBIT",
                            "txn_detail" => $txn_data['StatusDescription'],
                            "txn_status" => strtolower($txn_data['InvoiceStatus']),
                            "merchant_id" => $merchant_id
                            );
                        // set zaad table values to zero to avoid saving it to db
                        $edahab_values = 0;

                        // return msg
                        $txn_msg = array(0, $txn_data['InvoiceStatus'], $txn_data['StatusDescription']);
                }         
                
                // submit data to DB
                register_payment($txn_values, $edahab_values, "edahab", "debit");

                break;
    
            // if the txn is a credit transaction
            case "credit":
    
                $txn = run_credit_txn($account, $amount, $currency, $merchant_id);
                $txn_data = json_decode($txn['data'], true)[1];
                
                // if txn success with no errors process it
                if($txn_data['TransactionStatus'] == gateway_globals()['credit_succ_status']){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = 0; // actuall commission must be retrived from DB
                        $commission = ($commission_percentage / 100) * $txn['amount'];
                        $total_amount = $txn['amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "txn_id" => $txn_data['TransactionId'],
                            "amount" => $txn['amount'],
                            "total_amount" => $total_amount,
                            "payment_type" => "EDAHAB",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "txn_detail" => gateway_globals()['description'],
                            "txn_status" => strtolower($txn_data['TransactionStatus']),
                            "merchant_id" => $merchant_id
                            );

                        // edahab table values
                        $edahab_values = array(
                            "txn_date" =>time(),
                            "txn_id" => $txn_data['TransactionId'],
                            "account"=> $txn['account'],
                            "amount" => $txn['amount'],
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "status" => strtolower($txn_data['TransactionStatus']),
                            "txn_detail" => $txn_data['TransactionMesage']
                        );

                        // send alert on telegram
                        notify($txn['amount'], "credited", $sid, convert_date(time()), "eDahab", $txn['account'], $txn['currency']);

                        // save data on DB
                        register_payment($txn_values, $edahab_values, "edahab", "credit");

                        // return msg
                        $txn_msg = array(1, $txn_data['TransactionStatus'], $txn_data['TransactionMesage']);

                }else{
                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "amount" => $txn['amount'],
							"account"=> $txn['account'],
                            "total_amount" => $txn['amount'],
                            "payment_type" => "EDAHAB",
                            "currency_type" => $txn['currency'],
                            "txn_type" => "CREDIT",
                            "txn_detail" => $txn_data['TransactionMesage'],
                            "txn_status" => strtolower($txn_data['TransactionStatus']),
                            "merchant_id" => $merchant_id
                            );
                        // set edahab table values to zero to avoid saving it to db
                        $edahab_values = 0;

                        // save data on DB
                        register_payment($txn_values, $edahab_values, "edahab", "credit");

                        // return msg
                        $txn_msg = array(0, $txn_data['TransactionStatus'], $txn_data['TransactionMesage']); 
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