<?php

function generateGRandomString($length = 10) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// this function holds static api variables configs
function api_globals(){

    $date = date("Y-m-d", time()) . " Africa";

    $api_globals = array(
    "http_v" => "CURL_HTTP_VERSION_1_1", // http request type
    "post_url" => "https://api.waafipay.net/asm",
    "api_version" => "1.0", //schemaVersion
    "channel" => "WEB",
    "api_userID" => get_merchant_API()['zaad']['username'],
    "api_key" => get_merchant_API()['zaad']['password'],
    "merchant_no" => get_merchant_API()['zaad']['merchant_no'],
    "payment_method" => "MWALLET_ACCOUNT",
    "preAuth_detial" => "Transaction Sent",
    "preAuth_commit_detial" => "Commited",
    "credit_detail" => "Account Deposit Completed",
    "time" => "$date");
    
    return $api_globals;
}


// preAuth Transaction
function preAuth_pay($req_id, $account_no, $ref_id, $inv_id, $amount, $currency){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => api_globals()['post_url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => "'.api_globals()['http_v'].'",
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
        "schemaVersion"     : "'.api_globals()['api_version'].'",
        "requestId"         : "'.$req_id.'",
        "timestamp"         : "'.api_globals()['time'].'",
        "channelName"       : "'.api_globals()['channel'].'",
        "serviceName"       : "API_PREAUTHORIZE",
        "serviceParams": {
            "merchantUid": "'.api_globals()['merchant_no'].'",
            "apiUserId": "'.api_globals()['api_userID'].'",
            "apiKey": "'.api_globals()['api_key'].'",
            "paymentMethod": "'.api_globals()['payment_method'].'",
            "payerInfo": {
                "accountNo": "'.$account_no.'"
            },
            "transactionInfo": {
                "referenceId": "'.$ref_id.'",
                "invoiceId": "'.$inv_id.'",
                "amount": "'.$amount.'",
                "currency": "'.$currency.'",
                "description": "'.api_globals()['preAuth_detial'].'"
            }
        }

        }
    }',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    return $response = curl_exec($curl);
    $err     = curl_errno( $curl );
    $errmsg  = curl_error( $curl );
    
}

function preAuth_commit($req_id, $txn_id, $ref_id){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => api_globals()['post_url'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => "'.api_globals()['http_v'].'",
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
        "schemaVersion"     : "'.api_globals()['api_version'].'",
        "requestId"         : "'.$req_id.'",
        "timestamp"         : "'.api_globals()['time'].'",
        "channelName"       : "'.api_globals()['channel'].'",
        "serviceName"       : "API_PREAUTHORIZE_COMMIT",
        "serviceParams": {
            "merchantUid": "'.api_globals()['merchant_no'].'",
            "apiUserId": "'.api_globals()['api_userID'].'",
            "apiKey": "'.api_globals()['api_key'].'",
            "transactionId": "'.$txn_id.'",
            "referenceId": "'.$ref_id.'",
            "description": "'.api_globals()['preAuth_commit_detial'].'"
        }

        }
    }',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));

    return $response = curl_exec($curl);
    $err     = curl_errno( $curl );
    $errmsg  = curl_error( $curl );
    
}

function credit_account($req_id, $account_no, $ref_id, $inv_id, $amount, $currency, $account_type="CUSTOMER") {

  if($account_type != "CUSTOMER"){
    $account_no = "018".$account_no;
  }
  if(empty($account_type)){
    $account_type = "CUSTOMER";
  }

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => api_globals()['post_url'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => "'.api_globals()['http_v'].'",
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
      "schemaVersion"     : "'.api_globals()['api_version'].'",
      "requestId"         : "'.$req_id.'",
      "timestamp"         : "'.api_globals()['time'].'",
      "channelName"       : "'.api_globals()['channel'].'",
      "serviceName"       : "API_CREDITACCOUNT",
      "serviceParams": {
          "merchantUid": "'.api_globals()['merchant_no'].'",
          "apiUserId": "'.api_globals()['api_userID'].'",
          "apiKey": "'.api_globals()['api_key'].'",
          "paymentMethod": "'.api_globals()['payment_method'].'",
          "payerInfo": {
              "accountNo": "'.$account_no.'",
              "accountType": "'.$account_type.'",
              "accountHolder": "Sifalo Customer"
          },
          "transactionInfo": {
              "referenceId": "'.$ref_id.'",
              "invoiceId": "'.$inv_id.'",
              "amount": "'.$amount.'",
              "currency": "'.$currency.'",
              "description": "'.api_globals()['credit_detail'].'"
          }
      }

      }
  }',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
    ),
  ));

  return $response = curl_exec($curl);
  $err     = curl_errno( $curl );
  $errmsg  = curl_error( $curl );
  
  
}