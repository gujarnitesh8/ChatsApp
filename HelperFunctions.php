<?php

// print array with format
function pr($arr = null, $exit = 1, $append_text = null)
{
    if ($arr != null) {
        echo "<pre>";
        if ($arr != null)
            echo $append_text;

        print_r($arr);

        if ($exit == 1)
            exit;
    }
}

function validateValue($value, $placeHolder)
{
    $value = strlen($value) > 0 ? $value : $placeHolder;
    return $value;
}

function validateBoolValue($value, $key, $placeHolder)
{
    $value = strlen($value->$key) > 0 ? $value->key : $placeHolder;
    return $value;
}

function validateObject($object, $key, $placeHolder)
{

    if (isset($object->$key)) {
        return $object->$key;
    } else {
        return $placeHolder;
    }
}

function validatePostValue($key, $placeHolder)
{
    $value = isset($_POST[$key]) ? $_POST[$key] : $placeHolder;
    return $value;
}

function json_validate($string)
{
    if (is_string($string)) {
        @json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}

function getDefaultDate()
{
    return date("Y-m-d H:i:s");
}

function getPreviousDate($plusminus, $days)
{
    return date('Y-m-d H:i:s', strtotime('' . $plusminus . '' . $days . ' days'));
}

function generatePassword($password)
{
    $cost = 10;

    $saltPassword = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
    $saltPassword = sprintf("$2a$%02d$", $cost) . $saltPassword;

    $finalHashPassword = crypt($password, $saltPassword);

    return $finalHashPassword;
}

function matchPassword($userPassword, $dbPassword)
{
    if (crypt($userPassword, $dbPassword) == $dbPassword)
        return 1;
    else
        return 0;
}

function matchStringValue($str1, $str2)
{
    if (strcmp($str1, $str2))
        return 1;
    else
        return 0;
}

function encryptPassword($str)
{
    //    $qEncoded      = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( ENCRYPTION_KEY ), $str, MCRYPT_MODE_CBC, md5( md5( ENCRYPTION_KEY ) ) ) );

    $qEncoded = md5($str);

    return ($qEncoded);
}

function decryptPassword($str)
{
    $qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(ENCRYPTION_KEY), base64_decode($str), MCRYPT_MODE_CBC, md5(md5(ENCRYPTION_KEY))), "\0");
    return ($qDecoded);
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . date("YmdHis");
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function generateRandomCode($length)
{
    // $randomString = "";
    // $numbers = range('1', '9');
    // $final_array = array_merge($numbers);
    // while ($length--) {
    //     $key = array_rand($final_array);
    //     $randomString .= $final_array[$key];
    // }
    return $num_str = sprintf("%06d", mt_rand(1, 999999));
}

function copy_value($v)
{
    return $v;
}

function fetch_assoc_stmt(\mysqli_stmt $stmt, $buffer = true)
{
    if ($buffer) {
        $stmt->store_result();
    }
    $fields = $stmt->result_metadata()->fetch_fields();
    $args = array();
    foreach ($fields AS $field) {
        $key = str_replace(' ', '_', $field->name); // space may be valid SQL, but not PHP
        $args[$key] = &$field->name; // this way the array key is also preserved
    }
    call_user_func_array(array($stmt, "bind_result"), $args);

    $results = array();
    while ($stmt->fetch()) {
        //$results[] = array_map(array($this, "copy_value"), $args);
        $results[] = array_map("copy_value", $args);
    }
    if ($buffer) {
        $stmt->free_result();
    }

    return $results;
}

