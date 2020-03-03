<?php

function addNotification(mysqli $connection,$sender_id,$receiver_ids,$media_type,$feed_id,$noti_message,$is_testdata)
{
    $status = 2;
    $is_delete=DELETE_STATUS::NOT_DELETE;
    $insertFields = " sender_id,receiver_ids, media_type, feed_id, message, is_delete ,is_testdata, created_date,modified_date";
    $getCurrentDate=getDefaultDate();
    $insert_query = "Insert into ". TABLE_NOTIFICATIONS ." (".$insertFields.") values(?,?,?,?,?,?,?,?,?)";
    if($insertStmt = $connection->prepare($insert_query)) {
        $insertStmt->bind_param('sssssssss', $sender_id, $receiver_ids ,$media_type,$feed_id,$noti_message,$is_delete, $is_testdata,$getCurrentDate,$getCurrentDate);
        if ($insertStmt->execute()) {
            $insertStmt->close();
            //get device token of the user
            $data['status']=1;
        }
        else
        {
            $data['message']=$insertStmt->error;
            $data['status']=2;
        }
    }
    else
    {
        $data['message']=$connection->error;
        $data['status']=2;
    }
    return $data;
}

function checkAndUpdateDeviceToken(mysqli $connection,$user_id,$device_token){
    //select `device_token` from users where userid!= 1 and device_token ='eNBj6i1zYqI:APA91bHNiZy0T1-C4yqM22jUpimfjOSQGgceCc8szkp-JIh367SGXCtkqfBv54cE-ICDfFRL0XDArK3k7RzzWPN3a9YxOBbxq5fZsIwZN-32gvctr6HvwIzZMuzC1tx6j1hdovJAi1pz'
    $selectQuery = "SELECT `id` FROM " . TABLE_USER . " WHERE  `id` != ? AND device_token = ? AND is_delete='0'";
    if ($select_stmt = $connection->prepare($selectQuery)) {
        $select_stmt->bind_param("ss", $user_id,$device_token);
        $select_stmt->execute();
        $select_stmt->store_result();
        if ($select_stmt->num_rows > 0) {
            while($val=fetch_assoc_all_values($select_stmt)){
                $updatQuery = "UPDATE " . TABLE_USER . " SET device_token = NULL WHERE id = ? AND is_delete='0'";
                if ($update_stmt = $connection->prepare($updatQuery)) {
                    $update_stmt->bind_param('s', $val['id']);
                    if ($update_stmt->execute()) {
                        $update_stmt->close();
                        $status = SUCCESS;
                    }
                    else{
                        $status=FAILED;
                    }
                }
            }
        }
        else{
            $status=SUCCESS;
        }
        $select_stmt->close();
    }
}

function updateGuidForUser(mysqli $connection,$user_id,$is_testdata)
{
    $data=array();
    $is_delete=DELETE_STATUS::NOT_DELETE;
    $selectQuery = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
    FROM " . TABLE_USER . " as u WHERE u.id = ? AND u.is_delete=? AND u.is_testdata = ?";
    if ($select_review_stmt = $connection->prepare($selectQuery)){
        $select_review_stmt->bind_param("sss",$user_id,$is_delete,$is_testdata);
        $select_review_stmt->execute();
        $select_review_stmt->store_result();
        if($select_review_stmt->num_rows > 0)
        {
            $security= new  SecurityFunctions($connection);
            $generate_guid = $security->gen_uuid();

            $updatQuery = "UPDATE " . TABLE_USER . " SET guid = ? WHERE id = ? AND is_delete=? AND is_testdata = ?";
            if ($update_stmt = $connection->prepare($updatQuery)) {
                $update_stmt->bind_param('ssss', $generate_guid,$user_id,$is_delete,$is_testdata);
                if ($update_stmt->execute()) {
                    $update_stmt->close();
                    return $generate_guid;
                }
            }
        }
        else{
        }
        $select_review_stmt->close();
    }


    return $data;
}

