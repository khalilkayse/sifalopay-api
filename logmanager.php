<?php // this module is built to save/retrive logs and manage log folders/files.

function save_log($data){

    $current_year = date("Y", time());
    $current_month = date("m", time());
    $folder = "logs/".$current_year."/".$current_month;

    // if directory doesn't exist create it
    if(!is_dir($folder)){
        mkdir($folder, 0777, true);
    }

    $filename= date("Y-m-d", time()).".json";

    if(file_exists($folder."/".$filename)){

        $current_file_data = json_decode(file_get_contents($folder."/".$filename), true);

        array_push($current_file_data, $data);

        $data_to_record = $current_file_data;

       
    }else{

        $data_to_record = array($data);
    }

    if(!file_put_contents($folder."/".$filename, json_encode($data_to_record), LOCK_EX)){
        
        file_put_contents($folder."/".$filename, json_encode($data_to_record), LOCK_EX);
    }

    
}

function read_log($filename){

    $current_year = date("Y", time());
    $current_month = date("m", time());
    $folder = "logs/".$current_year."/".$current_month;

    if(is_dir($folder)){

        if(file_exists($folder."/".$filename)){

            return json_decode(file_get_contents($folder."/".$filename), true);

        }else{
            echo "file not found!";
        }

    }else{
        echo "folder not found!";
    }
    
}

