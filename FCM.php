<?php

class FCM
{
    public function sendPushiOS($deviceToken,$Notifsubject,$pushMessage,$isReject)
    {

        $development=false;
        $message=$pushMessage;
        $badge=0;
        $sound='default';
        $passphrase = 'password';

        $alert_message=$message['push_message'];

        $payload = array();
        if($isReject == 0)
        {
            $payload['aps'] = array('alert' => $alert_message, 'badge' => intval($badge), 'sound' => $sound);
        }
        else
        {
            $payload['aps'] = array('alert' => "", 'badge' => intval($badge), 'sound' => "",'content-available' => 1 );
            //print_r($payload['aps']);
            //exit;
        }


        $payload['custom'] = $message;
        $payload = json_encode($payload);

        $apns_url = NULL;
        $apns_cert = NULL;
        $apns_port = 2195;

        if($development)
        {
            $apns_url = 'gateway.sandbox.push.apple.com';
            $apns_cert = 'ck_dev.pem';
        }
        else
        {
            $apns_url = 'gateway.push.apple.com';
            $apns_cert = 'ck_prod.pem';
        }

        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $apns_cert);
        stream_context_set_option($stream_context, 'ssl', 'passphrase', $passphrase);

        $apns = stream_socket_client('ssl://' . $apns_url . ':' . $apns_port, $error, $error_string, 300, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $stream_context);
        $status=2;
        if($error) {
            //print("\nAPN: Maybe some errors: $error: $error_string");
            $status=2;
        }

