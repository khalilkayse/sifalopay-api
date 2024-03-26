<?php require "../core.php";
/* 
*  First Created: 18 April, 2022
*  Author: Khalil
*  Content: Core transfer code for Sifalo api gateway
*  Gateways: all
*/

function transfer_globals(){
    $txn_globals = array(
    "fund_transfer_succ" => "Fund transfer has been completed",
    "refund_transfer_issued" => "Transfer has failed, And a refund has been issued",
    "refund_failed" => "Something has went wrong, A refund will be issued shortly",
    "transfer_debit_failed" => "Transfer has failed, please try again!",
    "insufficient_balance" => "Sorry, you don't have sufficient balance for this transfer, please try again",
    );
    return $txn_globals;
}
function transfer_debit($token, $from, $from_gateway, $amount, $txn_type){
    // perform debit txn
    $values = array(
        "token"=>$token,
        "from"=>$from,
        "from_gateway"=>$from_gateway,
        "amount"=>$amount,
        "txn_type"=>$txn_type
        );
    
        $data = http_build_query($values);
        $url = "https://phpstack-889786-3206524.cloudwaysapps.com/gateway/txn_core.php";
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        return $response = json_decode(curl_exec($ch), true);

        curl_close($ch);
}

function transfer_credit($token, $to, $to_gateway, $amount, $txn_type){
    // perform credit txn
    $values = array(
        "token"=>$token,
        "to"=>$to,
        "to_gateway"=>$to_gateway,
        "amount"=>$amount,
        "txn_type"=>$txn_type
        );
    
        $data = http_build_query($values);
        $url = "https://phpstack-889786-3206524.cloudwaysapps.com/gateway/txn_core.php";
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        return $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
}

// core money transfer logic
function init_transfer($api_user, $api_key, $from, $to, $from_gateway, $to_gateway, $amount, $ip, $channel){

    $login = api_login($api_user, $api_key);

    if($login[0] == 1){

        // init login & generate token 
        $token = array($api_user, $login[2]);
        // debit funds from the customer account
        $debit_txn = transfer_debit($token, $from, $from_gateway, $amount, "debit"); 
		//var_dump($debit_txn);
        if($debit_txn['code'] == 601){ // if debit is successful

            $debit_txn_id = $debit_txn['txn_id']; // debit txn id

            // credit funds to the customer account
            $commission = transfer_commission($amount); // calculate commission
            $receiving_amount = $amount - $commission; // calculate receiving amount

            $credit_txn = transfer_credit($token, $to, $to_gateway, $receiving_amount, "credit"); 

            if($credit_txn['code'] == 601){ // if credit is successful

                $credit_txn_id = $credit_txn['txn_id']; // credit txn id

                // transfer success
                $respone_msg = json_encode(array(
                    "code"=> 1,
                    "response" => transfer_globals()['fund_transfer_succ']));
            }else{
                // if credit fails issue refund
                $refund = transfer_credit($token, $from, $from_gateway, $amount, "credit");

                if($refund['code'] == 601){ // if refund is successful

                    $refund_txn_id = $refund['txn_id']; // refund txn id

                    $respone_msg = json_encode(array(
                        "code"=> 0,
                        "response" => transfer_globals()['refund_transfer_issued']));
                } else { 
                    // if refund fails register manual refund here
                    $respone_msg = json_encode(array(
                        "code"=> 0,
                        "response" => transfer_globals()['refund_failed']));
                }
                   
            }

        }elseif($debit_txn['code'] == 604){ // if debit fail due to insufficient balance

            $respone_msg = json_encode(array(
                "code"=> 0,
                "response" => transfer_globals()['insufficient_balance']));

        }else{
            // if main debit txn is failed
            $respone_msg = json_encode(array(
                "code"=> 0,
                "response" => transfer_globals()['transfer_debit_failed']));
        }

        // store txn info to db
        insert_action("transfers",
        array(

            'debit_txn_id' => @$debit_txn_id,
            'credit_txn_id' => @$credit_txn_id,
            'refund_txn_id' => @$refund_txn_id,
            'from_account' => validatePhone($from, $from_gateway),
            'to_account' => validatePhone($to, $to_gateway),
            'from_gateway' => $from_gateway,
            'to_gateway' => $to_gateway,
            'status' => json_decode($respone_msg, true)['response'],
            'commission' => @$commission,
            'total_amount' => @$receiving_amount,
            'currency' => "USD",
            'channel' => $channel,
            'ip' => $ip,
        ));


        return $respone_msg;
    } else {
        // if api login is failed
        echo json_encode(array(
            "code"=> 0,
            "response" => $login['2']));
    }
    
}



// get data from api call
$from_api_call = json_decode(file_get_contents('php://input'), true);

