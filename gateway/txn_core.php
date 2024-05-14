<?php require "../core.php";

/* 
*  First Created: 9 Mar, 2022
*  Author: Khalil
*  Content: Core txns code for all api gateways
*  Gateways: all
*/

function txn_globals(){
    $txn_globals = array(
        "succ_code" => "601",
        "pending_code" => "603",
        "failed_code" => "600",
        "insuff_code" => "604",
        "pbwallet_pending"=> "ApprovalRequired",
        "zaad_pending"=> "pending",
        "edahab_pending"=> "Unpaid",
        "zaad_insuff"=> "Your Account Balance is not sufficient",
        "edahab_insuff_balance1" =>"Insufficient Customer Balance",
        "edahab_insuff_balance2" =>"You do not have sufficient balance.",
        "pbwallet_insuff_balance" => "Customer does not have sufficient balance in the wallet.",
        "txn_pending" => "Your Transaction is pending for Approval!",
        "txn_approved" => "Your Transaction has been Processed!",
        "txn_failed" => "Your Transaction has failed!",
        "txn_insuff" => "Transaction failed due to insufficient balance",
    );
        return $txn_globals;
}

function process_txn_meta($txn_meta, $sid){

    if($txn_meta[0] != "0"){
        $url = @$txn_meta[0];
        $ip = $txn_meta[1];
        $order_id=$txn_meta[2];
        $channel = $txn_meta[3];
        $billing = $txn_meta[4];

        // store txn meta to txn_log
        insert_action("txn_log",
        array(
            "sid"=>$sid,
            "channel"=>$channel,
            "url"=>$url,
            "ip"=>$ip
        ));

        // store billing to billing data
        insert_action("billing_address",
        array(
            "sid"=>$sid,
            "order_id"=>$order_id,
            "first_name"=>$billing['first_name'],
            "last_name"=>$billing['last_name'],
            "company"=>$billing['company'],
            "address_1"=>$billing['address_1'],
            "address_2"=>$billing['address_2'],
            "country"=>$billing['country'],
            "city"=>$billing['city'],
            "state"=>$billing['state'],
            "postcode"=>$billing['postcode'],
            "email"=>$billing['email'],
            "phone"=>$billing['phone']
        ));
    }

}

// transalate gateway api response msgs
function response_msgs($get_msg){
        if($get_msg['0'] == 1){
            // txn success
            return array_replace($get_msg,array(1, txn_globals()['succ_code'], txn_globals()['txn_approved']));

        }elseif($get_msg['0'] == 0 && (substr($get_msg['2'],16,38) == txn_globals()['zaad_insuff'] || $get_msg['2'] == txn_globals()['edahab_insuff_balance1'] || $get_msg['2'] == txn_globals()['edahab_insuff_balance2'] || $get_msg['2'] == txn_globals()['pbwallet_insuff_balance'])){
            // insufficient balance
            return array_replace($get_msg,array(0, txn_globals()['insuff_code'], txn_globals()['txn_insuff']));

        }elseif($get_msg['0'] == 0 && ($get_msg['2'] == txn_globals()['pbwallet_pending'] || $get_msg['2'] == txn_globals()['edahab_pending'] || $get_msg['2'] == txn_globals()['zaad_pending'])){
            // txn pending
            return array_replace($get_msg,array(0, txn_globals()['pending_code'], txn_globals()['txn_pending']));

        }else{
            // txn failed
			log_this($get_msg, "txn" ); // save fail respone on logs
            return array_replace($get_msg,array(0, txn_globals()['failed_code'], txn_globals()['txn_failed']));

        }
}
// escrow api
function escrow($token, $gateway, $account, $amount, $currency, $merchant_id, $sid, $account_type = "CUSTOMER"){
    
    $balance = round(get_wallet_balance($merchant_id, $currency), 2);

    if($balance < round($amount, 2)){
        return array(0, 00, "insuffient balance!");
    } else {
        return run_txn("credit", $account, $amount, $token, $currency, $sid, $account_type);
    }

    
}
// run transaction
function init_payment($token, $gateway, $account, $amount, $pay_type, $currency, $txn_meta, $account_type = "CUSTOMER"){

    // generate global txn id
    $sid = strtoupper(GenerateRandomString("both", "6"));
    // process and save txn meta data to db
    process_txn_meta($txn_meta, $sid);
    // get merchant_id
    $merchant_id = get_merchant_id($token[1]);

    // run txn in the intended gateway
    switch($gateway){
        case "zaad":
            require_once "zaad/zaad_gateway.php";
            
                // escrow payment
                if($pay_type == "escrow") { 
				
                    $respone_msg = escrow($token, $gateway, $account, $amount, $currency, $merchant_id, $sid, $account_type); 
                } else {
					
                    $respone_msg = run_txn($pay_type, $account, $amount, $token, $currency, $sid, $account_type);
                }

            break;

        case "pbwallet":
            require_once "pbwallet/pbwallet_gateway.php";

            if($currency != "SLSH"){

                if($pay_type == "escrow") { 

                    $respone_msg = escrow($token, $gateway, $account, $amount, $currency, $merchant_id, $sid); 

                }else {
                    
                    $respone_msg = run_txn($pay_type, $account, $amount, $token, $currency, $sid);
                }
	
            } else {

                $respone_msg = array(0,00,"SLSH Currency is coming soon.");
            }
                        
            break;

        case "edahab":
            require_once "edahab/edahab_gateway.php";
            
                // escrow payment
                if($pay_type == "escrow") { 
                    //echo $marchant_id;
                    $respone_msg = escrow($token, $gateway, $account, $amount, $currency, $merchant_id, $sid); 
    
                } else {
    
                    $respone_msg = run_txn($pay_type, $account, $amount, $token, $currency, $sid);
    
               }
                            
            break;        
        default: 
            $respone_msg = array(0,00,"coming soon");

    }

    return array($respone_msg, $sid);

}

