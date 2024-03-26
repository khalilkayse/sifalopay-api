<?php

// this function holds static api variables configs
function api_globals($merchant_id){

    $api_globals = array(
    'http_v' => 'CURL_HTTP_VERSION_1_1', // http request type
    'channel' => 'WEB',
    'LoginUserName' => get_merchant_API('pbwallet', $merchant_id)['pbwallet']['merchant_no'],
    'login_url' => 'https://api.premierwallets.com/api/MerchantLogin',
    'push_payment_url' => 'https://api.premierwallets.com/api/PushPayment',
    'get_payment_url' => 'https://api.premierwallets.com/api/GetPaymentDetails',
    'topup_url' => 'https://api.premierwallets.com/api/TopupOtherWallet',
    'api_user' => get_merchant_API('pbwallet', $merchant_id)['pbwallet']['username'],
    'api_pass' => ''.strip_tags(get_merchant_API('pbwallet', $merchant_id)['pbwallet']['password']),
    'machine_id' => get_merchant_API('pbwallet', $merchant_id)['pbwallet']['machine_id'],
    'channel_id' => get_merchant_API('pbwallet', $merchant_id)['pbwallet']['channel_id'],
    'device_type' => get_merchant_API('pbwallet', $merchant_id)['pbwallet']['device_type'],
    'Remarks' => 'Sifalo Pay',
    'topup_fee' => "0.0",
    'Category' => '1',
    'succ_code' => '001');

    return $api_globals;
}
	
function pbwallet_api_login($merchant_id){

    $curl = curl_init();
    $api_globals = api_globals($merchant_id);
    $authorization = base64_encode($api_globals['api_user'].':'.$api_globals['api_pass']);

    curl_setopt_array($curl, array(
      CURLOPT_URL => $api_globals['login_url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 15,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => "".$api_globals['http_v']."",
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        "Content-Length: 0",
        "MachineID: ".$api_globals['machine_id'],
        "ChannelID: ".$api_globals['channel_id'],
        "DeviceType: ".$api_globals['device_type'],
        "Authorization: Basic $authorization"
      ),
    ));

    $response = json_decode(curl_exec($curl), true);

    if($response['Response']['Code'] == $api_globals['succ_code']){
        return array(true, $response['Data']['Token']);
    }else{
        return array(false, $response);
    }

    
}

function push_payment($account, $amount, $token, $merchant_id){

    $curl = curl_init();

    $api_globals = api_globals($merchant_id);
    $post_data = [
        "CustomerWalletID" => $account,
        "Amount" => $amount,
        "Remarks" => $api_globals['Remarks'],
        "Category" => $api_globals['Category'],
        "LoginUserName" => $api_globals['LoginUserName']
    ];
    curl_setopt_array($curl, array(
      CURLOPT_URL => $api_globals['push_payment_url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 15,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => "".$api_globals['http_v']."",
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode($post_data),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        "MachineID: ".$api_globals['machine_id'],
        "ChannelID: ".$api_globals['channel_id'],
        "DeviceType: ".$api_globals['device_type'],
        "Authorization: Bearer $token"
      ),
    ));

    $response = json_decode(curl_exec($curl), true);

    if($response['Response']['Code'] == $api_globals['succ_code']){
        return array(true, $response['Data']['TransactionID'], $token);
    }else{
        return array(false, $response);
    }
}
// very payment status
function get_payment($txn_id, $token, $merchant_id){

    $curl = curl_init();
    $api_globals = api_globals($merchant_id);

    $post_data = [
        "TransactionID" => $txn_id,
        "LoginUserName" => $api_globals['LoginUserName']
    ];
    curl_setopt_array($curl, array(
      CURLOPT_URL => $api_globals['get_payment_url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => "".$api_globals['http_v']."",
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode($post_data),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        "MachineID: ".$api_globals['machine_id'],
        "ChannelID: ".$api_globals['channel_id'],
        "DeviceType: ".$api_globals['device_type'],
        "Authorization: Bearer $token"
      ),
    ));

    $response = json_decode(curl_exec($curl), true);

    if($response['Response']['Code'] == $api_globals['succ_code']){
        return array(true, $response);
    }else{
        return array(false, $response);
    }
}

function top_up($account, $amount, $token, $merchant_id){

  $curl = curl_init();
  
  $api_globals = api_globals($merchant_id);

  $post_data = [
      "walletId" => $account,
      "amount" => $amount,
      "fee" => $api_globals['topup_fee'],
      "LoginUserName" => $api_globals['LoginUserName']
  ];
  curl_setopt_array($curl, array(
    CURLOPT_URL => $api_globals['topup_url'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 15,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => "".$api_globals['http_v']."",
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($post_data),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      "MachineID: ".$api_globals['machine_id'],
      "ChannelID: ".$api_globals['channel_id'],
      "DeviceType: ".$api_globals['device_type'],
      "Authorization: Bearer $token"
    ),
  ));

  $response = json_decode(curl_exec($curl), true);

  if($response['Response']['Code'] == $api_globals['succ_code']){
      return array(true, $response);
  }else{
      return array(false, $response);
  }
}