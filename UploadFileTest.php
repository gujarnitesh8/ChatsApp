<?php

include_once 'SendEmail.php';

class UploadFileTest
{

    protected $connection;

    function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {

            case "UploadFileTest": {
                return $this->uploadChatFile();
            }
                break;
        }
    }

    public function loginUser($userData)
    {
        $status = 2;
        $connection = $this->connection;

        $role_id = "user";

        //$first_name = validateObject($userData, 'first_name', "");

        //$last_name = validateObject($userData, 'last_name', "");

        $country_code = validateObject($userData, 'country_code', "");

        $phone_no = validateObject($userData, 'phone_no', "");

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_make = validateObject($userData, 'device_type', 0);
        $device_make = addslashes($device_make);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $security = new SecurityFunctions($connection);
        $isSecure = $security->checkForSecurityNew($access_key, $secret_key, $device_make);
        // $isSecure=YES;
        $getCurrentDate = getDefaultDate();

        $verify_token = generateRandomCode(6);

        $appname = APPNAME;

        if ($isSecure == NO) {
            $data['message'] = MALICIOUS_SOURCE;
            $data['status'] = MALICIOUS_SOURCE_STATUS;
        } else {
            if ($country_code == "" && $phone_no) {
                $data['status'] = FAILED;
                $data['message'] = DEV_ERROR;
            } else {
                if ($country_code[0] != "+") {
                    $country_code = "+" . $country_code;
                }
                $phone_number = $country_code . $phone_no;

                $posts = array();
                $is_delete = DELETE_STATUS::NOT_DELETE;

                $errorMsg = "";

                //Update Null deviceToken If already Exist
                $this->checkAndUpdateNullDeviceToken($device_token);

                $select_phone_query = "Select
 u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
              from " . TABLE_USER . "  where u.user_country_code = ? and u.user_phone_no = ? and u.is_delete= ? AND u.is_testdata = ? ";

                if ($select_phone_stmt = $this->connection->prepare($select_phone_query)) {
                    $select_phone_stmt->bind_param("ssss", $country_code, $phone_no, $is_delete, $is_testdata);
                    $select_phone_stmt->execute();
                    $select_phone_stmt->store_result();

                    if ($select_phone_stmt->num_rows > 0) {
                        while ($val = fetch_assoc_all_values($select_phone_stmt)) {

                            $update_otp_query = "UPDATE " . TABLE_OTP . " SET otp_message = ? WHERE user_id = ? AND is_delete = ? AND is_testdata = ?";

                            if ($updateStmt1 = $connection->prepare($update_otp_query)) {
                                $updateStmt1->bind_param("ssss", $verify_token, $val['user_id'], $is_delete, $is_testdata);
                                $updateStmt1->execute();
                            }

                            $get_guid = "";
                            if ($val['guid'] == null || $val['guid'] == '') {
                                $get_guid = updateGuidForUser($this->connection, $val['user_id'], $is_testdata);
                            } else {
                                $get_guid = $val['guid'];
                            }

                            //Update Token for user
                            $tokenData = new stdClass;
                            $tokenData->GUID = $get_guid;
                            $tokenData->userId = $val['user_id'];
                            $tokenData->deviceType = $device_make;
                            $tokenData->deviceToken = $device_token;

                            $user_token = $security->updateTokenforUser($tokenData);

                            if ($user_token['status'] == SUCCESS) {
                                $data['userToken'] = $user_token['userToken'];
                            }

                            $contact_number = $phone_number;
                            $from = TWILIO_FROM_NUMBER;
                            $to = $contact_number; // twilio trial verified number
                            $body = "Your " . APPNAME . " App verification code is " . $verify_token;
                            $data_sms = array(
                                'From' => $from,
                                'To' => $to,
                                'Body' => $body,
                            );

                            $response = callTwilioAPI($data_sms);
                            //$response[STATUS] = SUCCESS;
                            if ($response[STATUS] == SUCCESS) {
                                $data['otp']['otp_message'] = $verify_token;
                                $data['status'] = SUCCESS;
                                $data['message'] = "OTP is send to your number.";
                                return $data;
                            } else {
                                return $response;
                            }
                        }
                    }
                    $select_phone_stmt->close();
                }

                //Generate GUID
                $generate_guid = $security->gen_uuid();
                //End to Generate GUID

                $insertFields1 = " role_id, user_country_code, user_phone_no, guid, is_delete ,is_testdata, created_date";
                $getCurrentDate = getDefaultDate();
                $insert_query1 = "Insert into " . TABLE_USER . " (" . $insertFields1 . ") values(?,?,?,?,?,?,?)";

                if ($insertStmt1 = $connection->prepare($insert_query1)) {

                    $insertStmt1->bind_param('sssssss', $role_id, $country_code, $phone_no, $generate_guid, $is_delete, $is_testdata, $getCurrentDate);

                    if ($insertStmt1->execute()) {

                        $user_inserted_id = $insertStmt1->insert_id;

                        $insertField2 = "user_id, otp_message, created_date, is_testdata";
                        $insert_query2 = "INSERT INTO " . TABLE_OTP . " (" . $insertField2 . ") values(?,?,?,?)";

                        if ($insertStmt2 = $connection->prepare($insert_query2)) {

                            $insertStmt2->bind_param('ssss', $user_inserted_id, $verify_token, $getCurrentDate, $is_testdata);
                            $insertStmt2->execute();
                        }

                        $insertField3 = "post_id, post_type, created_date, is_testdata";
                        $insert_query3 = "INSERT INTO " . TABLE_MEDIA . " (" . $insertField3 . ") values(?,1,?,?)";

                        if ($insertStmt3 = $connection->prepare($insert_query3)) {

                            $insertStmt3->bind_param('sss', $user_inserted_id, $getCurrentDate, $is_testdata);
                            $insertStmt3->execute();
                        }

                        $user_query = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                        FROM " . TABLE_USER . " as u WHERE u.user_id = ? AND u.is_delete = ? AND u.is_testdata = ? ";

                        if ($select_stmt = $connection->prepare($user_query)) {

                            $select_stmt->bind_param("sss", $user_inserted_id, $is_delete, $is_testdata);
                            $select_stmt->execute();
                            $select_stmt->store_result();

                            if ($select_stmt->num_rows > 0) {

                                while ($post = fetch_assoc_all_values($select_stmt)) {
                                    $get_guid = "";
                                    if ($post['guid'] == null || $post['guid'] == '') {
                                        $get_guid = updateGuidForUser($this->connection, $post['id'], $is_testdata);
                                    } else {
                                        $get_guid = $post['guid'];
                                    }

                                    //Update Token for user
                                    $tokenData = new stdClass;
                                    $tokenData->GUID = $get_guid;
                                    $tokenData->userId = $post['user_id'];
                                    $tokenData->deviceType = $device_make;
                                    $tokenData->deviceToken = $device_token;
                                    $user_token = $security->updateTokenforUser($tokenData);
                                    if ($user_token['status'] == SUCCESS) {
                                        $data['userToken'] = $user_token['userToken'];
                                    }
                                    //End Code for token
                                    $post['isSync'] = 0;
                                    $posts = $post;
                                }

                                $contact_number = $phone_number;
                                $from = TWILIO_FROM_NUMBER;
                                $to = $contact_number; // twilio trial verified number
                                $body = "Your " . APPNAME . " App verification code is " . $verify_token;
                                $data_sms = array(
                                    'From' => $from,
                                    'To' => $to,
                                    'Body' => $body,
                                );

                                $response = callTwilioAPI($data_sms);
                                //$response[STATUS] = SUCCESS;
                                if ($response[STATUS] == SUCCESS) {
                                    $data['otp']['otp_message'] = $verify_token;
                                    $status = 1;
                                    $errorMsg = "OTP is send to your number.";
                                } else {
                                    return $response;
                                }
                            }
                            $select_stmt->close();
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Failed to register users." . $insertStmt1->error;
                    }
                } else {
                    $status = 2;
                    $errorMsg = "Failed to register users." . $this->connection->error;
                }

                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
            }
        }
        return $data;
    }

    public function logoutUser($userData)
    {
        $status = 2;
        $errorMsg = "";
        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $device_type = validateObject($userData, 'device_type', '');
        $device_type = addslashes($device_type);

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $select_udid_query = "Select user_id from " . TABLE_USER . " where user_id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_udid_stmt = $this->connection->prepare($select_udid_query)) {
                $select_udid_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_udid_stmt->execute();
                $select_udid_stmt->store_result();
                if ($select_udid_stmt->num_rows > 0) {
                    // $update_token_query = "Update " . TABLE_USER . " set device_token = '' where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
                    // if ($updateStmt = $this->connection->prepare($update_token_query)) {
                    //     $updateStmt->bind_param('ss', $user_id, $is_testdata);
                    //     if ($updateStmt->execute()) {
                    //         $updateStmt->close();

                    $select_app_token_query = "Select tokens_id from " . TABLE_APP_TOKENS . " where user_id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND device_type = ? ";
                    if ($select_app_token_stmt = $this->connection->prepare($select_app_token_query)) {
                        $select_app_token_stmt->bind_param("ss", $user_id, $device_type);
                        $select_app_token_stmt->execute();
                        $select_app_token_stmt->store_result();
                        if ($select_app_token_stmt->num_rows > 0) {
                            $update_app_token_query = "Update " . TABLE_APP_TOKENS . " set device_token = '' where user_id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND device_type = ? ";
                            if ($updateTokenStmt = $this->connection->prepare($update_app_token_query)) {
                                $updateTokenStmt->bind_param('ss', $user_id, $device_type);
                                if ($updateTokenStmt->execute()) {
                                    $updateTokenStmt->close();
                                }
                            }
                            $select_app_token_stmt->close();
                        }
                    }
                    $status = 1;
                    $errorMsg = "User logged out successfully";
                    //     } else {
                    //         $status = 2;
                    //         $errorMsg = "Failed to logout.";
                    //     }
                    // } else {
                    //     $status = 2;
                    //     $errorMsg = "Failed to logout.";
                    // }
                } else {
                    $status = FAILED;
                    $errorMsg = DEFAULT_NO_RECORD;
                }
                $select_udid_stmt->close();
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }
        return $data;
    }

    public function manageProfile($userData)
    {

        $status = 2;

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $first_name = validateObject($userData, 'first_name', "");
        //$first_name = addslashes($first_name);

        $last_name = validateObject($userData, 'last_name', "");
        //$last_name = addslashes($last_name);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        $profile_image = validateObject($userData, 'profile_image', "");

        if ($first_name == "" || $last_name == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $posts = array();
            $profile_image_name = "";
            $errorMsg = "";

            $select_user_exist_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
            from " . TABLE_USER . " as u  where  u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_user_exist_stmt = $this->connection->prepare($select_user_exist_query)) {
                $select_user_exist_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_user_exist_stmt->execute();
                $select_user_exist_stmt->store_result();

                if ($select_user_exist_stmt->num_rows > 0) {

                    $user = fetch_assoc_all_values($select_user_exist_stmt);


                    if (strlen($profile_image) > 0) {
                        $select_profile_image_query = "Select * from " . TABLE_MEDIA . "  where  post_id = ? and post_type = 1 and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";

                        if ($select_profile_image_stmt = $this->connection->prepare($select_profile_image_query)) {
                            $select_profile_image_stmt->bind_param("ss", $user_id, $is_testdata);
                            $select_profile_image_stmt->execute();
                            $select_profile_image_stmt->store_result();

                            if ($select_profile_image_stmt->num_rows > 0) {
                                $profile_img = fetch_assoc_all_values($select_profile_image_stmt);

                                if ($profile_img['media_name'] != '') {
                                    $this->unLinkImageFolder(SERVER_PROFILE_IMAGES, $profile_img['media_name']);
                                    $this->unLinkImageFolder(SERVER_THUMB_IMAGE, $profile_img['media_name']);
                                }
                            }
                        }
                        $profile_image_name = round(microtime(true) * 1000) . generateRandomString(7) . ".jpg";
                        $this->uploadUserProfile($profile_image_name, $profile_image);
                        createThumbnailImage(SERVER_PROFILE_IMAGES . $profile_image_name, $profile_image_name);


                        $update_query = "Update " . TABLE_MEDIA . " set media_name = ? where post_id = ? and post_type = 1 and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                        if ($updateStmt = $this->connection->prepare($update_query)) {
                            $updateStmt->bind_param('sss', $profile_image_name, $user_id, $is_testdata);
                            if ($updateStmt->execute()) {
                                $updateStmt->close();
                            } else {
                                $status = 2;
                            }
                        } else {
                            $status = 2;
                        }
                    }

                    $update_query = "Update " . TABLE_USER . " set first_name = ?, last_name = ? where user_id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                    if ($updateStmt = $this->connection->prepare($update_query)) {
                        $updateStmt->bind_param('ssss', $first_name, $last_name, $user_id, $is_testdata);
                        if ($updateStmt->execute()) {
                            $updateStmt->close();
                            $errorMsg = "Profile updated successfully.";
                            $user_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                            from " . TABLE_USER . " as u  where u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                            if ($select_user_stmt = $this->connection->prepare($user_query)) {
                                $select_user_stmt->bind_param("ss", $user_id, $is_testdata);
                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();
                                if ($select_user_stmt->num_rows > 0) {
                                    while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                        $posts = $post;
                                    }
                                    $status = 1;
                                    $data['User'] = $posts;
                                    $data['User']['profile_pic'] = $profile_image_name;
                                }
                                $select_user_stmt->close();
                            }
                        } else {
                            $status = 2;
                            $errorMsg = "Update query " . $updateStmt->error;
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Update query " . $this->connection->error;
                    }
                    $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                    $data['message'] = $errorMsg;
                } else {
                    $data['status'] = FAILED;
                    $data['message'] = DEFAULT_NO_RECORD;
                }
                $select_user_exist_stmt->close();
            }

        }
        return $data;
    }

    public function loadConversion($userData)
    {
        $status = 2;

        $userId = validateObject($userData, 'user_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $offset = validateObject($userData, 'offset', 0);

        $limit = LIMIT_CHAT;
        $offset = $offset * $limit;

        if ($userId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $posts = array();
            $profile_image_name = "";
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_conversion_query = "SELECT conversion_id, created_by, recevied_by, last_message, modified_date FROM " . TABLE_CONVERSION . " WHERE (created_by = ? OR recevied_by = ?) AND is_delete=" . $is_delete . " AND is_testdata=? ORDER BY modified_date DESC LIMIT " . $limit . " OFFSET " . $offset . "";

            if ($select_conversion_stmt = $this->connection->prepare($select_conversion_query)) {
                $select_conversion_stmt->bind_param('iii', $userId, $userId, $is_testdata);

                $select_conversion_stmt->execute();
                $select_conversion_stmt->store_result();

                if ($select_conversion_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($select_conversion_stmt)) {

                        $select_unread_counter_query = "SELECT message_id FROM " . TABLE_CHAT_MESSAGE . " WHERE is_read = 0  AND receiver_id = ? AND conversion_id = ? AND is_delete=" . $is_delete . " AND is_testdata=?";

                        if ($select_unread_counter_stmt = $this->connection->prepare($select_unread_counter_query)) {
                            $select_unread_counter_stmt->bind_param('iii', $userId, $post['conversion_id'], $is_testdata);

                            $select_unread_counter_stmt->execute();
                            $select_unread_counter_stmt->store_result();

                            $post['un_read_counter'] = $select_unread_counter_stmt->num_rows;
                        }

                        if ($userId != $post['created_by']) {
                            $select_user_query = "SELECT first_name,last_name,m.media_name as profile_pic FROM " . TABLE_USER . " AS u LEFT JOIN " . TABLE_MEDIA . " AS m ON u.user_id=m.post_id WHERE m.post_type = 1 AND u.user_id = ? AND u.is_delete=" . $is_delete . " AND u.is_testdata=? AND m.is_delete=" . $is_delete . " AND m.is_testdata=?";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param('iii', $post['created_by'], $is_testdata, $is_testdata);

                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $post['other_user_profile_pic'] = $user_post['profile_pic'];
                                        $post['other_user_first_name'] = $user_post['first_name'];
                                        $post['other_user_last_name'] = $user_post['last_name'];
                                    }
                                }
                            }
                            $post['user_id'] = (int)$userId;
                            $post['other_user_id'] = $post['created_by'];
                        } else {
                            $select_user_query = "SELECT first_name,last_name,m.media_name as profile_pic FROM " . TABLE_USER . " AS u LEFT JOIN " . TABLE_MEDIA . " AS m ON u.user_id=m.post_id WHERE m.post_type = 1 AND u.user_id = ? AND u.is_delete=" . $is_delete . " AND u.is_testdata=? AND m.is_delete=" . $is_delete . " AND m.is_testdata=?";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param('iii', $post['recevied_by'], $is_testdata, $is_testdata);

                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $post['other_user_profile_pic'] = $user_post['profile_pic'];
                                        $post['other_user_first_name'] = $user_post['first_name'];
                                        $post['other_user_last_name'] = $user_post['last_name'];
                                    }
                                }
                            }
                            $post['user_id'] = (int)$userId;
                            $post['other_user_id'] = $post['recevied_by'];
                        }

                        $posts[] = $post;
                    }
                    $data['conversions_list'] = $posts;
                    $status = 1;
                    $errorMsg = "Conversions listed successfully.";
                } else {
                    $status = 2;
                    $errorMsg = "No conversions found.";
                }
            } else {
                $status = 2;
                $errorMsg = "Something went wrong with query 1";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function loadChatMessage($userData)
    {
        $status = 2;

        $userId = validateObject($userData, 'user_id', 0);
        $otherUserId = validateObject($userData, 'other_user_id', 0);
        $conversionId = validateObject($userData, 'conversion_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $loadingType = validateObject($userData, 'loading_type', 0);
        $lastFeedId = validateObject($userData, 'last_message_id', 0);

        $limit = LIMIT_CHAT;

        if ($userId == 0 || $otherUserId == 0 || $conversionId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $posts = array();
            $profile_image_name = "";
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_chat_query = "SELECT message_id, conversion_id, sender_id, receiver_id, message_type, message_type_caption, chat_message as message, created_date FROM " . TABLE_CHAT_MESSAGE . " AS cm WHERE cm.conversion_id = ? AND is_delete=" . $is_delete . " AND is_testdata = ?";

            if ($lastFeedId == 0) {
                $selectQuerySecondHalf = " ORDER BY message_id DESC LIMIT ?";
                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iii", $conversionId, $is_testdata, $limit);
            } else {
                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
                    $selectQuerySecondHalf = " AND message_id < ? ORDER BY message_id DESC LIMIT ?";
                } else {
                    $selectQuerySecondHalf = " AND message_id > ? ORDER BY message_id DESC LIMIT ?";
                }

                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iiii", $conversionId, $is_testdata, $lastFeedId, $limit);
            }


            if ($select_chat_stmt->execute()) {
                $select_chat_stmt->store_result();

                if ($select_chat_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($select_chat_stmt)) {
                        if ($post['message_type'] == 'IMAGE' || $post['message_type'] == 'VIDEO') {

                            $select_media_query = "SELECT media_id, media_name, media_type FROM " . TABLE_MEDIA . " WHERE post_id = ? AND post_type = 4 AND is_delete=" . $is_delete . " AND is_testdata = ?";
                            $select_media_stmt = $this->connection->prepare($select_media_query);
                            $select_media_stmt->bind_param("ii", $post['message_id'], $is_testdata);
                            $select_media_stmt->execute();
                            $select_media_stmt->store_result();
                            if ($select_media_stmt->num_rows > 0) {
                                $mediaArr = array();
                                while ($media_arr = fetch_assoc_all_values($select_media_stmt)) {
                                    $mediaArr['media_id'] = $media_arr['media_id'];
                                    $mediaArr['feed_image'] = $media_arr['media_name'];
                                    $mediaArr['type'] = $media_arr['media_type'];
                                }
                                $post['media'] = $mediaArr;
                            }
                        }
                        if ($userId == $post['sender_id']) {
                            $post['me'] = 1;
                        } else {
                            $post['me'] = 0;
                        }
                        $posts[] = $post;
                    }
                    $data['chat_list'] = $posts;
                    $status = 1;
                    $errorMsg = "Chat listed successfully.";
                }

            } else {
                $status = 2;
                $errorMsg = "Something went wrong on query 1";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function loadChatMessage2($userData)
    {
        $status = 2;

        $userId = validateObject($userData, 'user_id', 0);
        $otherUserId = validateObject($userData, 'other_user_id', 0);
        $conversionId = validateObject($userData, 'conversion_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $loadingType = validateObject($userData, 'loading_type', 0);
        $lastFeedId = validateObject($userData, 'last_message_id', 0);

        $limit = LIMIT_CHAT;

        if ($userId == 0 || $otherUserId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $posts = array();
            $profile_image_name = "";
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_chat_query = "SELECT message_id, conversion_id, sender_id, receiver_id, message_type, message_type_caption, chat_message as message, created_date FROM " . TABLE_CHAT_MESSAGE . " AS cm WHERE ((cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.receiver_id = ? AND cm.sender_id = ?)) AND is_delete=" . $is_delete . " AND is_testdata = ?";

            if ($lastFeedId == 0) {
                $selectQuerySecondHalf = " ORDER BY message_id DESC LIMIT ?";
                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iiiiii", $userId, $otherUserId, $userId, $otherUserId, $is_testdata, $limit);
            } else {
                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
                    $selectQuerySecondHalf = " AND message_id < ? ORDER BY message_id DESC LIMIT ?";
                } else {
                    $selectQuerySecondHalf = " AND message_id > ? ORDER BY message_id DESC LIMIT ?";
                }

                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iiiiiii", $userId, $otherUserId, $userId, $otherUserId, $is_testdata, $lastFeedId, $limit);
            }


            if ($select_chat_stmt->execute()) {
                $select_chat_stmt->store_result();

                if ($select_chat_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($select_chat_stmt)) {
                        if ($post['message_type'] == 'IMAGE' || $post['message_type'] == 'VIDEO') {

                            $select_media_query = "SELECT media_id, media_name, media_type FROM " . TABLE_MEDIA . " WHERE post_id = ? AND post_type = 4 AND is_delete=" . $is_delete . " AND is_testdata = ?";
                            $select_media_stmt = $this->connection->prepare($select_media_query);
                            $select_media_stmt->bind_param("ii", $post['message_id'], $is_testdata);
                            $select_media_stmt->execute();
                            $select_media_stmt->store_result();
                            if ($select_media_stmt->num_rows > 0) {
                                $mediaArr = array();
                                while ($media_arr = fetch_assoc_all_values($select_media_stmt)) {
                                    $mediaArr['media_id'] = $media_arr['media_id'];
                                    $mediaArr['feed_image'] = $media_arr['media_name'];
                                    $mediaArr['type'] = $media_arr['media_type'];
                                }
                                $post['media'] = $mediaArr;
                            }
                        }
                        if ($userId == $post['sender_id']) {
                            $post['me'] = 1;
                        } else {
                            $post['me'] = 0;
                        }
                        $posts[] = $post;
                    }
                    $data['chat_list'] = $posts;
                    $status = 1;
                    $errorMsg = "Chat listed successfully.";
                }

            } else {
                $status = 2;
                $errorMsg = "Something went wrong on query 1";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function uploadChatFile()
    {

        $imageInfo = array();
        $errorMsg = "";
        $status = 2;

        $keyMedia = 'chat_media';
        $isQuerySuccess = false;

        if (!empty($_FILES[$keyMedia]['name'])) {
            $mediaCount = 1;
            $isQuerySuccess = true;
        } else {
            $mediaCount = 0;
        }

        $hasMedia = $mediaCount > 0 ? 1 : 0;

        if ($hasMedia > 0 && $isQuerySuccess) {
            if ($isQuerySuccess) {
                $status = 1;
                $errorMsg = "Image uploaded";
                $imageInfo = $this->uploadMediasToFeed($isQuerySuccess, $keyMedia)['data'];
            }
        }

        $data['status'] = ($status > 1 ? FAILED : SUCCESS);
        $data['message'] = $errorMsg;
        $data['chat_media'] = $imageInfo;
        return $data;
    }

    public function readMessage($userData)
    {
        $status = 2;

        $userId = validateObject($userData, 'user_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);


        return $data;
    }

    public function getUserDetails($userData)
    {
        $status = 2;

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $self_user_id = validateObject($userData, 'self_user_id', 0);
        $self_user_id = addslashes($self_user_id);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        $posts = array();

        $errorMsg = "";

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {

            $select_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
            from " . TABLE_USER . " as u where u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {
                    $status = 1;
                    $user = fetch_assoc_all_values($select_stmt);

                    $select_media_query = "Select media_type, media_name from " . TABLE_MEDIA . "  where post_id = ? and post_type = 1  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

                    if ($select_media_stmt = $this->connection->prepare($select_media_query)) {
                        $select_media_stmt->bind_param("ss", $user_id, $is_testdata);
                        $select_media_stmt->execute();
                        $select_media_stmt->store_result();

                        if ($select_media_stmt->num_rows > 0) {
                            $media = fetch_assoc_all_values($select_media_stmt);
                            $user['profile_pic'] = $media['media_name'];
                        }
                    }

                    if ($self_user_id != 0) {
                        $select_following_query = "Select followers_id FROM " . TABLE_USER_FOLLOWERS . "  WHERE sender_id = ? and receiver_id = ? and (request_status = 'ACCEPT' OR request_status = 'PENDING')  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                        if ($select_following_stmt = $this->connection->prepare($select_following_query)) {
                            $select_following_stmt->bind_param("iii", $self_user_id, $user_id, $is_testdata);
                            $select_following_stmt->execute();
                            $select_following_stmt->store_result();

                            if ($select_following_stmt->num_rows > 0) {
                                $user['following_status'] = 1;
                            } else {
                                $user['following_status'] = 0;
                            }
                        }
                    } else {
                        $user['following_status'] = 2;
                    }

                    $data['User'] = $user;
                    $errorMsg = "User listed successfully.";

                } else {
                    $errorMsg = "User not found.";
                    $status = 2;
                }
                $select_stmt->close();
            } else {
                $errorMsg = "Something wrong with select query.";
                $status = 2;
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }
        return $data;
    }

    public function getUnReadCounter($userData)
    {
        $status = 2;
        $errorMsg = "";

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_query = "Select notification_id from " . TABLE_NOTIFICATION . "  where received_by = ? and is_read = 0 and is_delete='" . $is_delete . "' AND is_testdata = ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();
                $status = 1;
                if ($select_stmt->num_rows > 0) {
                    $data['data']['notification_badge'] = $select_stmt->num_rows;
                } else {
                    $data['data']['notification_badge'] = $select_stmt->num_rows;
                }

                $select_query2 = "Select message_id from " . TABLE_CHAT_MESSAGE . "  where receiver_id = ? and is_read = 0 and is_delete='" . $is_delete . "' AND is_testdata = ?";

                if ($select_stmt2 = $this->connection->prepare($select_query2)) {
                    $select_stmt2->bind_param("ss", $user_id, $is_testdata);
                    $select_stmt2->execute();
                    $select_stmt2->store_result();
                    $status = 1;
                    if ($select_stmt2->num_rows > 0) {
                        $data['data']['message_badge'] = $select_stmt2->num_rows;
                    } else {
                        $data['data']['message_badge'] = $select_stmt2->num_rows;
                    }
                    $errorMsg = "Listed successfully";
                } else {
                    $errorMsg = "Something wrong with select query 2.";
                    $status = 2;
                }
            } else {
                $errorMsg = "Something wrong with select query.";
                $status = 2;
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function readNotification($userData)
    {
        $status = 2;
        $errorMsg = "";

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $update_query = "UPDATE " . TABLE_NOTIFICATION . " SET is_read = 1 WHERE received_by = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";
            if ($update_stmt = $this->connection->prepare($update_query)) {
                $update_stmt->bind_param("ss", $user_id, $is_testdata);
                $update_stmt->execute();

                $status = 1;
                $errorMsg = "Read successfully";
            } else {
                $errorMsg = "Something wrong with update query.";
                $status = 2;
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    function unLinkImageFolder($folder, $image_name)
    {
        $uploadDir = $folder . $image_name;
        if (!unlink($uploadDir)) {
            //echo ("Error deleting ");
        } else {
            //echo ("Deleted ");
        }
    }

    function resizeImage($SrcImage, $DestImage, $MaxWidth, $MaxHeight, $Quality)
    {
        list($iWidth, $iHeight, $type) = getimagesize($SrcImage);


        if (($iWidth <= $MaxWidth) && ($iHeight <= $MaxHeight)) {
            /*copy($SrcImage, $DestImage);
            unlink($SrcImage);*/
            // return $SrcImage;
        } //no resizing needed

        $ImageScale = min($MaxWidth / $iWidth, $MaxHeight / $iHeight);
        $NewWidth = ceil($ImageScale * $iWidth);
        $NewHeight = ceil($ImageScale * $iHeight);
        $NewCanves = imagecreatetruecolor($NewWidth, $NewHeight);

        switch (strtolower(image_type_to_mime_type($type))) {
            case 'image/jpeg':
                $NewImage = imagecreatefromjpeg($SrcImage);
                break;
            case 'image/png':
                $NewImage = imagecreatefrompng($SrcImage);
                break;
            case 'image/gif':
                $NewImage = imagecreatefromgif($SrcImage);
                break;
            default:
                return false;
        }

        // Resize Image
        if (imagecopyresampled($NewCanves, $NewImage, 0, 0, 0, 0, $NewWidth, $NewHeight, $iWidth, $iHeight)) {
            // copy file
            if (imagejpeg($NewCanves, $DestImage, $Quality)) {
                imagedestroy($NewCanves);
                return true;
            }
        }
    }

    function uploadUserProfile($profile_image_name, $base64_image)
    {
        //__DIR__ or dirname(__FILE__)
        //        $profile_image_upload_dir = ".." . PROFILE_IMAGES . $profile_image_name;

        $profile_image_upload_dir = SERVER_PROFILE_IMAGES . $profile_image_name;

        if (!file_put_contents($profile_image_upload_dir, base64_decode($base64_image))) {
            return NO;

        } else {
            return YES;
        }
        return NO;
    }

    function checkAndUpdateNullDeviceToken($device_token)
    {
        $select_app_token_query = "Select tokens_id from " . TABLE_APP_TOKENS . " where device_token = ?  ";
        if ($select_app_token_stmt = $this->connection->prepare($select_app_token_query)) {
            $select_app_token_stmt->bind_param("s", $device_token);
            $select_app_token_stmt->execute();
            $select_app_token_stmt->store_result();
            if ($select_app_token_stmt->num_rows > 0) {
                $update_app_token_query = "Update " . TABLE_APP_TOKENS . " set device_token = '' where device_token = ? ";
                if ($updateTokenStmt = $this->connection->prepare($update_app_token_query)) {
                    $updateTokenStmt->bind_param('s', $device_token);
                    if ($updateTokenStmt->execute()) {
                        $updateTokenStmt->close();


                        $update_token_query = "Update " . TABLE_USER . " set device_token = '' where device_token = ? ";
                        if ($updateStmt = $this->connection->prepare($update_token_query)) {
                            $updateStmt->bind_param('s', $device_token);
                            if ($updateStmt->execute()) {
                                $updateStmt->close();
                            }
                        }
                    }
                }
                $select_app_token_stmt->close();
            }
        }
    }

    function uploadMediasToFeed(&$isQuerySuccess, $keyMedia)
    {
        $status = FAILED;
        $posts = array();
        $errorMsg = "";
        if ($_FILES[$keyMedia]['name'] != '') {

            if ($_FILES[$keyMedia]["error"] > 0) {

                $posts[$keyMedia] = null;
                $errorMsg = fileUploadCodeToMessage($_FILES[$keyMedia]["error"]);

            } else {
                $ext = pathinfo($_FILES[$keyMedia]['name'], PATHINFO_EXTENSION);
                $file = $milliseconds = round(microtime(true) * 1000) . generateRandomString(7) . "." . $ext;

                $mime = $_FILES[$keyMedia]['type'];

                if (strstr($mime, "image/")) {
                    $feedMediaType = FEED_MEDIA::IMAGE;
                } else if (strstr($mime, "video/")) {
                    $feedMediaType = FEED_MEDIA::VIDEO;

                } else if (strstr($mime, "audio/")) {
                    $feedMediaType = FEED_MEDIA::AUDIO;

                } else {
                    $status = FAILED;
                    $errorMsg = "Only images are allowed to upload.";
                    $posts[$keyMedia] = null;

                    $isQuerySuccess = false;

                    $data[STATUS] = $status;
                    $data[MESSAGE] = $errorMsg;
                    $data[DATA] = $posts;

                    return $data;
                }
                $uploadFile = $this->getMediaPrefixLocalPathBasedOnType($feedMediaType) . $file;

                if (move_uploaded_file($_FILES[$keyMedia]['tmp_name'], $uploadFile)) {

                    if ($feedMediaType == FEED_MEDIA::IMAGE) {
                        createImageThumbnail($uploadFile, $file, SERVER_THUMB_IMAGE);
                    } else if ($feedMediaType = FEED_MEDIA::VIDEO) {
                        createVideoThumbnail($uploadFile, $file, SERVER_THUMB_IMAGE);
                    }


                    $thumbImgName = NULL;

                    $posts = array('type' => $feedMediaType, 'feed_image' => $file);

                } else {
                    $status = FAILED;
                    $errorMsg = "Failed to upload image on server.";
                }
            }
        } else {
            $errorMsg = "Invalid file";
        }

        if ($status == FAILED) {
            $isQuerySuccess = false;
        } else {
            $isQuerySuccess = true;
        }

        $data[STATUS] = $status;
        $data[MESSAGE] = $errorMsg;
        $data[DATA] = $posts;

        return $data;
    }

    function getMediaPrefixLocalPathBasedOnType($mediaType)
    {

        switch ($mediaType) {
            case  FEED_MEDIA::IMAGE: {
                return SERVER_CHAT_IMAGES_PATH;
            }
            case  FEED_MEDIA::VIDEO: {
                return SERVER_CHAT_VIDEOS_PATH;
            }
            default: {
                return "";
            }
        }
    }

}

?>