        if (!$apns) {

            if ($error) {
                //print("\nAPN Failed". 'ssl://' . $apns_url . ':' . $apns_port. " to connect: $error $error_string");
                $status=2;
            }
            else {
                //print("\nAPN Failed to connect: Something wrong with context");
                $status=2;
            }
        }
        else {
            // print("\nAPN: Opening connection to: {ssl://" . $apns_url . ":" . $apns_port. "}");

            foreach($deviceToken as $device_token)
            {
                //print_r($device_token);
                $apns_message = chr(1)
                    . pack("N", time())
                    . pack("N", time() + 30000)
                    . pack('n', 32)
                    . pack('H*', str_replace(' ', '', $device_token))
                    . pack('n', strlen($payload))
                    . $payload;
                $result = fwrite($apns, $apns_message, strlen($apns_message));

                if($result) {
                    //echo "sent";
                    $status=1;
                }
                else {
                    // echo "not sent";
                    $status=2;
                }
            }
            fclose($apns);
        }
        return $status;
    }

    /*function send_gcm_notify(mysqli $connection,$isNotificationType,$payload,$user_id,$notification_type)
    {
        //define("FIREBASE_API_KEY", FCM_KEY);
        //define("FIREBASE_FCM_URL", "https://fcm.googleapis.com/fcm/send");

        $is_delete = DELETE_STATUS::NOT_DELETE;
        $queryUser = "SELECT device_token,device_type FROM " . TABLE_APP_TOKENS . "  WHERE user_id = ? AND device_token IS NOT NULL AND is_delete ='".$is_delete."'";

        if ($user_push_stmt = $connection->prepare($queryUser)) {
            $user_push_stmt->bind_param("i", $user_id);
            $user_push_stmt->execute();
            $user_push_stmt->store_result();

            if ($user_push_stmt->num_rows > 0) {

                $user_obj = fetch_assoc_stmt($user_push_stmt);

                $get_device_type = $user_obj[0]['device_type'];
                $get_device_token = $user_obj[0]['device_token'];

                if ($get_device_type == 2) {

                    $firstName = '';
                    $lastName = '';
                    $fullName = '';
                    $select_user_query = "SELECT first_name, last_name  FROM ".TABLE_USER." WHERE user_id = ? AND is_delete='".$is_delete."'";

                    if ($select_user_stmt = $connection->prepare($select_user_query)) {
                        $select_user_stmt->bind_param("i", $payload['sender_id']);
                        $select_user_stmt->execute();
                        $select_user_stmt->store_result();

                        if ($select_user_stmt->num_rows > 0) {

                            $user_post = fetch_assoc_stmt($select_user_stmt);

                            $payload['sender_first_name'] = $user_post[0]['first_name'];
                            $payload['sender_last_name'] = $user_post[0]['last_name'];

                            $fullName = $payload['sender_first_name'].' '.$payload['sender_last_name'];

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
                            $payload['message'] = $message;
                            $fields=array();


                            $select_badge_counter = "SELECT is_read  FROM ".TABLE_NOTIFICATION." WHERE received_by = ? AND is_read = 0 AND is_delete='".$is_delete."' AND is_testdata='".$payload['is_testdata']."'";
                            $select_badge_stmt = $connection->prepare($select_badge_counter);
                            $select_badge_stmt->bind_param("i", $payload['receiver_id']);
                            $select_badge_stmt->execute();
                            $select_badge_stmt->store_result();
                            $notification_counter = $select_badge_stmt->num_rows;

                            $select_message_counter = "SELECT is_read  FROM ".TABLE_CHAT_MESSAGE." WHERE receiver_id = ? AND is_read = 0 AND is_delete='".$is_delete."' AND is_testdata='".$payload['is_testdata']."'";
                            $select_message_stmt = $connection->prepare($select_message_counter);
                            $select_message_stmt->bind_param("i", $payload['receiver_id']);
                            $select_message_stmt->execute();
                            $select_message_stmt->store_result();
                            $message_counter = $select_message_stmt->num_rows;

                            $payload['badge'] = (int)$notification_counter + (int)$message_counter;


                            //$isNotificationType is Boolean value
                            if($isNotificationType){

                                if(is_array($get_device_token)){
                                    $fields['registration_ids'] = $get_device_token;

                                }else{
                                    $fields['to'] = $get_device_token;
                                }

                                $fields['priority'] =  "high";
                                $fields['notification'] =  $payload;

                            }
                            else{

                                if(is_array($get_device_token)){
                                    $fields['registration_ids'] = $get_device_token;

                                }else{
                                    $fields['to'] = $get_device_token;
                                }

                                $fields['priority'] =  "high";
                                $fields['data'] =  $payload;
                            }

                            $headers = array(
                                'Authorization: key=' . FCM_KEY,
                                'Content-Type: application/json'
                            );

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, FCM_URL);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

                            $result = curl_exec($ch);
                            if ($result === FALSE) {
                                die('Problem occurred: ' . curl_error($ch));
                            }
                            curl_close($ch);
                        }
                    }
                }else{
                    return false;
                }
            }
        }
    }*/

    function send_gcm_notify($reg_id, $isNotificationType,$payload,$extra)
    {
        // AIzaSyDqnJ3VF05wUFmeQa7JxU3lQ8Y_uu0u8C0
        define("FIREBASE_API_KEY", "AAAASDT6QuY:APA91bH1K_K2XzKGVwMjjO3hjMpxYsvPIFtFqDmjUPaz4cfH6dZ5JItwWVyeDmySmp4rVA2VNvWjfsGOcywFOx-LsrS5iGU90dOTGpdrHnddNgz-TtSzpuEq6JUwb9Mf15TMl43SbyrO");
        define("FIREBASE_FCM_URL", "https://fcm.googleapis.com/fcm/send");

        $fields=array();
//        echo $extra;
        //$isNotificationType is Boolean value
        if($isNotificationType){

            if(is_array($reg_id)){
                $fields['registration_ids'] = $reg_id;

            }else{
                $fields['to'] = $reg_id;
            }

            $fields['priority'] =  "high";

            $fields['notification'] =  $payload;

        }
        else{

            if(is_array($reg_id)){
                $fields['registration_ids'] = $reg_id;

            }else{
                $fields['to'] = $reg_id;
                
            }

            $fields['priority'] =  "high";
            $fields['notification'] =  $extra;
            $fields['data'] =  $payload;
        }


        //echo json_encode($fields);
        $headers = array(
            'Authorization: key=' . FIREBASE_API_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FIREBASE_FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Problem occurred: ' . curl_error($ch));
        }

        curl_close($ch);
        //$result;
        //echo $result;
    }
}
?>