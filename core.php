<?php 

/*
*   This file containt the core functions of the application
*   ex: logmanager / config file ...etc.
*/

require "logmanager.php";
require "dbcore.php";
include "verifyphone.php";

// convert timezone
function convert_date($time){
	$date = new DateTime('@'.$time);
	$date->setTimeZone(new DateTimeZone('Africa/Mogadishu'));
	return $date->format('Y-m-d g:i A');
}

// send txn alert on telegram
function notify($amount, $txn_type, $txn_id, $time, $txn_method, $account, $currency){
    // detect currency
    if($currency == "SLSH" || $currency == "slsh"){
        $sign = "SLSH ";
    }else {
        $sign = "$";
    }
    // use form or to according to txn method
    if($txn_type == "debited"){
        $word = "from";
    } else { $word = "to"; }

    $msg = "[SifaloPay] ".$sign.number_format($amount, 2)." has been ".$txn_type." via ".$txn_method." transaction ".$word." acc#".$account." at ".convert_date(time())." | txn: #".$txn_id;
    
    $data = [
        "chat_id" => "-1001204104467", // Sifalo Pay Transaction Channel
        "disable_web_page_preview" => true,
        "text" => "$msg"
    ];
    
    $apiToken = "5234508485:AAHjAI3DH6Sry1CmCQkIKE5c1mz18CQYHUs";
    
    $url = "https://api.telegram.org/bot$apiToken/sendMessage?".http_build_query($data);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($curl);
    curl_close($curl);
}

// transfer commission calculation
function transfer_commission($amount){

    $plan_a = 1; // $0 - $10
    $plan_b = 1; // $11 - $49
    $plan_c = 1; // $50 - $99
    $plan_d = 1; // $100 - $299
    $plan_e = 1; // $300+

    switch ($amount){

        case $amount <= 10:

            // caluclate 0.3 percent of $amount
            $commission = ($plan_a / 100) * $amount;

            // round commission
            return round($commission, 2);

        case $amount > 10 && $amount <= 49:

            // 0.6 percent of $amount
            $commission = ($plan_b / 100) * $amount;

            // round commission
            return round($commission, 2);

        case $amount > 49 && $amount <= 99:

            $commission = ($plan_c / 100) * $amount;

            // round commission
            return round($commission, 2);

        case $amount > 99 && $amount <= 299:
                
                $commission = ($plan_d / 100) * $amount;

                // round commission
                return round($commission, 2);

        case $amount > 299:
                
                $commission =  ($plan_e / 100) * $amount;

                // round commission
                return round($commission, 2);

        default:
        return $amount;

    }
}

// gateway commissions
function gateway_commission($gateway, $merchant_id){

    // set commission here
    $waafi = 1.6;
    $edahab = 0.6;
    $pbwallet = 1.0;
    $cards = 6.0;
    $ebirr = 1.9;

    switch ($gateway){

        case $gateway == "zaad":

            return $commission = $waafi;

        case $gateway == "edahab":

            return $commission = $edahab;


        case $gateway == "pbwallet":

            return $commission = $pbwallet;

        case $gateway == "cards":

            return $commission = $cards;

        case $gateway == "ebirr":

            return $commission = $ebirr;

        default:
        return $commission = "1.9";

    }
}

