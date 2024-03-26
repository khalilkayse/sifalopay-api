<?php
function validatePhone($input_data, $gateway)
{
    $phoneLength = strlen($input_data);
    if (strtolower($gateway) == "zaad") {
        switch ($input_data) {
                #VALID PHONES: 252631234567
            case (preg_match("/^[252]{3}[63190852]{2}[0-9]{7}$/", $input_data) ? true : false):
                $phone = substr($input_data, 3);
                $pcode = substr($phone, 0, 2);
                if ($pcode == 63) {
                    return "252" . $phone;
                }
                if ($pcode == 61) {
                    return "252" . $phone;
                }
                if ($pcode == 90) {
                    return "252" . $phone;
                }
                if ($pcode == 68) {
                    return "252" . $phone;
                }
                #VALID PHONES: 0631234567, 631234567
            case (preg_match("/^[0]*[0-9]{2}[0-9]{7}$/", $input_data) ? true : false): // eg. 0631234567
                if (strlen($input_data) == 10) {
                    $phone = substr($input_data, 1);
                } else $phone = $input_data;
                $pcode = substr($phone, 0, 2);
                if ($pcode == 63) {
                    return "252" . $phone;
                }
                if ($pcode == 61) {
                    return "252" . $phone;
                }
                if ($pcode == 90) {
                    return "252" . $phone;
                }
                if ($pcode == 68) {
                    return "252" . $phone;
                }
                #VALID PHONES: 252-63-1234567
            case (preg_match("/^[252]{3}[-_ ][63190852]{2}[-_ ][0-9]{7}$/", $input_data) ? true : false):

                $phone = substr($input_data, 4);
                $pcode = substr($phone, 0, 2);
                $nphone = substr($input_data, 7);
                if ($pcode == 63) {
                    return "25263" . $nphone;
                }
                if ($pcode == 61) {
                    return "25261" . $nphone;
                }
                if ($pcode == 90) {
                    return "25290" . $nphone;
                }
                if ($pcode == 68) {
                    return "25268" . $nphone;
                }
                #VALID PHONES: 063-1234567, 063 1234567, 63-1234567, 63 1234567
            case (preg_match("/^[0]*[0-9]{2}[-_ ][0-9]{7}$/", $input_data) ? true : false):
                if (strlen($input_data) == 11) {
                    $phone = substr($input_data, 1);
                    $nphone = substr($input_data, 4);
                }
                if (strlen($input_data) == 10) {
                    $phone = $input_data;
                    $nphone = substr($input_data, 3);
                }
                $pcode = substr($phone, 0, 2);

                if ($pcode == 63) {
                    if (strlen($input_data) == 11) {
                    }
                    return "25263" . $nphone;
                }
                if ($pcode == 61) {
                    return "25261" . $nphone;
                }
                if ($pcode == 90) {
                    return "25290" . $nphone;
                }
                if ($pcode == 68) {
                    return "25268" . $nphone;
                }

            default:
                return $input_data; //invalid phone format
        }
    } else if (strtolower($gateway) == "edahab") {
        switch ($input_data) {
                #VALID PHONES: 252631234567
            case (preg_match("/^[252]{3}[63190852]{2}[0-9]{7}$/", $input_data) ? true : false):
                $phone = substr($input_data, 3);
                $pcode = substr($phone, 0, 2);

                if ($pcode == 65) {
                    return $phone;
                }
                if ($pcode == 62) {
                    return $phone;
                }
                #VALID PHONES: 0631234567, 631234567
            case (preg_match("/^[0]*[0-9]{2}[0-9]{7}$/", $input_data) ? true : false): // eg. 0631234567
                if (strlen($input_data) == 10) {
                    $phone = substr($input_data, 1);
                } else $phone = $input_data;
                $pcode = substr($phone, 0, 2);

                if ($pcode == 65) {
                    return $phone;
                }
                if ($pcode == 62) {
                    return $phone;
                }
                #VALID PHONES: 252-63-1234567
            case (preg_match("/^[252]{3}[-_ ][63190852]{2}[-_ ][0-9]{7}$/", $input_data) ? true : false):

                $phone = substr($input_data, 4);
                $pcode = substr($phone, 0, 2);
                $nphone = substr($input_data, 7);

                if ($pcode == 65) {
                    return "65" . $nphone;
                }
                if ($pcode == 62) {
                    return "62" . $nphone;
                }
                #VALID PHONES: 065-1234567, 065 1234567, 65-1234567, 63 1234567
            case (preg_match("/^[0]*[0-9]{2}[-_ ][0-9]{7}$/", $input_data) ? true : false):
                if (strlen($input_data) == 11) {
                    $phone = substr($input_data, 1);
                    $nphone = substr($input_data, 4);
                }
                if (strlen($input_data) == 10) {
                    $phone = $input_data;
                    $nphone = substr($input_data, 3);
                }
                $pcode = substr($phone, 0, 2);


                if ($pcode == 65) {
                    return "65" . $nphone;
                }
                if ($pcode == 62) {
                    return "62" . $nphone;
                }

            default:
                return $input_data; //invalid phone format
        }
    } else if (strtolower($gateway) == "pbwallet") {
        if (preg_match("/[+]*[0-9_\- ]*[0-9]+$/", $input_data) ? true : false) {

            $phone = $input_data;
            $pcode = substr($phone, 0, 2);
            if ($pcode == "00") {
                if (strlen($input_data) >= 13) {
                    return preg_replace('/[^0-9]/', '', $phone);
                } else {
                    return $input_data; //invalid phone format
                }
            } else {
                if (strlen($input_data) >= 11) {
                    return preg_replace('/[^0-9]/', '', "00" . $phone);
                } else {
                    return $input_data; //invalid phone format
                }
            }
        } else {
            return $input_data; //invalid phone format
        }
    }
}
?>