function fetch_assoc_all_values($stmt)
{
    if ($stmt->num_rows > 0) {
        $result = array();
        $md = $stmt->result_metadata();
        $params = array();
        while ($field = $md->fetch_field()) {
            $params[] = &$result[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $params);
        if ($stmt->fetch())
            return $result;
    }
    return null;
}

function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

//sort multi dimension array in descending order
function cmp($a, $b)
{
    //echo $a['following_status'].' '.$b['following_status'].'<br>';
    if ($a['following_status'] == $b['following_status']) {
        return 0;
    }
    return ($a['following_status'] > $b['following_status']) ? -1 : 1;
}

function errorLogFunction($error_message)
{
    $log_file = date("F_j_Y") . '_log.txt';
    $file = 'error_log_' . date("Y_m_d") . '.txt';
    $current = @file_get_contents($file);
    $current = "\n----------------------------\n";
    //$current .= basename(__FILE__) .'/LogFile/'. "\n----------------------------\n";
    $current .= '/LogFile/' . "\n----------------------------\n";
    $current .= "Date := " . date("Y-m-d H:i:s") . "\n----------------------------\n";
    $current .= $error_message;
    $current .= (microtime(true)) - time() . " seconds elapsed\n\n";
    // Write the contents back to the file

    file_put_contents(dirname(__FILE__) . './LogFile/' . $file, $current, FILE_APPEND);
}

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        return round(($miles * 1.609344), 2);
    } else if ($unit == "N") {
        return round(($miles * 0.8684), 2);
    } else {
        return round($miles, 2);
    }
}

//echo distance(32.9697, -96.80322, 29.46786, -98.53506, "M") . " Miles<br>";
//echo distance(32.9697, -96.80322, 29.46786, -98.53506, "K") . " Kilometers<br>";
//echo distance(32.9697, -96.80322, 29.46786, -98.53506, "N") . " Nautical Miles<br>";


/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/
/*::                                                                         :*/
/*::  This routine calculates the distance between two points (given the     :*/
/*::  latitude/longitude of those points). It is being used to calculate     :*/
/*::  the distance between two locations using GeoDataSource(TM) Products    :*/
/*::                                                                         :*/
/*::  Definitions:                                                           :*/
/*::    South latitudes are negative, east longitudes are positive           :*/
/*::                                                                         :*/
/*::  Passed to function:                                                    :*/
/*::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  :*/
/*::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  :*/
/*::    unit = the unit you desire for results                               :*/
/*::           where: 'M' is statute miles                                   :*/
/*::                  'K' is kilometers (default)                            :*/
/*::                  'N' is nautical miles                                  :*/
/*::                                                                         :*/
/*::	distance(32.96, -96.80, 29.46, -98.53, "M") . " Miles<br>";			 :*/
/*::	distance(32.96, -96.80, 29.46, -98.53, "K") . " Kilometers<br>";	 :*/
/*::	distance(32.96, -96.80, 29.46, -98.53, "N") . " Nautical Miles<br>"; :*/
/*::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::*/

function distancefromlocation($lat1, $lon1, $lat2, $lon2, $unit)
{
    if ($lat1 != '' && $lon1 != '' && $lat2 != '' && $lon2 != '' && $unit != '') {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    } else {

        return false;
    }
}

function fetch($stmt)
{
    $array = array();

    if ($stmt instanceof mysqli_stmt) {
        $variables = array();
        $data = array();

        /* get resultset for metadata */
        $meta = $stmt->result_metadata();

        while ($field = $meta->fetch_field())
            $variables[] = &$data[$field->name]; // pass by reference

        call_user_func_array(array($stmt, 'bind_result'), $variables);

        $i = 0;
        while ($stmt->fetch()) {
            $array[$i] = array();
            foreach ($data as $k => $v)
                $array[$i][$k] = $v;
            $i++;
            // don't know why, but when I tried $array[] = $data, I got the same one result in all rows
        }
    } else if ($stmt instanceof mysqli_result) {
        while ($row = $stmt->fetch_assoc())
            $array[] = $row;
    }
    return $array;
}

/*
 *
 * if ($data_query_stmt = $connection->prepare($dataQuery)) {
            $data_query_stmt->execute();
            $data_query_stmt->store_result();
            if ($data_query_stmt->num_rows > 0) {

//                while($$val=fetch_assoc_all_values($data_query_stmt))
//                {
//                  $ar=array();
//                    $a['user-id']=$ar['id'];
//
//                }
                $rows = fetch($data_query_stmt);
                $data_query_stmt->close();
 * */