// global function to generate random string or int values
function GenerateRandomString($type, $length) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . strtolower('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $nums = '0123456789';
    if($type == "string"){
        $characters = $chars;
    }elseif($type == "int"){
        $characters = $nums;
    }else{
        $characters = $chars . $nums;
    }
    
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// get merchant id
function get_merchant_id($token){
    try{
        $query = mysqli_fetch_array(getData("SELECT user_id FROM api_login WHERE token='$token'"));
        extract($query); return $query['user_id']; 
    }catch(Throwable $e){
        return 0;
    }
}

// get merchant commission
/* function get_merchant_commission($id){
    try{
        $query = mysqli_fetch_array(getData("SELECT escrow_commission FROM  escrow_settings WHERE merchant_id= '$id'"));
        extract($query); return $query['escrow_commission']; 
    }catch(Throwable $e){
        return 0;
    }
} */

// get merchant wallet balance
function get_wallet_balance($merchant_id, $currency = "USD"){
    try{
        $query = mysqli_fetch_array(getData("SELECT sum(credit - debit) AS balance FROM wallet WHERE merchant_id = $merchant_id AND currency_type = '$currency' GROUP BY merchant_id;"));
        extract($query); return $query['balance']; 
    }catch(Throwable $e){
        return 0;
    }
}
// generate merchant api credntials
function generate_merchant_api($marchant){
    // generate user
    $a = substr($marchant, 0, -1);
    $ch = GenerateRandomString("both", 4);
    $x = $a.$ch;
    // generate key
    $z = time() . $marchant;
    $hash = sha1(md5($z));

    return array(
            "api_user" => $x,
            "api_key" => base64_encode($hash)
    );
}

// Update balance table
function updateGatewayBalance($gateway, $txn_type, $amount)
{
	$mysqli = $GLOBALS['con'];
	$updated_on = time();
	if ($txn_type == "CREDIT") {
		// the query
		$query = "UPDATE gateway_balance SET balance = balance - ?,updated_on=? WHERE gateway = ?";
	} else if ($txn_type == "DEBIT") {
		$query = "UPDATE gateway_balance SET balance = balance + ?,updated_on = ? WHERE gateway = ?";
	} else {
		return;
	}
 
	/* Prepare statement */
	$stmt = $mysqli->prepare($query);
	if ($stmt === false) {
		trigger_error('Wrong SQL: ' . $query . ' Error: ' . $mysqli->error, E_USER_ERROR);
	}

	/* Bind parameters. TYpes: s = string, i = integer, d = double,  b = blob */
	$stmt->bind_param(
		'dis',
		$amount,
		$updated_on,
		$gateway
	);

	//execute the query
    $stmt->execute();
}

// check if amount is bigger than balance
function check_balance($gateway, $amount, $queryb){
    
	$mysqli = $GLOBALS['con'];
	$query = "SELECT balance from gateway_balance where gateway='$gateway'";
	$qrun = mysqli_query($mysqli, $query);
	$result = mysqli_fetch_array($qrun);
	extract($result);
	$balance = $result['balance'];

    if($queryb == 1){
        return $balance;
    }else{
        if ($amount > $balance) {
            return false;
        } else {
            return true;
        }
    }

}

function record_failed_transfer($amount, $payment_type, $detail, $account, $sid){

    insert_action("transaction",
    array(
        'sid' => $sid,
        'amount' => $amount,
        'total_amount' => $amount,
        'payment_type' => $payment_type,
        'currency_type' => "USD",
        'txn_type' => "DEBIT",
        'txn_detail' => $detail,
        'account' => $account,
        'txn_status' => "failed",
        'txn_date' => time(),
        'merchant_id' => 4
    ));
}

function log_this($data, $log_type){
    if($log_type == "activity"){
        $extra_data = array(
            "user"=>"khalil",
            "organization"=>"sifalo",
            "url"=>"sifalo.com",
            "ip"=>"19.168.1.1",
            "date"=>time()
        );
    }elseif($log_type == "txn"){
        $extra_data = array(
            "date"=>time()
        );
    }
    
    //append the 2 arrays
    $extra_data += $data;
    // submit log
    save_log($extra_data);
}

// generate user api token
function generate_api_token($user){

    $a = time() . $user;
    $hash = sha1(md5($a));

    return base64_encode($hash);

}

function api_login($APIuser, $APIpass){

   //$data = getData("SELECT merchant_id, api_user, api_pass, status FROM merchant_accounts WHERE api_user = '$user'");
    include "config.php";
   
    $query = "SELECT merchant_id, api_user, api_pass, status FROM merchant_accounts WHERE api_user = '$APIuser'";
   $data = mysqli_query($con, $query);


    if(!empty($data)){
        // fetch user data
        while($raw = mysqli_fetch_array($data)){
            // check if merchant account is active
            if($raw['status'] == 1){

                $passDB = $raw['api_pass'];
                // very api password
                if($APIpass == $passDB){
                    $token =  generate_api_token($user); // generate new token
                    $date = time();
                    $validity = $date + 90; // 1.5 minute validity
                    // insert into db
                    insert_action("api_login",
                    array(
                        'token' => $token,
                        'user_id' => $raw['merchant_id'],
                        'date' => $date,
                        'token_validity' => $validity
                    ));
                    
                    return array(1, 00, $token);
                }else {
                    return array(0,00,"invalid api key");
                }

            } else {
                return array(0,00,"Sorry, This merchant is not active");
            }
        }

    } else{ return array(0,00,"merchant doesn't exist!") ; }
}
// verify api status
function verify_merchantAPI_status($api_user)
{
	$mysqli = $GLOBALS['con_pay'];
	// output any connection error
	if ($mysqli->connect_error) {
		die('Error : (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
	}
	// the query
	$query = "SELECT status from merchant_accounts where api_user='$api_user'";
	// mysqli select query
	$results = $mysqli->query($query);

	if ($results) {

		if ($results->num_rows === 0) {
			return 0;
		} else {
			$list = mysqli_fetch_array($results);
			extract($list);
			return $list['status'];
		}
	}
}

function encrypt($data, $key)
{
    $iv = random_bytes(16); // Generate a random initialization vector
    $ciphertext = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $encryptedData = base64_encode($iv . $ciphertext); // Concatenate IV and ciphertext and encode as base64
    return $encryptedData;
}

function decrypt($encryptedData, $key)
{
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16); // Extract the IV from the encrypted data
    $ciphertext = substr($data, 16); // Extract the ciphertext from the encrypted data
    $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plaintext;
}

function verify_token($user, $token){

    $data = getData("SELECT * FROM api_login WHERE token = '$token' AND user_id = (SELECT merchant_id FROM merchant_accounts WHERE api_user = '$user')");

    if(!empty($data)){

        while($raw = mysqli_fetch_array($data)){

            $valid = $raw['token_validity'];
            // check if token is valid
            if($valid > time()){
                return 1;
            }else {
                return 0;
            }
        }

    } else{ return "invalid token" ;}


}

function get_merchant_API($gateway, $merchant_id){

    if(!check_merchantAPI($gateway, $merchant_id)){
       $merchant_id = 1;
    }
    // check if merchant has api 
  $api_keys = [];
    $data = getData("SELECT gateway, username, password, merchant_no, device_type, machine_id, channel_id FROM gateway_keys WHERE merchant_id = '$merchant_id' AND gateway = '$gateway'");
    if(!empty($data)){
        while($raw = mysqli_fetch_array($data)){

         
            $api_keys[$raw['gateway']] = [
                'username' => $raw['username'],
                'password'=> $raw['password'],
                'merchant_no'=> $raw['merchant_no'],
            ];

            if($raw['gateway'] == "zaad"){
                $api_keys[$raw['gateway']]['payment_method'] = "MWALLET_ACCOUNT";
            }
            elseif($raw['gateway'] == "pbwallet"){
                $api_keys[$raw['gateway']]['machine_id'] = $raw['machine_id'];
                $api_keys[$raw['gateway']]['channel_id'] = $raw['channel_id'];
                $api_keys[$raw['gateway']]['device_type'] = $raw['device_type'];
            }
        }
        return $api_keys;
    }else{
        return "invalid merchant";

    }

}
function check_merchantAPI($gateway, $merchant_id){

    $data = getData("SELECT * FROM gateway_keys WHERE merchant_id = '$merchant_id' AND gateway = '$gateway'");
    if(!empty($data)){
        return true;
    }else{
        return false;
    }
}
// call this function to save log
// log_this(
//     array(
//         "action"=>"insert",
//         "table"=>"account",
//         "values"=>"ali, ali@gmail.com",
//         "detail"=>"new user created."
//     ), "activity" // log type
// );

// log_this(
//     array(
//         "txn_type"=>"debit",
//         "txn_id"=>"123456789",
//         "gateway"=>"zaad",
//         "amount"=>"15",
//         "currency"=>"USD",
//         "txn_status"=>"success",
//         "merchant"=>"001",
//         "detail"=>"debit txn."
//     ), "txn" // log type
// );

function isSaveWalletEnabled($gateway, $merchant_id) {
    // Check if merchant has an API
    $merchant_has_api = check_merchantAPI($gateway, $merchant_id) ?? false;

    if ($merchant_id == 1) {
        return true;
    } else if (!$merchant_has_api) {
        return true;
    } else {
        return false;
    }
}
