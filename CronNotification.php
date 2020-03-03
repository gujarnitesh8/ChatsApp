<?php

include_once 'config.php';
include_once 'HelperFunctions.php';
include_once 'Paths.php';
include_once 'TableVars.php';
include_once 'ConstantValues.php';
include_once 'CommonFunctions.php';

    $connection=$GLOBALS['con'];
    /**
     *@uses function to send push notification to all user
     **/
    send_push_notification_to_all($connection);

    function send_push_notification_to_all($connection){

        $result = array();
        $per_page = 15000;
        $notification = get_notification_message($connection);
        $media_type = $notification['media_type_id'];
        $notificationMsg = array("message" => $notification['message'],"media_type_id" => $notification['media_type_id'], "title" => $notification['title'], 'created_date' => $notification['created_date']);

        if(!empty($notification)){
            if($notification['last_user_id'] >= $notification['max_user_id'])
            {
                //update notification status to 1
                mark_notification($connection,1,$notification['id']);
                echo "All notification sent for notification_ex_media_id : ".$notification['id'];
                exit;
            }
            else{
                $notificationId = $notification['notification_time_id'];
                // get user from user table to send notification that maches notification_time_id
                $users=getUsersToken($connection,$notificationId,$per_page, $notification['last_user_id']);
                if(sizeof($users)){
                    /*HSU CODE snippet Start send push notification */
                    $uids = array();
                    $android_device_token = array();
//                    echo "\n count=>".count($users);

                    for($i=0;$i<count($users);$i++)
                    {
                       $last_user_id = $users[$i]['id'];

                        array_push($uids,$users[$i]['id']);
                        $nId = explode(',', $users[$i]['notification_time_id']);
                        //$uids = implode(',', $users[$i]['id'])

                        foreach ($nId as $nid) {
                            if($nid == $notificationId)
                            {
                                $deviceToken = $users[$i]['device_token'];
                                $deviceType = $users[$i]['device_type'];
                                $getToken=getToken($connection,$users[$i]['id']);
                                foreach($getToken as $token)
                                {
                                    $token['device_token'];
                                    if($token['device_token'] != NULL && $token['device_token'] != '0')
                                    {
                                        if($deviceType == '1' && strlen($token['device_token']) == 64)
                                        {

                                            if($media_type != 5){
                                                //echo "<br>IOS".$token['device_token'];
                                                $returnMsg = push_notification_ios($token['device_token'], $notificationMsg);
                                            }
                                        }
                                        if($deviceType == '2'){
                                            $android_device_token[] = $token['device_token'];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if($media_type != 5){
                       //echo "<br>Android<pre>";print_r($android_device_token);
                        $result = send_android_notification($android_device_token, $notificationMsg);
                    }
                    $ex_media_id = $notification['id'];
                    // Start - HSU -code
                    if(isset($notificationMsg['message'])){
                        $message = $notificationMsg['message'];
                    }else{
                        $message = "";
                    }
                    $insertNotiFields = " sender_id ,media_type, receiver_ids , feed_id,message,created_date ";
                    $insert_noti_query = "INSERT INTO " . TABLE_NOTIFICATIONS . " (" . $insertNotiFields . ") values(?,?,?,?,?,?)";
                    if ($insertNotiStmt = $connection->prepare($insert_noti_query)) {
                        $media_type="Exclusive_Media";
                        $user_ids=implode(",", $uids);
                        $created_date=date("Y-m-d H:i:s");
                        $insertNotiStmt->bind_param('ssssss',$notification['sender_id'],$media_type,$user_ids,$last_user_id,$message,$created_date );
                        if ($insertNotiStmt->execute()) {
                            $insertNotiStmt->close();
                        }
                        else{
//                            echo $insertNotiStmt->error;
                        }
                    }
                    else{
                        $connection->error;
                    }

                    // END-
                    update_notification($connection,$ex_media_id,$last_user_id);
                    /* send push notification */
                }else{
                    echo "No user found for notification_ex_media_id:".$notification['id'];
                }
                print_r($result);
                exit;
            }
        }else{
            echo "No notification remain to send.";exit;
        }
    }


    /**
     *@uses send push notification to ios device
     * @param $regID varchar device token
     * @param $message varchar pushnotification message
     **/
    function push_notification_ios($regId, $message)
    {
        $development=true; // true for devlopment mode and false for production

        $dev_pem_path ="/var/www/html/pem_file/ReachOut_dev_push.pem";
        $pro_pem_path = "/var/www/html/pem_file/ReachOut_Push.pem";

        // Put your private key's passphrase here:
        $passphrase = 'password';
        $ctx = stream_context_create();

        $apns_url = NULL;
        $apns_cert = NULL;
        $apns_port = 2195;

        if($development)
        {
            $apns_url = 'gateway.sandbox.push.apple.com';
            $apns_cert = $dev_pem_path;
        }
        else
        {
            $apns_url = 'gateway.push.apple.com';
            $apns_cert = $pro_pem_path;
        }

        // stream_context_set_option($ctx, 'ssl', 'local_cert', $dev_pem_path);
        stream_context_set_option($ctx, 'ssl', 'local_cert', $apns_cert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        // Open a connection to the APNS server
        /* $fp = stream_socket_client(
                 'ssl://gateway.push.apple.com:2195', $err,
                 $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);*/
        $fp = stream_socket_client('ssl://' . $apns_url . ':' . $apns_port, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        /*  $fp = stream_socket_client(
                   'ssl://gateway.sandbox.push.apple.com:2195', $err,
                   $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
          */
        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);
        // Create the payload body
        $body['aps'] = array(
            'alert' => $message['message'],
            'title' => $message['title'],
            'media_type_id' => $message['media_type_id'],
            'created_date' => $message['created_date'],
            'sound' => 'default'
        );
        // Encode the payload as JSON
        $payload = json_encode($body);
        // Build the binary notification
        $msg = chr(0)
            .pack('n', 32)
            .pack('H*', str_replace(' ', '', $regId))
            .pack('n', strlen($payload)).$payload;
        //  echo $msg;
        $result = fwrite($fp, $msg, strlen($msg));
        fclose($fp);
        return $result;
    }

    /**
     * use to send notification to android users
     * @param $registration_ids array of device_token
     * @param $message array of title and message
     */
    function send_android_notification($registration_ids, $message)
    {
        //$message = array("message" => "this is testing notification");
        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $message
        );
        $headers = array(
            'Authorization: key=AAAAWTC_XE4:APA91bFgQkh0wHgPMT5CL5_Jk7RrBWXfclz8evnWn7fZtbIw4E_2WY7Vm7b_gW7JycC5CZZSaH1ANFbTBh8eLZXIMEjZn326kFn7wWdJiF0fA7ONEseamf5uv6rtr0iKPGHFIkerLVoX', // FIREBASE_API_KEY_FOR_ANDROID_NOTIFICATION
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Disabling SSL Certificate support temporarely
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        // Execute post
        $result = curl_exec($ch);
        if($result === false)
        {
            die('Curl failed:' .curl_errno($ch));
        }
        // Close connection
        curl_close($ch);
        return $result;
    }

    function get_notification_message($connection){
        $get_user=[];
        $select_query = "SELECT * FROM " . TABLE_NOTIFICATION_EX_MEDIA . " WHERE send_status='0'  AND is_delete='" . DELETE_STATUS::NOT_DELETE . "' ORDER BY id asc LIMIT 1";
        $select_query = "SELECT * FROM " . TABLE_NOTIFICATION_EX_MEDIA . " WHERE id= '9'  AND is_delete='" . DELETE_STATUS::NOT_DELETE . "' ORDER BY id asc LIMIT 1";
        if ($select_stmt = $connection->prepare($select_query)) {
            $select_stmt->execute();
            $select_stmt->store_result();
            if ($select_stmt->num_rows > 0) {
                $get_user=fetch_assoc_all_values($select_stmt);
                return $get_user;
            }
        }
        return $get_user;
    }

function getUsersToken($connection,$notificationId,$per_page, $start)
{
    $user = array();
    $select_query = "SELECT id,device_token,notification_time_id,device_type FROM " . TABLE_USER . " WHERE  (notification_time_id LIKE '%".$notificationId."%') AND is_delete='" . DELETE_STATUS::NOT_DELETE . "' LIMIT " . $per_page . "," . $start;
//    $select_query = "SELECT id,device_token,notification_time_id,device_type FROM " . TABLE_USER . " WHERE id IN(10,73) AND (notification_time_id LIKE '%".$notificationId."%') AND is_delete='" . DELETE_STATUS::NOT_DELETE . "'";
    if ($select_stmt = $connection->prepare($select_query)) {
        $select_stmt->execute();
        $select_stmt->store_result();
        if ($select_stmt->num_rows > 0) {
            $user = fetch_assoc_stmt($select_stmt);
        }
    }
    return $user;
}

    function mark_notification($connection,$status,$notification_ex_media_id){
        $today = date("Y-m-d H:i:s");
        $update_ex_media_query = "UPDATE " . TABLE_NOTIFICATION_EX_MEDIA . " SET send_status= ?,modified_date=? WHERE id =?";
        if ($updateExMediaStmt = $connection->prepare($update_ex_media_query)) {
            $updateExMediaStmt->bind_param('sss',$status,$today,$notification_ex_media_id);
            if ($updateExMediaStmt->execute()) {
                $updateExMediaStmt->close();
            }
        }
    }

function update_notification($connection,$ex_media_id, $last_user_id){
    $today = date("Y-m-d H:i:s");
    $update_ex_media_query = "UPDATE " . TABLE_NOTIFICATION_EX_MEDIA . " SET last_user_id= ?,modified_date=? WHERE id =?";
    if ($updateExMediaStmt = $connection->prepare($update_ex_media_query)) {
        $updateExMediaStmt->bind_param('sss',$last_user_id,$today,$ex_media_id);
        if ($updateExMediaStmt->execute()) {
            $updateExMediaStmt->close();
        }
    }
}

function getToken($connection,$user_id)
{
    $user = array();
    $select_query = "SELECT device_token FROM " . TABLE_APP_TOKENS. " WHERE user_id =? AND is_delete='" . DELETE_STATUS::NOT_DELETE . "'";
    if ($select_stmt = $connection->prepare($select_query)) {
        $select_stmt->bind_param("s",$user_id);
        $select_stmt->execute();
        $select_stmt->store_result();
        if ($select_stmt->num_rows > 0) {
            $user = fetch_assoc_stmt($select_stmt);
        }
    }
    return $user;
}
?>