function createThumbnailImage($originalImage, $imagename)
{
    include_once 'resize-class.php';
    list($w, $h) = getimagesize($originalImage);
    $sourceRatio = $w / $h;
    $imagePathS = SERVER_THUMB_IMAGE . $imagename;
    $resizeObj = new resize($originalImage);
    $resizeObj->resizeImage(110, 110, 'exact');
    $resizeObj->saveImage($imagePathS, 100);
}

function uploadSingleImageWithMultipart($upload_key, $file_name, $folder_path)
{
    //  $uploadDirImg = $_SERVER['DOCUMENT_ROOT'] .
    $uploadDir = "." . $folder_path;
    $uploadFile = $uploadDir . $file_name;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    } else {
        chmod($uploadDir, 0777);
    }
    if ($upload_key['name'] != '') {
        if ($upload_key['error'] > 0) {
            $status = FAILED;
            $message = "Invalid file";
        } else {
            if (($upload_key['error'] < 24000000)) {
                $status = FAILED;
                $message = "Media size exceeds than 24 MB.";
            } else {
                if ($upload_key["error"] > 0) {
                    $message = $upload_key["error"];
                    $status = FAILED;
                } else {
                    if (move_uploaded_file($upload_key['tmp_name'], $uploadFile)) {
                        $status = SUCCESS;
                        $message = "Image uploaded successfully.";
                    } else {
                        $status = FAILED;
                        $message = "Failed to upload Image";
                    }
                }
            }
        }
    }
    $data['status'] = $status;
    $data['message'] = $message;
    return $data;
}

function getDatesFromRange($start, $end, $format = 'Y-m-d')
{
    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach ($period as $key => $date) {
        $array[$key]['date'] = $date->format($format);
        $array[$key]['day'] = date('D', strtotime($date->format($format)));
        //$array[$key] = $date->format($format);
    }

    return $array;
}

function fileUploadCodeToMessage($code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            break;
        case UPLOAD_ERR_PARTIAL:
            $message = "The uploaded file was only partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            $message = "No file was uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $message = "Missing a temporary folder";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $message = "Failed to write file to disk";
            break;
        case UPLOAD_ERR_EXTENSION:
            $message = "File upload stopped by extension";
            break;

        default:
            $message = "Unknown upload error";
            break;
    }
    return $message;
}

function fwrite_stream($fp, $string)
{
    for ($written = 0; $written < strlen($string); $written += $fwrite) {
        $fwrite = fwrite($fp, substr($string, $written));
        if ($fwrite === false) {
            return $written;
        }
    }
    return $written;
}

function createImageThumbnail($uploadFile, $fileName, $uploadDirImg)
{


    $width = 250;
    $height = 250;

    // Get new dimensions
    list($width_orig, $height_orig) = getimagesize($uploadFile);

    $ratio_orig = $width_orig / $height_orig;

    if ($width / $height > $ratio_orig) {
        $width = $height * $ratio_orig;
    } else {
        $height = $width / $ratio_orig;
    }


    if (!is_dir($uploadDirImg)) {
        mkdir($uploadDirImg, 0777, true);
    }


    $thumbImgName = getThumbNameFromFileName($fileName);


    //$ffmpeg = 'C:/FFMPEG/bin/ffmpeg'; //Europa Server

    $ffmpeg = "C:/ffmpeg-git-61b1d85-win32-static/ffmpeg-git-61b1d85-win32-static/bin/ffmpeg";
    $thumbFile = SERVER_THUMB_IMAGE . $thumbImgName;

    $cmd = "$ffmpeg -i $uploadFile -vf scale=w=$width:h=$height -crf 1 $thumbFile";
    shell_exec($cmd);
    return $thumbImgName;
}

