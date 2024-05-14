<?php require "../core.php";
/* 
*  First Created: 16 Feb, 2022
*  Author: Khalil
*  Content: Core gateway code for Sifalo api gateway
*  Gateways: all
*/

function validate_request($user, $pass, $amount, $account, $gateway, $pay_type, $currency, $txn_meta, $return_url, $account_type = "CUSTOMER") {
    
    $login = api_login($user, $pass);

    if($login[0] == 1){

        // init login & generate token 
        $token = array($user, $login[2]);

        // perform txn
        $values = array(
        "txn_token"=>$token,
        "txn_amount"=>$amount,
        "txn_account"=>$account,
        "txn_type"=>$pay_type,
        "txn_currency"=>$currency,
        "txn_gateway"=>$gateway,
        "txn_meta"=>$txn_meta,
        "txn_return_url"=>$return_url,
        "txn_account_type"=>$account_type

        );
    
        $data = http_build_query($values);
        $url = "https://".$_SERVER['HTTP_HOST']."/gateway/txn_core.php";
        //$url = "https://phpstack-889786-3206524.cloudwaysapps.com/gateway/txn_core.php";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        return $response = curl_exec($ch);
        curl_close($ch);
 
    }else{

        echo json_encode(array(
            "code"=> 0,
            "response" => $login['2']));
    }
}

//print_r(json_decode(file_get_contents('php://input'), true)); die();
// get data from api call
//list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6))); 
$from_api_call = json_decode(file_get_contents('php://input'), true);  //echo $_SERVER['PHP_AUTH_USER']; echo $_SERVER['PHP_AUTH_PW']; die();
 
    // verify pbwallet transaction status
    if(isset($from_api_call['pbwallet_txn_id'])) { 

        require "pbwallet/pbwallet_gateway.php"; 
        header('Content-Type: application/json; charset=utf-8');   
        echo json_encode(verify_transaction_status($from_api_call['pbwallet_txn_id'])); die(); 
    }
    
    // core Sifalo Pay txn api
    if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && isset($from_api_call['amount']) && isset($from_api_call['gateway']) && isset($from_api_call['currency'])){

        if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])  && !empty($from_api_call['amount']) && !empty($from_api_call['gateway']) && !empty($from_api_call['currency'])){
            // add "order_id" and "billing"
                // verify if api call contain txn meta data (billing, ip, url)
                if((isset($from_api_call['url']) || isset($from_api_call['ip']) || isset($from_api_call['channel']) || isset($from_api_call['billing'])) && $from_api_call['gateway'] != "checkout"){

                    // if channel was submitted by the api 
                    if(isset($from_api_call['channel']) && !empty($from_api_call['channel']) && $from_api_call['gateway'] != "checkout"){
                    $txn_meta = array($from_api_call['url'], $from_api_call['ip'], $from_api_call['txn_order_id'], $from_api_call['channel'], $from_api_call['billing']);
                    }else{
                        $channel = "custom"; // if the channel was not submitted by the api
                        $txn_meta = array("", $_SERVER['REMOTE_ADDR'], $from_api_call['order_id'], $channel, @$from_api_call['billing']);
                    }
                }else{
                    error_log($from_api_call['url']."TEST".$from_api_call['ip']);
                    // capture if request come from checkout api and save order id and billing data
                    if($from_api_call['gateway'] == "checkout"){

                        $txn_meta = array($from_api_call['url'], $from_api_call['ip'], $from_api_call['order_id'], "checkout", $from_api_call['billing']);
                    }else{
                        $txn_meta = array(0,0,0,0,0);
                    }
                    
                }

                // users other then sifalo can't perform credit transactions
                if($_SERVER['PHP_AUTH_USER'] != "sifalo"){

                    if(isset($from_api_call['payment_type']) && $from_api_call['payment_type'] != "credit"){
                        
                        $payment_type = $from_api_call['payment_type'];
                        
                    } else {
                        // set payment type to debit
                        $payment_type = "debit";
                    }
                    
                } else {

                    if(isset($from_api_call['payment_type']) && !empty($from_api_call['payment_type'])){

                        $payment_type = $from_api_call['payment_type'];
                        
                        
                    }else{
                        $payment_type = "debit"; // set payment type to debit
                    }
                    
                }
                // if payment is not checkout then get account from api call else set default account
                if(strtolower($from_api_call['gateway']) != "checkout" && isset($from_api_call['account']) && !empty($from_api_call['account'])){
                    
                    $account = $from_api_call['account'];

                }else if(strtolower($from_api_call['gateway']) == "checkout"){
                        $account = "xxxx xxxx xxxx xxxx";
                        if(isset($from_api_call['return_url']) && !empty($from_api_call['return_url'])){
                            $return_url = $from_api_call['return_url'];
                        } else {
                            $return_url = 0;
                        }
                }
                
                // Convert SOS currency to SLSH
                if($from_api_call['currency'] == "SOS" || $from_api_call['currency'] == "sos"){
                    $currency = "SLSH";
                } else {
                    $currency = $from_api_call['currency'];
                }
                
                // run txn and return response
                header('Content-Type: application/json; charset=utf-8');    
                echo $txn_response = validate_request($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $from_api_call['amount'], $account, strtolower($from_api_call['gateway']), $payment_type, strtoupper($currency), $txn_meta, @$return_url, $from_api_call['account_type'] ?? NULL);
                
        } else {
            // detect missing parameters
            header('Content-Type: application/json; charset=utf-8');  
            echo json_encode(array(
                "code"=> "404",
                "response" => "Empty parameters detected."));

         // save txn on the logs
         $userdata = array ("merchant"=>$_SERVER['PHP_AUTH_USER'], "txn_detail"=>"Empty parameters detected.");
         $from_api_call += $userdata;
         log_this($from_api_call, "txn"); // log type
        }
    }else{
                // detect missing parameters
                header('Content-Type: application/json; charset=utf-8');  
                echo json_encode(array(
                    "code"=> "404",
                    "response" => "Required parameters missing."));

        // save txn on the logs
        $userdata = array ("merchant"=>$_SERVER['PHP_AUTH_USER'], "txn_detail"=>"Required parameters missing.");
        $from_api_call += $userdata;
        log_this($from_api_call, "txn"); // log type
            
    }