function getUserInformation(mysqli $connection,$user_id,$is_testdata)
{
    $is_delete=DELETE_STATUS::NOT_DELETE;
    $query="Select id,CONCAT(firstname,' ',lastname) as `name`,profilepic,description as user_description from " . TABLE_USER . " where id = '".$user_id."' and is_delete= '".$is_delete."'  AND is_testdata = '".$is_testdata."'";
    if ($select_stmt = $connection->prepare($query)) {

        //$select_stmt->bind_param("ss", $user_id,$is_testdata);
        $select_stmt->execute();
        $select_stmt->store_result();

        if($select_stmt->num_rows > 0)
        {
            $user_array = fetch_assoc_stmt($select_stmt);
            $user['user_id']=$user_array[0]['id'];
            $user['name']=$user_array[0]['name'];
            $user['profilepic']=$user_array[0]['profilepic'];

            return $user;
        }
        else{
            return null;
        }

        $select_stmt->close();
    }
    else{
       return null;
    }
}

function sendNotification(mysqli $connection,$user_id,$message){

    $android_device_token_array = array();
    $ios_device_token_array = array();

    //device_tpe = 1 Android and device_tpe = 2 iOS

    $queryUser = "SELECT device_token,device_type FROM " . TABLE_USER . "  WHERE id = ? AND device_token IS NOT NULL AND is_delete ='".DELETE_STATUS::NOT_DELETE."'";
    if ($user_push_stmt = $connection->prepare($queryUser)) {
        $user_push_stmt->bind_param("i", $user_id);
        $user_push_stmt->execute();
        $user_push_stmt->store_result();

        if ($user_push_stmt->num_rows > 0) {

            $user_obj = fetch_assoc_stmt($user_push_stmt);
            $get_device_type = $user_obj[0]['device_type'];
            $get_device_token = $user_obj[0]['device_token'];

            if ($get_device_type == 1) {
                $ios_device_token_array[] = $get_device_token;
            } elseif ($get_device_type == 2) {
                $android_device_token_array[] = $get_device_token;
            }

            $push_info = array();

            $send_push = new FCM();
            //$ios_device_token_array[]="40b0d354fd6cd2f329d2c5cd36bde993f5938ecf388abcba0efe5e625d8a00d3";
            if (!empty($ios_device_token_array)) {
                //	For IOS
                $Notifsubject = APPNAME;
                $pushMessage = $message;
                $deviceToken = array();
                $deviceToken[] = $get_device_token;
                $isReject = '0';
                $send_push->sendPushIOS($ios_device_token_array, $Notifsubject, $pushMessage, $isReject);
            }
            if (!empty($android_device_token_array)) {
                //For Android

                //false means notification type => data
                //true means notification type => notification

                $android_device_token_array = array_unique($android_device_token_array);

                $send_notifications = array();
                $send_notifications = array_values($android_device_token_array);

                $message_body = $message;//'Your r
                //equest has been accepted ';

                $data = array("Notification_Type" => 1, "body" => $message_body, "Information" => $push_info);
                $send_push->send_gcm_notify($send_notifications, false, $data);
            }

        }
        $user_push_stmt->close();
    }
}