function createVideoThumbnail($uploadFile, $fileName, $uploadDirImg)
{

    if (!is_dir($uploadDirImg)) {
        mkdir($uploadDirImg, 0777, true);
    }

    //$ffmpeg = 'C:/FFMPEG/bin/ffmpeg';
    $ffmpeg = 'C:/ffmpeg-git-61b1d85-win32-static/ffmpeg-git-61b1d85-win32-static/bin/ffmpeg';
    $thumbImgName = getThumbNameFromFileName($fileName);
    $thumbFile = SERVER_THUMB_IMAGE . getThumbNameFromFileName($thumbImgName);
    //$size = "250x250";
    $size = "w=iw:h=ih";
    //$size = "200:100";
    //$cmd = "$ffmpeg -i $uploadFile -y -vcodec mjpeg -vframes 1 -an -f rawvideo -s $size $thumbFile";

    $cmd = "$ffmpeg -i $uploadFile -y -vcodec mjpeg -vframes 1 -filter:v scale=$size $thumbFile";

    //$cmd = "$ffmpeg -ss 100 -i $uploadFile -frames:v 1 -q:v 2 -vf 'scale=200:100:force_original_aspect_ratio=increase,crop=".$size."' $thumbFile";

    shell_exec($cmd);

    return $thumbImgName;
}

function getThumbNameFromFileName($fileName)
{
    $nameFile = explode(".", $fileName);
    return $nameFile[0] . ".jpg";
}

function addEntryInNotificationTable(mysqli $connection, $arrayNotificationValues)
{

    $currentDate = getDefaultDate();
    $insertNotification = "INSERT INTO " . TABLE_NOTIFICATION . " (sender_id,receiver_id,notification_type_id,notification_type,created_date,is_testdata) VALUES(?,?,?,?,?,?)";
    $insertStmtNotification = $connection->prepare($insertNotification);

    // echo '<pre>';
    // print_r($arrayNotificationValues);
    // echo '</pre>';
    // die;
    foreach ($arrayNotificationValues as $notificationObj) {

        $senderId = $notificationObj['sender_id'];
        $receiverId = $notificationObj['receiver_id'];
        $notificationTypeId = $notificationObj['notification_type_id'];
        $notificationType = $notificationObj['notification_type'];
        $is_testdata = $notificationObj['is_testdata'];
        $insertStmtNotification->bind_param('iiiisi', $senderId, $receiverId, $notificationTypeId, $notificationType,
            $currentDate, $is_testdata);

//        echo $senderId . " " . $receiverId . " " . $notificationTypeId . " " . $notificationType,
//            $currentDate . " " . $is_testdata;
        if ($insertStmtNotification->execute()) {

            // echo $insertStmtNotification->error;
        } else {
            // echo $insertStmtNotification->error;

            break;
        }

    }
}

function removeEntryInNotificationTable(mysqli $connection, $arrayNotificationValues, $notifyType = '')
{

    $currentDate = getDefaultDate();

//    if ($notifyType != '') {
//        $notification_type_txt = "(notification_type = 1 OR notification_type = 2)";
//    } else {
//        $notification_type_txt = "notification_type = ?";
//    }

    $notification_type_txt = "notification_type = ?";

    $updateNotification = "UPDATE " . TABLE_NOTIFICATION . " SET is_delete = 1 WHERE sender_id = ? AND notification_type_id = ? AND " . $notification_type_txt . " AND is_testdata = ? AND is_delete = 0";

    $updateStmtNotification = $connection->prepare($updateNotification);
    // echo '<pre>';
    // print_r($arrayNotificationValues);
    // echo '</pre>';


    foreach ($arrayNotificationValues as $notificationObj) {

        // print_r($notificationObj);


        $senderId = $notificationObj['sender_id'];
        //$receiverId = $notificationObj['receiver_id'];
        $notificationTypeId = $notificationObj['notification_type_id'];
        $notificationType = $notificationObj['notification_type'];
        $is_testdata = $notificationObj['is_testdata'];

      //  echo $senderId." ".$notificationTypeId." ". " ".$notificationType." ". $is_testdata;
        $updateStmtNotification->bind_param('iiii', $senderId, $notificationTypeId, $notificationType, $is_testdata);

        if ($updateStmtNotification->execute()) {

            // echo $updateStmtNotification->error;
        } else {
            //echo $updateStmtNotification->error;

            break;
        }

    }
}

