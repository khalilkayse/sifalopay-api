<?php require "pbwallet_core.php";

/* 
*  First Created: 5 Feb, 2022
*  Author: Khalil
*  Content: Core txns code for premier wallet api gateway
*  Gateways: Premier Wallet
*/

function pbwallet_globals(){
    $pbwallet_globals = array(
        "succ_code" => "001",
        "approval_pending"=> "ApprovalRequired");
        return $pbwallet_globals;
}

function pbwallet_debit_txn($account, $amount){

        // call login
    $login = pbwallet_api_login();

    if($login[0] == true){
        // get login token
        $token = $login[1];
        // run debit txn
        $payment = push_payment($account, $amount, $token);

        if($payment[0] == true){
            $txn_id = $payment[1];
            $token = $payment[2];

            // put a 10 second delay here
            sleep(10);
            // retrieve txn info
            $get_payment_info = get_payment($txn_id, $token);

            if($get_payment_info[0] == true){

                // return payment info
                if($get_payment_info[1]['Data']['Status'] == pbwallet_globals()['approval_pending']){
                    
                    sleep(30); // if payment status is not approved wait 30 more seconds.
                    // get payment status again
                    return get_payment($txn_id, $token)[1];
                   
                } else {
                    
                    return $get_payment_info[1];
                }
                // return $get_payment_info[1];
                

            } else {
                // if info retrieval failed
                return $get_payment_info[1]; 
            }

        } else {
            // if payment failed
            return $payment[1]; }

    } else {
        // if login failed
        return $login[1]; 
    }

}

function pbwallet_credit_txn($account, $amount){
        // call login
        $login = pbwallet_api_login();

        if($login[0] == true){
            // get login token
            $token = $login[1];
            // run debit txn
            $payment = top_up($account, $amount, $token);
    
            if($payment[0] == true){

                // return top up response
                return $payment[1];
    
            } else {
                // if payment failed
                return $payment[1]; }
    
        } else {
            // if login failed
            return $login[1]; 
        }
    
}

function pbwallet_verify_pending_txn($txn_id){

    // call login
    $login = pbwallet_api_login();

    if($login[0] == true){
        // get login token
        $token = $login[1];

            return get_payment($txn_id, $token);

    } else {
        // if login failed
        return $login[1]; 
    }

}


// run credit txn
 // $res = pbwallet_credit_txn("00252634428282", "0.5");

  //echo "<pre>"; print_r($res); echo "</pre>"; 

// echo $res['Response']['Messages'];  echo "<br/>";
// echo $res['Response']['Errors'][0]['Message'];  echo "<br/>";
// echo $res['Response']['Code']; echo "<br/>";
// echo $res['Data']['TransactionID']; echo "<br/>";
// echo $res['Data']['TransactionDate']; echo "<br/>";
// echo $res['Data']['List'][4]['Value']; echo "<br/>";