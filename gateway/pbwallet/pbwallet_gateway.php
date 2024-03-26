<?php require "pbwallet_db.php";

/* 
*  First Created: 12 Feb, 2022
*  Author: Khalil
*  Content: Core txns code for premier wallet api gateway
*  Gateways: Premier Wallet
*/

// this function contains global variables of this api gateway
function gateway_globals(){
    $gateway_globals = array(
        "description" => "Transaction Processed.",
        "pbwallet_success_code" => "001");

        return $gateway_globals;
    }
// perform debit txn
function debit_txn($account, $amount){
    require("pbwallet_txn.php");
// process txn
return array(
    "account"=>$account,
    "amount"=>$amount,
    "data"=> json_encode(pbwallet_debit_txn($account, $amount)));
}

// perform credit txn
function credit_txn($account, $amount){
    require("pbwallet_txn.php");
   // process txn
   return array(
    "account"=>$account,
    "amount"=>$amount,
    "data"=> json_encode(pbwallet_credit_txn($account, $amount)));

   }
// call this function to run txns and register on DB
function run_txn($txn_type, $account, $amount, $token, $currency, $sid){

    if(verify_token($token[0], $token[1]) == 1){

        // get merchant_id
        $merchant_id = get_merchant_id($token[1]);

        switch ($txn_type){
            // if the txn is a debit transaction.
            case "debit":
    
                $txn_data = debit_txn($account, $amount);
                $txn = json_decode($txn_data['data'], true);
                //$txn_data = json_decode($get_txn['data'], true);
                
                //print_r($txn); die();
    
                // if txn is success with no errors or pending for for approval process it
                if($txn['Response']['Code'] == gateway_globals()['pbwallet_success_code'] && !($txn['Data']['Status'] == "ApprovalRequired" || $txn['Data']['Status'] == "Rejected")){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = gateway_commission("pbwallet", $merchant_id);; // commission is retrived from core file
                        $commission = round(($commission_percentage / 100) * $txn['Data']['Amount'], 2); // round to 2 decimal places
						if($commission == 0){ $commission = 0.01; } // default commission fee
                        $total_amount = $txn['Data']['Amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                                        "txn_date" => time(),
                                        "sid" => $sid,
                                        "txn_id" => $txn['Data']['TransactionId'],
                                        "amount" => $txn['Data']['Amount'],
                                        "commission_perc" => $commission_percentage,
                                        "commission" => $commission,
                                        "total_amount" => $total_amount,
                                        "payment_type" => "PREMIER WALLET",
                                        "txn_type" => "DEBIT",
                                        'currency' => $currency,
                                        "txn_detail" => gateway_globals()['description'],
                                        "txn_status" => strtolower($txn['Data']['Status']),
                                        "merchant_id" => $merchant_id
                                        );
                        // pbwallet table values
                        $pbwallet_values = array(
                                            "txn_date" =>$txn['Data']['TransactionDate'],
                                            "txn_id" => $txn['Data']['TransactionId'],
                                            "account"=> $txn['Data']['CustomerWalletID'],
                                            "customer_name"=> $txn['Data']['CustomerName'],
                                            "amount" => $txn['Data']['Amount'],
                                            "txn_type" => "DEBIT",
                                            "txn_detail" => gateway_globals()['description']
                                        );
                        // send telegram alert
                        notify($txn['Data']['Amount'], "debited", $sid, date("Y-m-d H:i:s",$txn['Data']['TransactionDate']), "PREMIER WALLET", $txn['Data']['CustomerWalletID'], $currency);
                        
                        // return msg
                        $txn_msg = array(1, $txn['Response']['Code'], $txn['Response']['Messages']);
                               
                    } else {
                        // if txn is pending for approval only save in txn db table
                        if(!empty($txn['Data']['Status']) &&  $txn['Data']['Status'] == "ApprovalRequired"){
                            $txn_status = "pending";
                            $txn_detail = "Customer Approval Required";
                            $return_msg = array(0, $txn['Response']['Code'], $txn['Data']['Status']);

                            // if txn is rejected only save in txn db table
                         } elseif(!empty($txn['Data']['Status']) &&  $txn['Data']['Status'] == "Rejected"){
                            $txn_status = "rejected";
                            $txn_detail = "Customer Rejected Transaction";
                            $return_msg = array(0, $txn['Response']['Code'], $txn['Data']['Status']);
                            
                        }else {
                            $txn_status = "failed";
                            $txn_detail = $txn['Response']['Errors'][0]['Code'].": ".$txn['Response']['Errors'][0]['Message'];
                            $return_msg = array(0, $txn['Response']['Errors'][0]['Code'], $txn['Response']['Errors'][0]['Message']);
                        }

                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "txn_id" => $txn['Data']['TransactionId'],
                            "amount" => $txn_data['amount'], // retrive from user input
							"account" => $account,
                            "total_amount" =>  $txn_data['amount'], // retrive from user input
                            'currency' => $currency,
                            "payment_type" => "PREMIER WALLET",
                            "txn_type" => "DEBIT",
                            "txn_detail" => $txn_detail,
                            "txn_status" => strtolower($txn_status),
                            "merchant_id" => $merchant_id
                            );
                            
                        // set pbwallet table values to zero to avoid saving it to db
                        $pbwallet_values = 0;

                        // return msg
                        $txn_msg = $return_msg;
                }  

                 // submit data to DB
                register_payment($txn_values, $pbwallet_values, "pbwallet", "debit");
                
                break;
    
            // if the txn is a credit transaction
            case "credit":
    
                $txn_data = credit_txn($account, $amount);
                $txn = json_decode($txn_data['data'], true);
                
                // if txn success with no errors process it
                if($txn['Response']['Code'] == gateway_globals()['pbwallet_success_code']){
    
                        // get amount and calculate the commission to get the total amount
                        $commission_percentage = 0; // actuall commission must be retrived from DB
                        $commission = ($commission_percentage / 100) * $txn['amount'];
                        $total_amount = $txn['amount'] - $commission;
    
                        // collect response from API & gateway variable and prepare for DB input
                        // Txn table values
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "txn_id" => $txn['Data']['TransactionID'],
                            "amount" => $txn_data['amount'],
                            "commission_perc" => $commission_percentage,
                            "commission" => $commission,
                            "total_amount" => $txn_data['amount'],
                            "payment_type" => "PREMIER WALLET",
                            "currency" => $currency,
                            "txn_type" => "CREDIT",
                            "txn_status" => "executed",
                            "txn_detail" => $txn['Response']['Messages'],
                            "merchant_id" => $merchant_id
                            );
  
                         // pbwallet table values
                        $pbwallet_values = array(
                            "txn_date" =>$txn['Data']['TransactionDate'],
                            "txn_id" => $txn['Data']['TransactionID'],
                            "account"=> $txn['Data']['List'][5]['Value'],
                            "customer_name"=> $txn['Data']['List'][4]['Value'],
                            "amount" => $txn['Data']['TotalAmount'],
                            "txn_type" => "CREDIT",
                            "txn_detail" => $txn['Response']['Messages']
                        );

                        // send telegram alert
                        notify($txn['Data']['TotalAmount'], "credited", $sid, date("Y-m-d H:i:s",$txn['Data']['TransactionDate']), "PREMIER WALLET", $txn['Data']['List'][5]['Value'], $currency);
                        
                        // return msg
                        $txn_msg = array(1, $txn['Response']['Code'], $txn['Response']['Messages']);

                }else{
                        // if txn failed submit txn status to db
                        $txn_values = array(
                            "txn_date" => time(),
                            "sid" => $sid,
                            "amount" => $txn_data['amount'], // retrive from user input
							"account" => $account,
                            "total_amount" =>  $txn_data['amount'], // retrive from user input
                            'currency' => $currency,
                            "payment_type" => "PREMIER WALLET",
                            "txn_type" => "CREDIT",
                            "txn_detail" => $txn['Response']['Errors'][0]['Message'],
                            "txn_status" => "failed",
                            "merchant_id" => $merchant_id
                            );
                        // set pbwallet table values to zero to avoid saving it to db
                        $pbwallet_values = 0;  

                        $txn_msg = array(0, $txn['Response']['Code'], $txn['Response']['Errors'][0]['Message']);
                }      

                // save data on DB
                echo register_payment($txn_values, $pbwallet_values, "pbwallet", "credit");
                
                break;
        
        }
                // save txn on the logs
                log_this(
                    array(
                        "sid"=>$sid,
                        "account"=>$txn_data['account'],
                        "amount"=>$txn_data['amount'],
                        "currency"=>$txn_values['currency'],
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


function verify_transaction_status($txn_id){
    require("pbwallet_txn.php");
    return pbwallet_verify_pending_txn($txn_id);

}
// this is goning to be the main api function that would be called
// run pbwallet_txn("debit");
//ohoi