function run_checkout($token, $amount, $currency, $txn_meta, $return_url){
     // send txn details to checkout page
     $values = array(
        "token"=>$token,
        "amount"=>$amount,
        "currency"=>$currency,
        "return_url"=>$return_url,
        "txn_meta"=>$txn_meta
        
     );
    
 //redirect api call to another webpage to complete transaction

 // get the first 3 digits of the user and the last 3 digits of the password
    $key = substr($token[0], 0, 3); // first 3 digits of the api user
    $secret = substr($token[1], -7); // last 7 digits of the api password

    $alg=$key.$secret;

    echo json_encode(
        array(
            "key" => urlencode($token[1]), // generated api request token
            "token" => urlencode(encrypt(json_encode($values), $alg)) // encrypted request data
        )
    );

}

// detect and run transaction
if(isset($_POST['txn_token'])){
    // run checkout transaction
	if($_POST['txn_gateway'] == "checkout"){
        run_checkout($_POST['txn_token'], $_POST['txn_amount'], $_POST['txn_currency'], $_POST['txn_meta'], $_POST['txn_return_url']);
        return;
    }

    $account = validatePhone($_POST['txn_account'], $_POST['txn_gateway']);
    $run_txn = init_payment($_POST['txn_token'], $_POST['txn_gateway'], $account, $_POST['txn_amount'], $_POST['txn_type'], $_POST['txn_currency'], $_POST['txn_meta'], $_POST['txn_account_type'] ?? null);
    
    // run txn
    $txn = response_msgs($run_txn[0]);

        echo json_encode(array(
            "code"=> $txn[1],
            "sid"=> $run_txn[1],
            "response" => $txn[2]));
}

// detect and run transfer
if(isset($_POST['from_gateway']) || isset($_POST['to_gateway'])){

        $txn_meta = array(0,0,0,0); // to satisfy init txn method

        if(isset($_POST['from_gateway'])){
            $account = validatePhone($_POST['from'], $_POST['from_gateway']);
            $gateway = $_POST['from_gateway'];
        }elseif(isset($_POST['to_gateway'])){
            $account = validatePhone($_POST['to'], $_POST['to_gateway']);
            $gateway = $_POST['to_gateway'];
        }

            $transfer_txn = init_payment($_POST['token'], $gateway, $account, $_POST['amount'], $_POST['txn_type'], "USD", $txn_meta);

            $txn = response_msgs($transfer_txn[0]);

            echo json_encode(array(
                "code"=> $txn[1],
                "response" => $txn[2],
                "txn_id" => $transfer_txn[1]));

}