function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

function extract_hashtags($string)
{
    $keywords = array();
    /* Match hashtags */
    preg_match_all('/#(\w+)/', $string, $matches);

    /* Add all matches to array */
    foreach ($matches[1] as $match) {
        $keywords[] = $match;
    }

    return (array)$keywords;
}

function notificationMsgList(mysqli $connection, $notificationArr)
{

    $deleteStatus = DELETE_STATUS::NOT_DELETE;
    $user_arr = array();
    $select_user_query = "SELECT firstname, lastname, profilepic,description as user_description FROM " . TABLE_USER . " WHERE id = ? AND is_testdata = " . $notificationArr['is_testdata'] . "";

    $select_user_stmt = $connection->prepare($select_user_query);
    $select_user_stmt->bind_param('i', $notificationArr['sender_id']);
    $select_user_stmt->execute();
    $select_user_stmt->store_result();
    if ($select_user_stmt->num_rows > 0) {
        $user_arr = fetch_assoc_all_values($select_user_stmt);

        $notificationArr['firstname'] = $user_arr['firstname'];
        $notificationArr['lastname'] = $user_arr['lastname'];
        $notificationArr['profilepic'] = $user_arr['profilepic'];

        if ($notificationArr['notification_type'] != 1 && $notificationArr['notification_type'] != 2) { //&& $notificationArr['notification_type'] != 7

            $select_feed_image_query = "SELECT id as media_id,media_type,media_name  FROM " . TABLE_MEDIA . " WHERE post_id = ? AND is_delete = " . $deleteStatus . " AND is_testdata = " . $notificationArr['is_testdata'] . " ORDER BY id ASC LIMIT 1";

            $select_feed_image_stmt = $connection->prepare($select_feed_image_query);
            $select_feed_image_stmt->bind_param('i', $notificationArr['notification_type_id']);
            $select_feed_image_stmt->execute();
            $select_feed_image_stmt->store_result();
            $profile_image_arr = fetch_assoc_all_values($select_feed_image_stmt);

            $notificationArr['post_media'][0]['media_id'] = $profile_image_arr['media_id'];
            $notificationArr['post_media'][0]['media_url'] = ($profile_image_arr['media_name'] ? $profile_image_arr['media_name'] : '');
            $notificationArr['post_media'][0]['media_type'] = $profile_image_arr['media_type'];
        }

    }

    if (NOTIFICATION_TYPE_FOLLOW == $notificationArr['notification_type']) {

        $notificationArr['message'] = "started following you.";

    } else if (NOTIFICATION_TYPE_REQUEST_PENDING == $notificationArr['notification_type']) {

        $notificationArr['message'] = "sent a request to follow you.";

    } else if (NOTIFICATION_TYPE_COMMENT == $notificationArr['notification_type']) {

        $notificationArr['message'] = "commented on your post.";


    } else if (NOTIFICATION_TYPE_LIKE == $notificationArr['notification_type']) {

        $notificationArr['message'] = "liked your post.";

    } else if (NOTIFICATION_TYPE_ADD_MEMBER_GROUP == $notificationArr['notification_type']) {

        $notificationArr['message'] = "added you in the group.";

    } else if (NOTIFICATION_TYPE_ADD_ME_MEMBER_GROUP == $notificationArr['notification_type']) {

        $notificationArr['message'] = "You have joined in the group.";

    } else if (NOTIFICATION_TYPE_UPLOAD_POST_GROUP == $notificationArr['notification_type']) {

        $notificationArr['message'] = "uploaded a new post.";

    }

    return $notificationArr;
}

?>