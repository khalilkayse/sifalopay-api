<?php //require "../../new/mashkuur/app/core.php";

/* 
*  First Created: 11 Dec, 2021
*  Author: Khalil
*  Content: Core txnDB code for zaad api gateway
*  Gateways: Zaad
*/

// call this function to input txn to DB
function register_payment($values, $zaad, $gateway, $txn_type)
{
    // check if merchant has waafi api
    $merchantHasAPI = check_merchantAPI('zaad', $values['merchant_id']) ?? false;

    // check gateway type and txn type before processing db input
    if ($gateway == "zaad" && $txn_type == "debit") {


        if ($zaad != 0) {
            insert_action(
                "transaction",
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
                )
            );

            insert_action(
                "zaad",
                array(
                    'txn_id' => $zaad['txn_id'],
                    'request_id' => $zaad['req_id'],
                    'reference_id' => $zaad['ref_no'],
                    'account_no' => $zaad['account'],
                    'amount' => $zaad['amount'],
                    'txn_type' => $zaad['txn_type'],
                    'currency_type' => $zaad['currency_type'],
                    'description' => $zaad['txn_detail'],
                    'txn_date' => $zaad['txn_date']
                )
            );

            // calculate wallet balance
            $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency_type']), 6);
            if ($get_balance != 0) {
                $balance = $get_balance + $values['total_amount'];
            } else {
                $balance = $values['total_amount'];
            }

            // if merchant has an API and the merchant id is 1 (sifalo) then insert to wallet table 
            if ($merchantHasAPI || $values['merchant_id'] == 1) {
                insert_action(
                    "wallet",
                    array(
                        'txn_id' => $values['txn_id'],
                        'merchant_id' => $values['merchant_id'],
                        'credit' => $values['total_amount'],
                        'balance' => round($balance, 6), // round up balance to 6 decimal places
                        'currency_type' => $values['currency_type'],
                        'gateway' => $values['payment_type'],
                        'description' => $zaad['txn_detail'],
                        'txn_date' => $values['txn_date']
                    )
                );
            }

            // update balance
            if ($values['currency_type'] == "SLSH") {
                $account_sfx = "ZAAD-SLSH";
            } else {
                $account_sfx = "ZAAD-USD";
            }

            updateGatewayBalance($account_sfx, "DEBIT", $zaad['amount']);
        } else {

            insert_action(
                "transaction",
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
                )
            );
        }
    } elseif ($gateway == "zaad" && $txn_type == "credit") {

        if ($zaad != 0) {

            insert_action(
                "transaction",
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
                    'txn_status' => $values['txn_status'],
                    'txn_date' => $values['txn_date'],
                    'txn_detail' => $values['txn_detail'],
                    'merchant_id' => $values['merchant_id']
                )
            );

            insert_action(
                "zaad",
                array(
                    'txn_id' => $zaad['txn_id'],
                    'request_id' => $zaad['req_id'],
                    'reference_id' => $zaad['ref_no'],
                    'account_no' => $zaad['account'],
                    'amount' => $zaad['amount'],
                    'txn_type' => $zaad['txn_type'],
                    'currency_type' => $zaad['currency_type'],
                    'txn_date' => $zaad['txn_date'],
                    'account_type' => $zaad['account_type'],
                    'account_holder' => $zaad['account_holder'],
                    'issuer_approval_code' => $zaad['issuer_approval_code'],
                    'merchant_charges' => $zaad['merchant_charges'],
                    'customer_charges' => $zaad['customer_charges'],
                    'account_exp_date' => $zaad['account_exp_date'],
                    'issuer_txn_id' => $zaad['issuer_txn_id']
                )
            );

            // calculate wallet balance
            $get_balance = round(get_wallet_balance($values['merchant_id'], $values['currency_type']), 6);
            if ($get_balance != 0) {
                $balance = $get_balance - $values['total_amount'];
            } else {
                $balance = $get_balance;
            }

            //if merchant has anAPI and the merchant id is 1 (sifalo) then insert to wallet table 
            if ($merchantHasAPI || $values['merchant_id'] == 1) {
                insert_action(
                    "wallet",
                    array(
                        'txn_id' => $values['txn_id'],
                        'merchant_id' => $values['merchant_id'],
                        'debit' => $values['total_amount'],
                        'balance' => round($balance, 6), // round up balance to 6 decimal places
                        'currency_type' => $values['currency_type'],
                        'gateway' => $values['payment_type'],
                        'description' => $values['txn_detail'],
                        'txn_date' => $values['txn_date'],
                        'sid' => $values['sid']
                    )
                );
            }

            // update balance

            if ($values['currency_type'] == "SLSH") {
                $account_sfx = "ZAAD-SLSH";
            } else {
                $account_sfx = "ZAAD-USD";
            }

            updateGatewayBalance($account_sfx, "CREDIT", $zaad['amount']);
        } else {

            insert_action(
                "transaction",
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
                )
            );
        }
    }
}
