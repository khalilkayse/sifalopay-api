<?php require "../core.php";
// this is the verify txn page hosted on the gateway

$from_api_call = json_decode(file_get_contents('php://input'), true);

$sid= $from_api_call['sid'];


function getAccountHolder($paymentType, $txn_id)
{
    // Connect to the database
    $mysqli = $GLOBALS['con'];
    // output any connection error
    if ($mysqli->connect_error) {
        die('Error : (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }
    if ($paymentType == "ZAAD") {
        $query = "SELECT account_no from zaad where txn_id = '$txn_id' ";
    }
    if ($paymentType == "EDAHAB") {
        $query = "SELECT account as account_no from edahab where txn_id = '$txn_id' ";
    }
    if ($paymentType == "PREMIER WALLET") {
        $query = "SELECT account as account_no from pbwallet where txn_id = '$txn_id' ";
    }
    if ($paymentType == "CARD") {
        $query = "SELECT account as account_no from stripe where txn_id = '$txn_id' ";
    }
    $results = $mysqli->query($query);

    if ($results) {
        if ($results->num_rows === 0) {
            return "";
        } else {

            $list = mysqli_fetch_array($results);
            extract($list);
            return $list['account_no'];
        }
    } else {
        return "";
    }
}

function verifyTransaction($sid)
{
    $con = $GLOBALS['con'];
    $data = array();
    $sql = "SELECT total_amount,account,payment_type,txn_status,sid, txn_id  FROM transaction WHERE sid = '$sid'";
    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        $list = mysqli_fetch_assoc($result);
        $data['sid'] = $list['sid'];
        $data['account'] = getAccountHolder($list['payment_type'], $list['txn_id']);
        $data['payment_type'] = $list['payment_type'];
        $data['amount'] = $list['total_amount'];
        switch ($list['txn_status']) {
            case "approved":
                $data['status'] = "success";
                $data['code'] = 601;
                break;
            case "executed":
                $data['status'] = "success";
                $data['code'] = 601;
                break;
            case "paid":
                $data['status'] = "success";
                $data['code'] = 601;
                break;
            case "succeeded":
                $data['status'] = "success";
                $data['code'] = 601;
                break;
            case "failed":
                $data['status'] = "failed";
                $data['code'] = 600;
                break;
            case "pending":
                $data['status'] = "failed";
                $data['code'] = 600;
                break;
            case "rejected":
                $data['status'] = "failed";
                $data['code'] = 600;
                break;
            case "invalid":
                $data['status'] = "failed";
                $data['code'] = 600;
                break;
            default:
                $data['status'] = "failed";
                $data['code'] = 600;
                break;
        }
    } else {
        $data['sid'] = null;
        $data['account'] = null;
        $data['payment_type'] = null;
        $data['amount'] = null;
        $data['status'] = "failed";
        $data['code'] = 600;
    }
    return json_encode($data);
}

if (isset($from_api_call['sid']) && !empty($from_api_call['sid'])) {
	
	header('Content-Type: application/json; charset=utf-8');  
    echo verifyTransaction($from_api_call['sid']);

}
