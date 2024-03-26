<?php

function api_globals(){

    $api_globals = array(
        "succ_code" => "0",
        "succ_status" => "Paid",
        "credit_succ_status" => "Approved",
        "apiKey" => "oM395cbpeDwuUYPyRKxIbnerxstNkTX8PT11uA", 
        "agent" => "711674", // this is sifalo edahab marchant account
        "secret" => "SHSDXiw03Mu1miFD1MWkm4QXSkx36gzbbfpF3r",
        "debit_url" => "https://edahab.net/api/api/IssueInvoice?hash=",
        "credit_url" => "https://edahab.net/api/api/agentPayment?hash="
    );
    
    return $api_globals;
}

function debit_txn($account, $amount, $inv_id, $currency){

    $req_params = array(
        "apiKey" => api_globals()['apiKey'], 
        "EdahabNumber" => $account, 
        "Amount" => $amount, 
        "AgentCode" => api_globals()['agent'],
        "Currency" => $currency,
        "ThirdPartyInvoiceNo" => $inv_id
        
    );

    // Encode it into a JSON string.
    $json = json_encode($req_params, JSON_UNESCAPED_SLASHES);
    // generate hash    
    $hashed = hash('SHA256', $json . api_globals()['secret']);

    $url = api_globals()['debit_url'] . $hashed;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // check if payment is approved
    if($response['StatusCode'] == api_globals()['succ_code'] && $response['InvoiceStatus'] == api_globals()['succ_status']){
        return array(true, $response);
    }else{
        return array(false, $response);
    }
}

function credit_txn($account, $amount, $txn_id, $currency){

    $req_params = array(
        "apiKey" => api_globals()['apiKey'], 
        "phoneNumber" => $account, 
        "transactionAmount" => $amount, 
        "transactionId" => $txn_id
        
    );

    // Encode it into a JSON string.
    $json = json_encode($req_params, JSON_UNESCAPED_SLASHES);
    // generate hash    
    $hashed = hash('SHA256', $json . api_globals()['secret']);

    $url = api_globals()['credit_url'] . $hashed;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // check if payment is approved
    if($response['TransactionStatus'] == api_globals()['credit_succ_status']){
        return array(true, $response);
    }else{
        return array(false, $response);
    }
}