function sendIosNotification(mysqli $connection,$user_id,$notification_type, $pushType = array(), $data = array()) {   
     
    $is_delete = DELETE_STATUS::NOT_DELETE;    
    $queryUser = "SELECT device_token,device_type FROM " . TABLE_APP_TOKENS . "  WHERE user_id = ? AND device_token IS NOT NULL AND is_delete ='".$is_delete."'";

    $message = '';

    if ($user_push_stmt = $connection->prepare($queryUser)) {
        
        $user_push_stmt->bind_param("i", $user_id);
        $user_push_stmt->execute();
        $user_push_stmt->store_result();

        if ($user_push_stmt->num_rows > 0) {
            
            $user_obj = fetch_assoc_stmt($user_push_stmt);
            $get_device_type = $user_obj[0]['device_type'];
            $get_device_token = $user_obj[0]['device_token'];

            if ($get_device_type == 1) {

                $firstName = '';
                $lastName = '';
                $fullName = '';
                $select_user_query = "SELECT first_name, last_name,description as user_description  FROM ".TABLE_USER." WHERE user_id = ? AND is_delete='".$is_delete."'";

                if ($select_user_stmt = $connection->prepare($select_user_query)) {

                    $select_user_stmt->bind_param("i", $data[0]['sender_id']);
                    $select_user_stmt->execute();
                    $select_user_stmt->store_result();

                    if ($select_user_stmt->num_rows > 0) {

                        $user_post = fetch_assoc_stmt($select_user_stmt);
                        
                        $data[0]['sender_first_name'] = $user_post[0]['first_name'];
                        $data[0]['sender_last_name'] = $user_post[0]['last_name'];

                        $fullName = $data[0]['sender_first_name'].' '.$data[0]['sender_last_name'];

                        if(NOTIFICATION_TYPE_FOLLOW == $notification_type){

                            $message = $fullName." started following you.";  

                        }else if(NOTIFICATION_TYPE_REQUEST_PENDING == $notification_type){

                            $message = $fullName." sent a request to follow you.";

                        }else if(NOTIFICATION_TYPE_COMMENT == $notification_type){

                            $message = $fullName." commented on your post.";

                        }else if(NOTIFICATION_TYPE_LIKE == $notification_type){

                            $message = $fullName." liked your post.";

                        }else if(NOTIFICATION_TYPE_ADD_MEMBER_GROUP == $notification_type){

                            $message = $fullName." added you in the group.";

                        }else if(NOTIFICATION_TYPE_ADD_ME_MEMBER_GROUP == $notification_type){

                            $message = "You have joined in the group.";

                        }else if(NOTIFICATION_TYPE_UPLOAD_POST_GROUP == $notification_type){

                            $message = $fullName." uploaded a new post.";

                        }

                        // --- For testing ----
                        //$pemFile = SERVER_PUSH_NOTIFICATION.'Push_SandBox.pem';  //Testing Sandbox
                        //$appleUrl = 'ssl://gateway.sandbox.push.apple.com:2195'; //Testing Sandbox
                        
                        $pemFile = SERVER_PUSH_NOTIFICATION.'Push_Prod.pem';
                        $appleUrl = 'ssl://gateway.push.apple.com:2195';
                        $passphrase = ''; 
                        $ctx = stream_context_create();
                        stream_context_set_option($ctx, 'ssl', 'local_cert', $pemFile);
                        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
                        // Open a connection to the APNS server
                        $fp = stream_socket_client($appleUrl, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
                        if (!$fp)
                            exit("Failed to connect: $err $errstr" . PHP_EOL);

                        $select_badge_counter = "SELECT is_read  FROM ".TABLE_NOTIFICATION." WHERE received_by = ? AND is_read = 0 AND is_delete='".$is_delete."' AND is_testdata='".$data[0]['is_testdata']."'";
                        $select_badge_stmt = $connection->prepare($select_badge_counter);
                        $select_badge_stmt->bind_param("i", $data[0]['receiver_id']);
                        $select_badge_stmt->execute();
                        $select_badge_stmt->store_result();
                        $notification_counter = $select_badge_stmt->num_rows;

                        $select_message_counter = "SELECT DISTINCT conversion_id FROM ".TABLE_CHAT_MESSAGE." WHERE receiver_id = ? AND is_read = 0 AND is_delete='".$is_delete."' AND is_testdata='".$data[0]['is_testdata']."'";
                        $select_message_stmt = $connection->prepare($select_message_counter);
                        $select_message_stmt->bind_param("i", $data[0]['receiver_id']);
                        $select_message_stmt->execute();
                        $select_message_stmt->store_result();
                        $message_counter = $select_message_stmt->num_rows;

                        $notification_count = (int)$notification_counter + (int)$message_counter;
                        // Create the payload body
                        $body['aps'] = array(
                            'alert' => $message,
                            'badge' => $notification_count,
                            'sound' => 'default',
                            'pushType' => $pushType
                        );
                        $body['payload'] = $data[0];
                        
                        // Encode the payload as JSON
                        $payload = json_encode($body);
                        $payload = str_replace('\\\\', urldecode('%5C'), $payload);
                        
                        // Build the binary notification  
                        $msg = chr(0) . pack('n', 32) . pack('H*', $get_device_token) . pack('n', strlen($payload)) . $payload;
                        $resultIOS = fwrite($fp, $msg, strlen($msg));
                        if (!$resultIOS) {
                            //echo 'Message not delivered' . PHP_EOL;
                        } else {
                            //echo 'Message successfully delivered' . PHP_EOL;
                        }
                        // Close the connection to the server
                        fclose($fp);
                        
                    }
                }
            } else{
                return false;
            }
        }
    }     
}

function callAPIASync($api, $content)
{
    $protocol = "tcp";
    $host = "52.2.73.94";//gethostbyname(gethostname()); //development
    $port = 80;
    $path = "/WS/MobileAppService.php?Service=".$api;

    $timeout = 10;
    try {
        # Open our socket to the API Server.
        $socket = fsockopen($protocol . "://" . $host, $port,
            $errno, $errstr, $timeout);

        if($socket)
        {
            if ($errno != 0) {
                // echo "in error ".$errno . $errstr;
                error_log("Error $errno : $errstr");
            }
            //print_r(socket_get_status($socket));
            # Create the request body, and make the request.
            $req = "";
            $req .= "POST " . $path . " HTTP/1.1\r\n";
            $req .= "Host: " . $host . "\r\n";
            $req .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $req .= "Content-length: " . strlen($content) . "\r\n";
            $req .= "\r\n";
            $req .= $content;
            //echo "\n request=. ".$req;
            fwrite($socket, $req);
            fread($socket, 1);
            fclose($socket);
        }
        else{
        }

    } catch (Exception $e) {
        error_log("Error {$e->getCode()} : {$e->getMessage()}");
    }
}

function uploadMultipartImage($media_array_name,$upload_media_folder,$file_size){
    $data=array();
    $status=FAILED;

       /* if (is_dir($upload_media_folder) == false) {
            mkdir($upload_media_folder, 0777);        // Create directory if it does not exist
        }
        else {
            chmod($upload_media_folder, 0777);
        }*/
    //        print_r($_FILES[$media_array_name]);

        if ($_FILES[$media_array_name]['name'] != '')
        {
    //            if ($_FILES[$media_array_name]['size'] < $file_size)
    //            {
                if ($_FILES[$media_array_name]['error'] > 0) {
                    $message = "File name error ".$_FILES[$media_array_name]['error'];
                    $posts['media_name'] = null;
                }
                else {
                    $uploadDir="";
                    $ext = pathinfo($_FILES[$media_array_name]['name'], PATHINFO_EXTENSION);
                    $file = $milliseconds = round(microtime(true) * 1000) . generateRandomString(7) ."." . $ext;
                    $mime = $_FILES[$media_array_name]['type'];
                    /*if (strstr($mime, "image/")) {
                        $uploadDir =  $upload_media_folder;
                    }*/
                    $uploadDir =  $upload_media_folder;
                    $uploadFile = $uploadDir . $file;

                    if (move_uploaded_file($_FILES[$media_array_name]['tmp_name'], $uploadFile))
                    {
                        $status=SUCCESS;
                        $message=$file;
                    }
                    else {
                        $message = "Failed to upload image on server.";
                    }
                }
    //            } else {
    //                $message = "Media size exceeds than $file_size.";
    //            }
        } else {
            $message = "Invalid file";
        }

    $data['status']=$status;
    $data['message']=$message;
    return $data;
}

function callTwilioAPI($data){
    error_reporting(0);
    $response[STATUS]=FAILED;
    $twilio_account_id = TWILIO_ACCOUNT_SID;
    $token = TWILIO_AUTH_TOKEN;
    $url = "https://api.twilio.com/2010-04-01/Accounts/$twilio_account_id/SMS/Messages";
    $post = http_build_query($data);
    $x = curl_init($url);
    curl_setopt($x, CURLOPT_POST, true);
    curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false); //This line is needed otherwise
    curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($x, CURLOPT_USERPWD, "$twilio_account_id:$token");
    curl_setopt($x, CURLOPT_POSTFIELDS, $post);
    $y = curl_exec($x);
    curl_close($x);
    $xml = simplexml_load_string($y);
    $json = json_encode($xml);
    $array_json = json_decode($json, TRUE);
       
    if ($y != false) {
        if ($array_json['SMSMessage']['Status'] == "queued") {
            $response[STATUS]=SUCCESS;
            return $response;
        }
        else {
            $error['Status'] = $array_json['RestException']['Status'];
            $error['Message'] = $array_json['RestException']['Message'];
            $error['Code'] = $array_json['RestException']['Code'];
            $response[MESSAGE] = "Status " . $error['Status'] . ", Code " . $error['Code'].", Message ".$error['Message'];

            return $response;
        }
    }
}

?>