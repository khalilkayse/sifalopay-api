<?php require "config.php";

// $con = Create_db();
// INSERT FUNCTION: $request_data should be an array
function insert_action($db_tbl_name, $request_data)
{
    // retrieve the keys of the array (column titles)
    $fields = array_keys($request_data);

    // build the query
    $sql = "INSERT INTO " . $db_tbl_name . "
    (`" . implode('`,`', $fields) . "`)
    VALUES('" . implode("','", $request_data) . "')";

    //Create a prepared statement
    $stmt = mysqli_stmt_init($GLOBALS['con']);
    //prepare the prepared statement
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        
        //return display_message("error", "SQL error: Enable to save Data");
        return display_message("error", mysqli_error($GLOBALS['con']));
    } else {
        mysqli_stmt_execute($stmt);
        return  display_message("success", "Data saved Successfully");
    }
}
// update statement
// function update_action($db_tbl_name, $request_data)
// {
//     // Assuming the first element of $request_data contains the unique identifier for the record
//     $id_field = array_key_first($request_data);
//     $id_value = $request_data[$id_field];
//     unset($request_data[$id_field]); // Remove the identifier from the update data

//     // Start building the SQL query
//     $sql = "UPDATE " . $db_tbl_name . " SET ";
//     $updates = [];
//     foreach ($request_data as $field => $value) {
//         $updates[] = "`" . $field . "` = ?";
//     }
//     $sql .= implode(', ', $updates);
//     $sql .= " WHERE `" . $id_field . "` = ?";

//     // Create a prepared statement
//     $stmt = mysqli_stmt_init($GLOBALS['con']);
//     // Prepare the prepared statement
//     if (!mysqli_stmt_prepare($stmt, $sql)) {
//         // Return error message if SQL preparation fails
//         return display_message("error", mysqli_error($GLOBALS['con']));
//     } else {
//         // Bind the parameters to the statement
//         $types = str_repeat('s', count($request_data)) . 's'; // All parameters are strings
//         mysqli_stmt_bind_param($stmt, $types, ...array_values($request_data), $id_value);

//         // Execute the statement
//         mysqli_stmt_execute($stmt);

//         // Check if any rows were updated
//         if (mysqli_stmt_affected_rows($stmt) > 0) {
//             return display_message("success", "Data updated Successfully");
//         } else {
//             return display_message("info", "No data was updated. Check your input.");
//         }
//     }
// }


// get data from mysql database
function getData($sql)
{

    $result = mysqli_query($GLOBALS['con'], $sql);

    if (mysqli_num_rows($result) > 0) {
        return $result;
    }
}
// set id to textbox
function setID($sql)
{
    $getid = getData($sql);
    $id = 0;
    if ($getid) {
        while ($row = mysqli_fetch_assoc($getid)) {
            $id = $row['id'];
        }
    }
    return ($id + 1);
}

//check if textbox is empty and valide if value is between 6 - 20 char
function check_if_empty($value)
{
    $textbox = mysqli_real_escape_string($GLOBALS['con'], trim($_POST[$value]));
    // $textbox = trim($GLOBALS['con'], trim($_POST[$value]));
    // $textbox = stripslashes($GLOBALS['con'], trim($_POST[$value]));
    // $textbox = htmlspecialchars($GLOBALS['con'], trim($_POST[$value]));

    if (empty($textbox)) {
        return display_message("error", $value . " cannot be empty");
        // return false;
    } else {

        return $textbox;
    }
}
//  Validate Inputs
function validate_input($input_value)
{
    // check user enters to avoid Cross-site scripting
    $input_data = check_if_empty($input_value);
    if (!preg_match("/^[a-z0-9A-Z-' ]*$/", $input_data)) {
        return display_message("error", "Invalid Input! Only letters and white space allowed.");
    } else {
        return $input_data;
    }
}
// validate phone number
function validate_phone($phone_value)
{
    $input_data = check_if_empty($phone_value);
    if (preg_match("/^(\+\d{1,2}\s)?\(?\d{3}\)?[\s.-]\d{2}[\s.-]\d{7}$/", $input_data)) {
        // $phone is valid
        return $input_data;
    }else{
        return display_message("error", "Invalid phone number.");
    }
}
//  Validate E-mail
function validate_email($email_value)
{
    // check user enters to avoid Cross-site scripting
    $email_data = check_if_empty($email_value);
    if (!filter_var($email_data, FILTER_VALIDATE_EMAIL)) {
        return display_message("error", "Invalid email format.");
    } else {
        return $email_data;
    }
}

// checks if POST FIELDS present or not
function validate_form($data)
{
    foreach ($GLOBALS['fields'] as $field) {
        if (!array_key_exists($field, $data)) {
            return display_message("error", $field . " is not present in the data");
        }
    }
}

// Displays messages
function display_message($msg_type, $msg)
{
    $element = "";
    if ($msg_type == "error") {
        $element =
            "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
        $msg
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
         <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
    }
    if ($msg_type == "success") {
        $element =
            "<div class='alert alert-success alert-dismissible fade show' role='alert'>
        $msg
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
         <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
    }

    return $element;
}
