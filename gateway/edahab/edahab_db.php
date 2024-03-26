<?php //require "new/mashkuur/app/core.php";;

/* 
*  First Created: 05 Mar, 2022
*  Author: Khalil
*  Content: Core txnDB code for edahab api gateway
*  Gateways: eDahab
*/

// call this function to input txn to DB
function register_payment($values, $edahab, $gateway, $txn_type){

    // check gateway type and txn type before processing db input
    if($gateway == "edahab" && $txn_type == "debit"){

            
            if($edahab != 0){
                insert_action("transaction",
                array(
                    'txn_id' => $values['txn_id'],
                    'sid' => $values['sid'],
                    'amount' => $values['amount'],
                    'commission_perc' => $values['commission_perc'],
                    'commission' => $values['commission'],
                    'total_amount' => $values['total_amount'],
                    'payment_type' => $values['payment_type'],
                    'currency_type' => $values['currency_type'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id']
                ));

                insert_action("edahab",
                array(
                    'txn_id' => $edahab['txn_id'],
                    'req_id' => $edahab['req_id'],
                    'inv_id' => $edahab['inv_id'],
                    'account' => $edahab['account'],
                    'amount' => $edahab['amount'],
                    'txn_type' => $edahab['txn_type'],
                    'currency' => $edahab['currency_type'],
                    'description' => $edahab['txn_detail'],
                    'status' => $edahab['status'],
                    'txn_date' => $edahab['txn_date']
                ));
                                // calculate wallet balance
                                $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency_type']),6);
                                if($get_balance != 0){
                                    $balance = $get_balance + $values['total_amount'];
                                } else {
                                    $balance = $values['total_amount'];
                                }

                insert_action("wallet",
                array(
                    'txn_id' => $values['txn_id'],
                    'merchant_id' => $values['merchant_id'],
                    'credit' => $values['total_amount'],
                    'balance' => round($balance, 6), // round up balance to 6 decimal places
                    'currency_type' => $values['currency_type'],
                    'gateway' => $values['payment_type'],
                    'description' => $edahab['txn_detail'],
                    'txn_date' => $values['txn_date'],
                    'sid' => $values['sid']
                ));   
                // update balance
                if($values['currency_type'] == "SLSH"){
                    $account_sfx = "EDAHAB-SLSH";

                }else{
                    $account_sfx = "EDAHAB-USD";
                }
                updateGatewayBalance($account_sfx, "DEBIT", $edahab['amount']);
            }else {

                insert_action("transaction",
                array(

                    'amount' => $values['amount'],
					'account' => $values['account'],
                    'sid' => $values['sid'],
                    'total_amount' => $values['total_amount'],
                    'payment_type' => $values['payment_type'],
                    'currency_type' => $values['currency_type'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id']
                ));
            }


    }elseif($gateway == "edahab" && $txn_type == "credit"){
                
                if($edahab != 0){

                    insert_action("transaction",
                            array(
                                'txn_id' => $values['txn_id'],
                                'sid' => $values['sid'],
                                'amount' => $values['amount'],
                                'total_amount' => $values['total_amount'],
                                'payment_type' => $values['payment_type'],
                                'currency_type' => $values['currency_type'],
                                'txn_type' => $values['txn_type'],
                                'txn_detail' => $values['txn_detail'],
                                'txn_status' => $values['txn_status'],
                                'txn_date' => $values['txn_date'],
                                'merchant_id' => $values['merchant_id']
                            ));
                   
                    insert_action("edahab",
                            array(
                                'txn_id' => $edahab['txn_id'],
                                'account' => $edahab['account'],
                                'amount' => $edahab['amount'],
                                'txn_type' => $edahab['txn_type'],
                                'currency' => $edahab['currency_type'],
                                'description' => $edahab['txn_detail'],
                                'status' => $edahab['status'],
                                'txn_date' => $edahab['txn_date']
                            ));
                            
                    // calculate wallet balance
                    $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency_type']),6);
                    if($get_balance != 0){
                        $balance = $get_balance - $values['total_amount'];
                        //if($balance == -0) { $balance = 0; } // remove minus sign
                    } else {
                        $balance = $get_balance;
                    }
                    
                    insert_action("wallet",
                    array(
                        'txn_id' => $values['txn_id'],
                        'merchant_id' => $values['merchant_id'],
                        'debit' => $values['total_amount'],
                        'balance' => round($balance, 6), // round up balance to 6 decimal places
                        'currency_type' => $values['currency_type'],
                        'gateway' => $values['payment_type'],
                        'description' => $edahab['txn_detail'],
                        'txn_date' => $values['txn_date']
                    ));
                       
                    // update balance
                    if($values['currency_type'] == "SLSH"){
                        $account_sfx = "EDAHAB-SLSH";
    
                    }else{
                        $account_sfx = "EDAHAB-USD";
                    }
                    updateGatewayBalance($account_sfx, "CREDIT", $edahab['amount']);
                } else {

                    insert_action("transaction",
                            array(

                                'amount' => $values['amount'],
								'account' => $values['account'],
                                'sid' => $values['sid'],
                                'total_amount' => $values['total_amount'],
                                'payment_type' => $values['payment_type'],
                                'currency_type' => $values['currency_type'],
                                'txn_type' => $values['txn_type'],
                                'txn_detail' => $values['txn_detail'],
                                'txn_status' => $values['txn_status'],
                                'txn_date' => $values['txn_date'],
                                'merchant_id' => $values['merchant_id']
                            ));
                }

    }

}