// transfer api
if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && isset($from_api_call['from']) && isset($from_api_call['to']) && isset($from_api_call['from_gateway']) && isset($from_api_call['to_gateway']) && isset($from_api_call['amount']) && isset($from_api_call['ip']) && isset($from_api_call['channel'])){
    
    // block zaad transactions
    // if ($from_api_call['from_gateway'] == "zaad" || $from_api_call['to_gateway'] == "zaad"){
        
    //     echo json_encode(array(
    //         "code"=> 404,
    //         "response" => "Sorry, this service is currently unavailable. Please use eDahab or Premier Wallet instead."));

    //         die();
    // }
 
        // Maintenance MSG: Sorry we’re down for maintenance right now, Something big & special is coming and we can’t wait for you to see it. Please check back soon.

        
    if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW']) && !empty($from_api_call['from']) && !empty($from_api_call['to']) && !empty($from_api_call['from_gateway']) && !empty($from_api_call['to_gateway']) && !empty($from_api_call['amount']) && !empty($from_api_call['ip']) && !empty($from_api_call['channel'])){
        
        // run txn and return response
        header('Content-Type: application/json; charset=utf-8'); 
        // prefent transfers larger than $1,000 from the app
        // if($from_api_call['channel'] == "APP" && $from_api_call['amount'] > 1000){

        //     echo json_encode(array(
        //         "code"=> 404,
        //         "response" => "Maximum transfer is $1,000. For larger transfers, please signup for Sifalo Pay Business."));

        // } else {

            // check if there is enough balance for this transfer (to gateway amount)
            //if (check_balance(strtolower($from_api_call['to_gateway']), $from_api_call['amount'], 0)) {
            if (1 == 2) {

                // perform transfer
                echo init_transfer($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $from_api_call['from'], $from_api_call['to'], strtolower($from_api_call['from_gateway']), strtolower($from_api_call['to_gateway']), $from_api_call['amount'], $from_api_call['ip'], $from_api_call['channel']);
                

            } else {

            // return general error for insufficient balance
            header('Content-Type: application/json; charset=utf-8');  
            echo json_encode(array(
            "code"=> 407,
            "response" => "We’re sorry for the inconvenience, but we’re working on improving our system right now. Please come back later and thank you for your patience. 😊"));

            // send alert on telegram for insufficient balance
            //$msg = "[SifaloPay] Insufficient Balance on ".strtoupper($from_api_call['to_gateway']).", Transfer of $".$from_api_call['amount']." requested from ".strtoupper($from_api_call['from_gateway'])." Account: #".$from_api_call['from']." Available Balance: $". check_balance(strtolower($from_api_call['to_gateway']), $from_api_call['amount'], 1);
            $msg = "[SifaloPay] Request has been made to transfer $".$from_api_call['amount']." from ".strtoupper($from_api_call['from_gateway'])." account: ".$from_api_call['from']." to ".strtoupper($from_api_call['to_gateway'])." account: ".$from_api_call['to']." at ".convert_date(time());

            $url = "https://labs.sifalo.com/sms.php?msg=".urlencode($msg)."&channel=-1001652670265";
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($curl);
            curl_close($curl);

            // record failed transfer on db
            $sid = strtoupper(GenerateRandomString("both", "6"));
            if($from_api_call['from_gateway'] == "pbwallet") { $from_api_call['from_gateway'] = "PREMIER WALLET" ;} // Overwrite gateway if it's premier wallet
            record_failed_transfer($from_api_call['amount'], strtoupper($from_api_call['from_gateway']), $msg, $from_api_call['from'], $sid);
            // save txn on the logs
            log_this(
                array(
                    "sid"=> $sid,
                    "IP"=> $from_api_call['ip'],
                    "channel"=> $from_api_call['channel'],
                    "from_gateway"=>strtoupper($from_api_call['from_gateway']),
                    "from_account"=>$from_api_call['from'],
                    "to_gateway"=>strtoupper($from_api_call['to_gateway']),
                    "to_account"=>$from_api_call['to'],
                    "amount"=>$from_api_call['amount'],
                    "currency"=>"USD",
                    "txn_type"=>"Transfer",
                    "merchant_id"=>4,
                    "txn_detail"=>$msg
                ), "txn" // log type
            );
        }
    //}
        
    } else {

    // detect missing parameters
    header('Content-Type: application/json; charset=utf-8');  
    echo json_encode(array(
        "code"=> 404,
        "response" => "Please click Retry to try again."));
    }

} else {
    // detect missing parameters
    header('Content-Type: application/json; charset=utf-8');  
    echo json_encode(array(
        "code"=> "404",
        "response" => "Required parameters missing."));
}