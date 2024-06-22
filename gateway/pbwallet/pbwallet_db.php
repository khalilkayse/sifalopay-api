<?php //require "new/mashkuur/app/core.php";

/* 
*  First Created: 12 Feb, 2022
*  Author: Khalil
*  Content: Core txnDB code for premier wallet api gateway
*  Gateways: Premier Wallet
*/

function register_payment($values, $pbwallet, $gateway, $txn_type)
{

    // check gateway type and txn type before processing db input
    if ($gateway == "pbwallet" && $txn_type == "debit") {


        if ($pbwallet != 0) {
            insert_action(
                "transaction",
                array(
                    'txn_id' => $values['txn_id'],
                    'sid' => $values['sid'],
                    'amount' => $values['amount'],
                    'commission_perc' => $values['commission_perc'],
                    'commission' => $values['commission'],
                    'total_amount' => $values['total_amount'],
                    'currency_type' => $values['currency'],
                    'payment_type' => $values['payment_type'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id'],
                    'account' => $values['account']
                )
            );

            insert_action(
                "pbwallet",
                array(
                    'txn_id' => $pbwallet['txn_id'],
                    'account' => $pbwallet['account'],
                    "customer_name" => $pbwallet['customer_name'],
                    'amount' => $pbwallet['amount'],
                    'txn_type' => $pbwallet['txn_type'],
                    'description' => $pbwallet['txn_detail'],
                    'txn_date' => $pbwallet['txn_date']
                )
            );

            // calculate wallet balance
            $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency']), 6);
            if ($get_balance != 0) {
                $balance = $get_balance + $values['total_amount'];
            } else {
                $balance = $values['total_amount'];
            }


            // if merchant has an API and the merchant id is 1 (sifalo) then insert to wallet table  
            if (isSaveWalletEnabled('pbwallet', $values['merchant_id'])) {
                insert_action(
                    "wallet",
                    array(
                        'txn_id' => $values['txn_id'],
                        'merchant_id' => $values['merchant_id'],
                        'credit' => $values['total_amount'],
                        'balance' => round($balance, 6), // round up balance to 6 decimal places
                        'currency_type' => $values['currency'],
                        'gateway' => $values['payment_type'],
                        'description' => $pbwallet['txn_detail'],
                        'txn_date' => $values['txn_date'],
                        'sid' => $values['sid']
                    )
                );
            }
            // update balance
            updateGatewayBalance("PBWALLET-USD", "DEBIT", $pbwallet['amount']);
        } else {

            insert_action(
                "transaction",
                array(
                    'txn_id' => $values['txn_id'], // added while fixing the payment verification
                    'amount' => $values['amount'],
                    'sid' => $values['sid'],
                    'total_amount' => $values['total_amount'],
                    'account' => $values['account'],
                    'payment_type' => $values['payment_type'],
                    'currency_type' => $values['currency'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id'],
                    
                )
            );
        }
    } elseif ($gateway == "pbwallet" && $txn_type == "credit") {

        if ($pbwallet != 0) {
            insert_action(
                "transaction",
                array(
                    'txn_id' => $values['txn_id'],
                    'sid' => $values['sid'],
                    'amount' => $values['amount'],
                    'commission_perc' => $values['commission_perc'],
                    'commission' => $values['commission'],
                    'total_amount' => $values['total_amount'],
                    'currency_type' => $values['currency'],
                    'payment_type' => $values['payment_type'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id'],
                    'account' => $values['account']
                )
            );

            insert_action(
                "pbwallet",
                array(
                    'txn_id' => $pbwallet['txn_id'],
                    'account' => $pbwallet['account'],
                    "customer_name" => $pbwallet['customer_name'],
                    'amount' => $pbwallet['amount'],
                    'txn_type' => $pbwallet['txn_type'],
                    'description' => $pbwallet['txn_detail'],
                    'txn_date' => $pbwallet['txn_date']
                )
            );

            // calculate wallet balance
            $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency']), 6);
            if ($get_balance != 0) {
                $balance = $get_balance - $values['total_amount'];
            } else {
                $balance = $get_balance;
            }

            // if merchant has an API and the merchant id is 1 (sifalo) then insert to wallet table 
            if (isSaveWalletEnabled('pbwallet', $values['merchant_id'])) {
                insert_action(
                    "wallet",
                    array(
                        'txn_id' => $values['txn_id'],
                        'merchant_id' => $values['merchant_id'],
                        'debit' => $values['total_amount'],
                        'balance' => round($balance, 6), // round up balance to 6 decimal places
                        'currency_type' => $values['currency'],
                        'gateway' => $values['payment_type'],
                        'description' => $pbwallet['txn_detail'],
                        'txn_date' => $values['txn_date']
                    )
                );
            }
            // update balance
            updateGatewayBalance("PBWALLET-USD", "CREDIT", $pbwallet['amount']);
        } else {

            insert_action(
                "transaction",
                array(
                    'amount' => $values['amount'],
                    'sid' => $values['sid'],
                    'total_amount' => $values['total_amount'],
                    'account' => $values['account'],
                    'payment_type' => $values['payment_type'],
                    'currency_type' => $values['currency'],
                    'txn_type' => $values['txn_type'],
                    'txn_detail' => $values['txn_detail'],
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'merchant_id' => $values['merchant_id']
                )
            );
        }
    }
}

function get_merchant_id_by_txn_id($txn_id)
{
    $data = getData("SELECT merchant_id FROM transaction WHERE txn_id = '$txn_id' limit 1");
    if (!empty($data)) {
        $row = mysqli_fetch_assoc($data);
        extract($row);
        return $row['merchant_id'];
    } else {
        return 0;
    }
}
