<?php 
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $curl = curl_init();
        curl_setopt ($curl, CURLOPT_URL, $actual_link."/service/get-setup-status?setup=true");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec ($curl);

        $jsonResult = json_decode($result);
        curl_close ($curl);

        if($jsonResult->data->config == true && $jsonResult->data->database == true){
                header('Location: '.$actual_link.'mom');
        }else{
                header('Location: '.$actual_link.'setup');
        }
        
?>