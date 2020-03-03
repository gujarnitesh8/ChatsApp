<?php

include_once 'SendEmail.php';

class UserFunctions
{

    protected $connection;

    public function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {
            case "Register":{
                    return $this->registerUser($postData);
                }
                break;

            default:{
                    return null;
                }
        }
    }

    public function registerUser($userData)
    {

        $connection = $this->connection;
        $status = 2;

        $name = validateObject($userData, 'name', null);
        $name = addslashes($name);

        $mobile_number = validateObject($userData, 'mobile_number', "");
        $mobile_number = addslashes($mobile_number);

        
        $country_code= validateObject($userData, 'country_code', "");
        $country_code= addslashes($country_code);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $isSecure = YES;

        // print_r($userData);exit();

        $posts = array();
        $isNewUser = true;
        $errorMsg = "";

        if ($mobile_number == '') {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $mobileNumberCheck_query = "Select * from users as u where u.user_country_code=? AND u.mobile_number = ? AND u.is_testdata = ?";
            if ($mono_stmt = $this->connection->prepare($mobileNumberCheck_query)) {

                $mono_stmt->bind_param("sss",$country_code, $mobile_number, $is_testdata);
                $mono_stmt->execute();
                $mono_stmt->store_result();

                if ($mono_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($mono_stmt)) {
                        $otp = $this->resendOTP($post);
                        $post['otp'] = $otp;
                        $isNewUser = false;
                        $posts['User'] = $post;
                        $status = 1;
                        $errorMsg = "Otp sent successfully.";
                    }
                }
            } else {
                //echo "select=> " . $mono_stmt->error;
                $status = 2;
                $errorMsg = "Failed to register users." . $mono_stmt->error;
            }
            $mono_stmt->close();

            ////   INSERT PART START
            if ($isNewUser) {
                $profile_image_name = null;
                $getCurrentDate = getDefaultDate();
                $insert_query = "insert into users (name,mobile_number,user_country_code,photo,created_date,is_testdata) values('" . $name . "','" . $mobile_number . "','".$country_code."', '" . $profile_image_name . "', '" . $getCurrentDate . "', '" . $is_testdata . "')";
                if ($insertStmt = $this->connection->prepare($insert_query)) {
                    if ($insertStmt->execute()) {
                        $user_inserted_id = $insertStmt->insert_id;

                        $user_query = "Select * from users as u where u.id = ? AND u.is_testdata = ?";
                        if ($select_stmt = $this->connection->prepare($user_query)) {

                            $select_stmt->bind_param("ss", $user_inserted_id, $is_testdata);
                            $select_stmt->execute();
                            $select_stmt->store_result();

                            if ($select_stmt->num_rows > 0) {
                                while ($post = fetch_assoc_all_values($select_stmt)) {
                                    $otp = $this->resendOTP($post);
                                    $post['otp'] = $otp;
                                    $posts['User'] = $post;
                                    $status = 1;
                                    $errorMsg = "User registered successfully.";
                                }
                            }
                        } else {
                            //echo "select=> " . $select_stmt->error;
                            $status = 2;
                            $errorMsg = "Failed to register users." . $select_stmt->error;
                        }
                        $select_stmt->close();

                    } else {
                        $status = 2;
                        $errorMsg = "Failed to register users." . $insertStmt->error;
                        $posts = null;
                    }
                } else {
                    echo $insert_query;
                    $status = 2;
                    $errorMsg = "Failed to register users." . $insertStmt->error;
                    $posts = null;
                }
                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
                $data['data'] = $posts;
            } else {
                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
                $data['data'] = $posts;
            }
        }
        return $data;
    }
    public function generateOTP($mobile_number, $user_inserted_id)
    {
        $verify_token = generateRandomCode(9);
        return $verify_token;
    }
    public function resendOTP($userData)
    {

        $user_id = $userData['id'];
        $otp = generateRandomCode(9);
        $select_query = "select * from generated_otp where userId='" . $user_id . "'";
        if ($select_stmt = $this->connection->prepare($select_query)) {
            $select_stmt->execute();
            $select_stmt->store_result();
            if ($select_stmt->num_rows > 0) {
                $update_query = "update generated_otp set otp_message='" . $otp . "' where userId=$user_id";
                if ($updateStmt = $this->connection->prepare($update_query)) {
                    if ($updateStmt->execute()) {
                        $updateStmt->close();
                        return $otp;
                    } else {
                        echo $updateStmt->error;
                    }
                } else {
                    echo $updateStmt->error;
                }
            } else {
                $insert_query = "insert into generated_otp (otp_message, userId) values('" . $otp . "', $user_id)";
                if ($insertStmt = $this->connection->prepare($insert_query)) {
                    if ($insertStmt->execute()) {
                        $user_inserted_id = $insertStmt->insert_id;
                        $insertStmt->close();
                        return $otp;
                    } else {
                        // echo $insertStmt->error;
                    }
                }
            }
        }
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
            $select_udid_query = "Select id from " . TABLE_USER . " where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_udid_stmt = $this->connection->prepare($select_udid_query)) {
                $select_udid_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_udid_stmt->execute();
                $select_udid_stmt->store_result();
                if ($select_udid_stmt->num_rows > 0) {

                    $select_app_token_query = "Select id from " . TABLE_APP_TOKENS . " where user_id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND device_type = ? ";
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

        // $email_id = validateObject($userData, 'email_id', "");
        //$first_name = addslashes($first_name);

        $last_name = validateObject($userData, 'last_name', "");

        $user_name = validateObject($userData, 'user_name', "");
        //$last_name = addslashes($last_name);

        // $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        // $is_testdata = addslashes($is_testdata);

        // $secret_key = validateObject($userData, 'secret_key', "");
        // $secret_key = addslashes($secret_key);

        // $access_key = validateObject($userData, 'access_key', "");
        // $access_key = addslashes($access_key);

        // $device_token = validateObject($userData, 'device_token', 0);
        // $device_token = addslashes($device_token);

        // $device_make = validateObject($userData, 'device_type', 0);
        // $device_make = addslashes($device_make);

        $profile_image = validateObject($userData, 'profile_image', "");

        $user_possition = validateObject($userData, 'user_possition', "");

        // $user_description = validateObject($userData, 'user_description', "");

        // $user_description = addslashes($user_description);

        if ($first_name == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $posts = array();
            $profile_image_name = "";
            $errorMsg = "";

            $select_user_exist_query = "Select u.id, u.firstname, u.lastname, u.password,u.profilepic, u.is_private
            from " . TABLE_USER . " as u  where  u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ? ";
            if ($select_user_exist_stmt = $this->connection->prepare($select_user_exist_query)) {
                $select_user_exist_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_user_exist_stmt->execute();
                $select_user_exist_stmt->store_result();

                if ($select_user_exist_stmt->num_rows > 0) {

                    $user = fetch_assoc_all_values($select_user_exist_stmt);

                    // if (strlen($email_id) > 0) {
                    //     $select_email_query = "Select email from " . TABLE_USER . "  where email = ? and id != ?  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                    //     if ($select_email_stmt = $this->connection->prepare($select_email_query)) {
                    //         $select_email_stmt->bind_param("sss", $email_id, $user_id, $is_testdata);
                    //         $select_email_stmt->execute();
                    //         $select_email_stmt->store_result();
                    //         if ($select_email_stmt->num_rows > 0) {
                    //             $status = 2;
                    //             while ($val = fetch_assoc_all_values($select_email_stmt)) {
                    //                 $errorMsg = "Email address belongs to other user.";

                    //                 $data['status'] = FAILED;
                    //                 $data['message'] = $errorMsg;

                    //                 return $data;
                    //             }
                    //         } else {
                    //             $update_query = "Update " . TABLE_USER . " set email = ? where id = ?  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                    //             if ($updateStmt = $this->connection->prepare($update_query)) {
                    //                 $updateStmt->bind_param('sss', $email_id, $user_id, $is_testdata);
                    //                 if ($updateStmt->execute()) {
                    //                     $updateStmt->close();
                    //                 } else {
                    //                     $status = 2;
                    //                 }
                    //             } else {
                    //                 $status = 2;
                    //             }
                    //         }
                    //         $select_email_stmt->close();
                    //     }
                    // }

                    if (strlen($profile_image) > 0) {

                        if ($user['profilepic'] != "") {
                            $this->unLinkImageFolder(SERVER_PROFILE_IMAGES, $user['profilepic']);
                            $this->unLinkImageFolder(SERVER_THUMB_IMAGE, $user['profilepic']);
                        }

                        $profile_image_name = round(microtime(true) * 1000) . generateRandomString(7) . ".jpg";

                        $profile_image_upload_dir = SERVER_PROFILE_IMAGES . $profile_image_name;
                        file_put_contents($profile_image_upload_dir, base64_decode($profile_image));

                        createThumbnailImage($profile_image_upload_dir, $profile_image_name);

                        $update_query = "Update " . TABLE_USER . " set profilepic = ?, firstname = ?, lastname = ? where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                    } else {
                        $update_query = "Update " . TABLE_USER . " set firstname = ?, lastname = ? where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                    }

                    if ($updateStmt = $this->connection->prepare($update_query)) {
                        if (strlen($profile_image) > 0) {
                            $updateStmt->bind_param('sssss', $profile_image_name, $first_name, $last_name, $user_id, $is_testdata);
                        } else {
                            $updateStmt->bind_param('ssss', $first_name, $last_name, $user_id, $is_testdata);
                        }

                        if ($updateStmt->execute()) {
                            $updateStmt->close();
                            $errorMsg = "Profile updated successfully.";
                            $user_query = "Select u.id, u.firstname, u.lastname, u.email, u.password, u.follower_count, u.following_count, u.profilepic, u.is_private
                            from " . TABLE_USER . " as u  where u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                            if ($select_user_stmt = $this->connection->prepare($user_query)) {
                                $select_user_stmt->bind_param("ss", $user_id, $is_testdata);
                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();
                                if ($select_user_stmt->num_rows > 0) {
                                    while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                        $posts['User'] = $post;

                                    }
                                    $status = 1;

                                    //$data['User']['profile_pic'] = $profile_image_name;
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
                    $data['data'] = $posts;
                } else {
                    $data['status'] = FAILED;
                    $data['message'] = DEFAULT_NO_RECORD;
                }
                $select_user_exist_stmt->close();
            }

        }
        return $data;
    }

    public function checkAvailableUserName($userData)
    {
        $user_name = validateObject($userData, 'user_name', "");

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_make = validateObject($userData, 'device_type', 0);
        $device_make = addslashes($device_make);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($user_name == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {

            $select_user_exist_query = "Select id from " . TABLE_USER . "  where  username = ?  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_user_exist_stmt = $this->connection->prepare($select_user_exist_query)) {
                $select_user_exist_stmt->bind_param("ss", $user_name, $is_testdata);
                $select_user_exist_stmt->execute();
                $select_user_exist_stmt->store_result();

                if ($select_user_exist_stmt->num_rows > 0) {
                    $status = 2;
                    $errorMsg = "This username is already exist";
                } else {
                    $status = 1;
                    $errorMsg = "Success";
                }

                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
            } else {
                $data['status'] = FAILED;
                $data['message'] = DEV_ERROR;
            }
        }
        return $data;
    }

    private function ConversionList($post_data)
    {
        $user_id = validateObject($post_data, "user_id", 0);
        $user_id = addslashes($user_id);

        $status = FAILED;
        $message = DEV_ERROR;
        $posts = array();

        $my_query = "SELECT * FROM " . TABLE_CONVERSION . " WHERE (sender_id = ? OR receiver_id = ?) AND is_delete = '0' ORDER BY modified_date DESC";
        if ($my_stmt = $this->connection->prepare($my_query)) {
            $my_stmt->bind_param("ii", $user_id, $user_id);
            if ($my_stmt->execute()) {
                $my_stmt->store_result();
                if ($my_stmt->num_rows > 0) {
                    $status = SUCCESS;
                    $message = "listed successfull";
                    while ($conversion_arr = fetch_assoc_all_values($my_stmt)) {

                        $select_unread_counter_query = "SELECT id as message_id FROM " . TABLE_CHAT_MESSAGE . " WHERE is_read = 0  AND receiver_id = ? AND conversion_id = ? AND is_delete='0' ORDER BY id DESC";

                        if ($select_unread_counter_stmt = $this->connection->prepare($select_unread_counter_query)) {
                            $select_unread_counter_stmt->bind_param('ii', $user_id, $conversion_arr['id']);

                            $select_unread_counter_stmt->execute();
                            $select_unread_counter_stmt->store_result();

                            $conversion_arr['un_read_counter'] = $select_unread_counter_stmt->num_rows;
                        }

                        if ($user_id != $conversion_arr['sender_id']) {
                            $select_user_query = "SELECT firstname,lastname,profilepic FROM " . TABLE_USER . " AS u WHERE u.id = ? AND u.is_delete='0'";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param('i', $conversion_arr['sender_id']);

                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $conversion_arr['other_user_profile_pic'] = $user_post['profilepic'];
                                        $conversion_arr['other_user_first_name'] = $user_post['firstname'];
                                        $conversion_arr['other_user_last_name'] = $user_post['lastname'];
                                    }
                                }
                            }
                            $conversion_arr['user_id'] = (int) $user_id;
                            $conversion_arr['other_user_id'] = $conversion_arr['sender_id'];
                        } else {
                            $select_user_query = "SELECT firstname,lastname,profilepic FROM " . TABLE_USER . " AS u WHERE u.id = ? AND u.is_delete='0'";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param('i', $conversion_arr['receiver_id']);

                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $conversion_arr['other_user_profile_pic'] = $user_post['profilepic'];
                                        $conversion_arr['other_user_first_name'] = $user_post['firstname'];
                                        $conversion_arr['other_user_last_name'] = $user_post['lastname'];
                                    }
                                }
                            }
                            $conversion_arr['user_id'] = (int) $user_id;
                            $conversion_arr['other_user_id'] = $conversion_arr['receiver_id'];
                        }
                        $conversion_arr['conversion_id'] = $conversion_arr['id'];
                        unset($conversion_arr['id']);
                        $posts[] = $conversion_arr;
                    }
                } else {
                    $status = FAILED;
                    $message = "No records found";
                }
            }
        }

        $data[STATUS] = $status;
        $data[MESSAGE] = $message;
        $data[DATA] = $posts;
        return $data;
    }

    private function ChatMessageList($userData)
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
            $is_delete = 0;

            $select_chat_query = "SELECT id as message_id, conversion_id, sender_id, receiver_id, message_type, chat_message as message, created_date FROM " . TABLE_CHAT_MESSAGE . " AS cm WHERE ((cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.receiver_id = ? AND cm.sender_id = ?)) AND is_delete=" . $is_delete . " AND is_testdata = ?";

            if ($lastFeedId == 0) {
                $selectQuerySecondHalf = " ORDER BY id DESC LIMIT ?";
                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iiiiii", $userId, $otherUserId, $userId, $otherUserId, $is_testdata, $limit);
            } else {
                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
                    $selectQuerySecondHalf = " AND id < ? ORDER BY id DESC LIMIT ?";
                } else {
                    $selectQuerySecondHalf = " AND id > ? ORDER BY id DESC LIMIT ?";
                }

                $selectQuery = $select_chat_query . $selectQuerySecondHalf;
                $select_chat_stmt = $this->connection->prepare($selectQuery);
                $select_chat_stmt->bind_param("iiiiiii", $userId, $otherUserId, $userId, $otherUserId, $is_testdata, $lastFeedId, $limit);
            }

            if ($select_chat_stmt->execute()) {

                $select_chat_stmt->store_result();

                if ($select_chat_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($select_chat_stmt)) {
                        if ($post['message_type'] == FEED_MEDIA::IMAGE || $post['message_type'] == FEED_MEDIA::VIDEO) {
                            $post['message_id'];
                            $select_media_query = "SELECT id as media_id, media_name, media_type FROM " . TABLE_MEDIA . " WHERE post_id = ? AND is_deleted='" . $is_delete . "'";
                            $select_media_stmt = $this->connection->prepare($select_media_query);
                            $select_media_stmt->bind_param("i", $post['message_id']);
                            $select_media_stmt->execute();
                            $select_media_stmt->store_result();

                            if ($select_media_stmt->num_rows > 0) {
                                $mediaArr = array();
                                while ($media_arr = fetch_assoc_all_values($select_media_stmt)) {
                                    $mediaArr['media_id'] = $media_arr['media_id'];
                                    $mediaArr['chat_image'] = $media_arr['media_name'];
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
                } else {
                    $status = 2;
                    $errorMsg = "No records found";
                    $data['chat_list'] = $posts;
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

    public function isPrivate($userData)
    {
        $status = 2;

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $is_private = validateObject($userData, 'is_private', "");
        $is_private = addslashes($is_private);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        if ($user_id == "" || $is_private == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $update_query = "Update " . TABLE_USER . " set is_private = ? where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

            if ($updateStmt = $this->connection->prepare($update_query)) {
                $updateStmt->bind_param('iii', $is_private, $user_id, $is_testdata);

                if ($updateStmt->execute()) {
                    $updateStmt->close();

                    $status = 1;
                    $errorMsg = "Profile updated successfully";
                }
            } else {
                $status = 2;
                $errorMsg = "Update query " . $updateStmt->error;
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

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
          from " . TABLE_USER . " as u  where u.id = ?  and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {
                    $status = 1;
                    $user = fetch_assoc_all_values($select_stmt);

                    if ($self_user_id != 0) {
                        $select_following_query = "Select id,request_status FROM " . TABLE_USER_FOLLOWERS . "  WHERE sender_id = ? and receiver_id = ? and (request_status = 'ACCEPT' OR request_status = 'PENDING')  and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                        if ($select_following_stmt = $this->connection->prepare($select_following_query)) {
                            $select_following_stmt->bind_param("iii", $self_user_id, $user_id, $is_testdata);
                            $select_following_stmt->execute();
                            $select_following_stmt->store_result();

                            if ($select_following_stmt->num_rows > 0) {
                                while ($stmt_arr = fetch_assoc_all_values($select_following_stmt)) {

                                    if ($stmt_arr['request_status'] == 'PENDING') {
                                        $user['following_status'] = 3;
                                    } else {
                                        $user['following_status'] = 1;
                                    }
                                }
                            } else {
                                $user['following_status'] = 0;
                            }
                        }
                    } else {
                        $user['following_status'] = 2;
                    }

                    $posts['User'] = $user;
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
            $data['data'] = $posts;
        }
        return $data;
    }

    public function forgotPassword($userData)
    {

        $email_id = validateObject($userData, 'email_id', "");
        $email_id = addslashes($email_id);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        if ($email_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $appname = APPNAME;

            $select_user_query = "Select email from " . TABLE_USER . " where email = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                $select_user_stmt->bind_param("ss", $email_id, $is_testdata);
                $select_user_stmt->execute();
                $select_user_stmt->store_result();
                if ($select_user_stmt->num_rows > 0) {

                    $sendEmail = new SendEmail();
                    $randomString = generateRandomString(10);

                    $userPassword = $randomString;
                    $dbPassword = encryptPassword($userPassword);

                    while ($post = fetch_assoc_all_values($select_user_stmt)) {
                        $username = $post['email'];
                        $message = '<html><body>
                        <p>Hi,</p>
                        <p>Your new credentials for ' . $appname . ' account are:</br>
                        username: ' . $username . '</br>
                        password: ' . $userPassword . '</p>
                        <p>Regards,</br>
                        ' . $appname . ' Team</p>
                        </body></html>';

                        $result = $sendEmail->sendemail(SENDER_EMAIL_ID, $message, "Forgot Password", $email_id, $appname);
                        if ($result == 1) {
                            $update_query = "update " . TABLE_USER . " set password = ? where email = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                            if ($updateStmt = $this->connection->prepare($update_query)) {
                                $updateStmt->bind_param('sss', $dbPassword, $email_id, $is_testdata);
                                if ($updateStmt->execute()) {
                                    $updateStmt->close();
                                    $data['status'] = SUCCESS;
                                    $data['message'] = "Password sent successfully.";
                                    $data['data'] = $post;
                                } else {
                                    $data['status'] = FAILED;
                                    $data['message'] = "Server Error.Please try again.";
                                    $data['data'] = null;
                                }
                            } else {
                                $data['status'] = FAILED;
                                $data['message'] = "Server Error.Please try again.";
                                $data['data'] = null;
                            }

                        } else {
                            $data['status'] = FAILED;
                            $data['message'] = "SMTP Error.";
                            $data['data'] = null;
                        }
                    }
                } else {
                    $post['User'] = null;
                    $data['status'] = 'failed';
                    $data['message'] = "Email Id does not exist.";
                    $data['data'] = $post;
                }
                $select_user_stmt->close();
            }
        }
        return $data;
    }

    public function changePassword($userData)
    {
        $status = FAILED;
        $message = "";

        $user_id = validateObject($userData, 'user_id', "");
        $user_id = addslashes($user_id);

        $password = validateObject($userData, 'newpassword', "");

        $old_password = validateObject($userData, 'oldpassword', '');

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        if ($password == "" || $old_password == "" || $user_id == "") {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = DEV_ERROR;
        } else {
            $select_user_query = "Select id, password from " . TABLE_USER . " where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ? ";
            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                $select_user_stmt->bind_param("ss", $user_id, $is_testdata);
                $select_user_stmt->execute();
                $select_user_stmt->store_result();
                if ($select_user_stmt->num_rows > 0) {

                    $user_obj = fetch_assoc_stmt($select_user_stmt);
                    $db_password = $user_obj[0]['password'];
                    $encrypted_old_password = encryptPassword($old_password);
                    $encrypted_new_password = encryptPassword($password);
                    if ($encrypted_old_password == $encrypted_new_password) {
                        $status = FAILED;
                        $message = SAME_AS_OLD_PWD;
                    } else {
                        if ($encrypted_old_password == $db_password) {
                            $update_query = "update " . TABLE_USER . " set password = ? where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";
                            if ($updateStmt = $this->connection->prepare($update_query)) {
                                $updateStmt->bind_param('sss', $encrypted_new_password, $user_id, $is_testdata);
                                if ($updateStmt->execute()) {
                                    $updateStmt->close();
                                    $status = SUCCESS;
                                    $message = CHANGE_PASSWORD;
                                } else {
                                    $status = FAILED;
                                    $message = SERVER_ERROR;
                                }
                            }
                        } else {
                            $status = FAILED;
                            $message = INCORRECT_CURRENT_PWD;
                        }
                    }
                } else {
                    $status = FAILED;
                    $message = DEFAULT_NO_RECORDS;
                }
                $select_user_stmt->close();
            }
            $data[STATUS] = $status;
            $data[MESSAGE] = $message;
        }

        return $data;
    }

    public function loginWithFacebook($userData)
    {
        $connection = $this->connection;
        $status = 2;

        $role_id = "USER";

        $first_name = validateObject($userData, 'first_name', "");
        //$first_name = addslashes($first_name);

        $last_name = validateObject($userData, 'last_name', "");
        //$last_name = addslashes($last_name);

        $profile_image = validateObject($userData, 'profile_image', "");

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $facebook_id = validateObject($userData, 'facebook_id', "");
        //$facebook_id = addslashes($facebook_id);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $is_active = 1;

        $security = new SecurityFunctions($connection);
        $isSecure = $security->checkForSecurityNew($access_key, $secret_key, $device_type);
        //$isSecure=YES;
        if ($isSecure == NO) {
            $data['message'] = MALICIOUS_SOURCE;
            $data['status'] = FAILED;
        } else {
            if ($facebook_id == "") {
                $data['status'] = FAILED;
                $data['message'] = DEV_ERROR;
            } else {
                $posts = array();
                $errorMsg = "";

                $profile_image_name = "";

                $current_date = getDefaultDate();

                $select_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                from " . TABLE_USER . " as u where u.facebookid = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                if ($select_fbid_stmt = $this->connection->prepare($select_query)) {
                    $select_fbid_stmt->bind_param("ss", $facebook_id, $is_testdata);
                    $select_fbid_stmt->execute();
                    $select_fbid_stmt->store_result();
                    if ($select_fbid_stmt->num_rows > 0) {

                        //Update Null deviceToken If already Exist
                        //$this->checkAndUpdateNullDeviceToken($device_token);

                        $email_stmt = "";
//                        if ($userData->email_id) {
                        //                            $email_stmt = " email ='" . $userData->email_id . "', ";
                        //                        }
                        //$profile_image="";

                        $update_query = "Update " . TABLE_USER . " set
                            " . $email_stmt . "modified_date = ?
                            where facebookid = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

                        if ($updateStmt = $this->connection->prepare($update_query)) {

                            $updateStmt->bind_param('sss', $current_date, $facebook_id, $is_testdata);

                            if ($updateStmt->execute()) {
                                $updateStmt->close();
                                $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                from " . TABLE_USER . " as u where u.facebookid = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                    $select_user_stmt->bind_param("ss", $facebook_id, $is_testdata);
                                    $select_user_stmt->execute();
                                    $select_user_stmt->store_result();
                                    if ($select_user_stmt->num_rows > 0) {
                                        while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                            $get_guid = "";
                                            if ($post['guid'] == null || $post['guid'] == '') {
                                                $get_guid = updateGuidForUser($this->connection, $post['id'], $is_testdata);
                                            } else {
                                                $get_guid = $post['guid'];
                                            }

                                            //Update Token for user
                                            $tokenData = new stdClass;
                                            $tokenData->GUID = $get_guid;
                                            $tokenData->userId = $post['id'];
                                            $tokenData->deviceType = $device_type;
                                            $tokenData->deviceToken = $device_token;
                                            $user_token = $security->updateTokenforUser($tokenData);
                                            if ($user_token['status'] == SUCCESS) {
                                                $data['userToken'] = $user_token['userToken'];
                                            }
                                            //End Code for token
                                            $post['guid'] = $get_guid;

                                            $posts['User'] = $post;
                                        }
                                        $status = 1;
                                        $errorMsg = "Login Successfully.";

                                    }
                                    $select_user_stmt->close();
                                }
                            } else {
                                $status = 2;
                            }
                        }
                    } else {
                        $is_do_insert = '';
                        if (isset($userData->email_id)) {
                            $email_id = $userData->email_id;
                            $select_user_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                            from " . TABLE_USER . " as u where u.email = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param("ss", $email_id, $is_testdata);
                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {

                                    $get_user_details = fetch_assoc_all_values($select_user_stmt);
                                    //Update Null deviceToken If already Exist
                                    $this->checkAndUpdateNullDeviceToken($device_token);
                                    $user_id = $get_user_details['id'];

                                    $update_query = "Update " . TABLE_USER . " set facebookid = ?, modified_date =?
                                        where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

                                    if ($updateStmt = $this->connection->prepare($update_query)) {

                                        $updateStmt->bind_param('ssss', $facebook_id, $current_date, $user_id, $is_testdata);

                                        if ($updateStmt->execute()) {

                                            $updateStmt->close();
                                            $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                            from " . TABLE_USER . " as u where u.id = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                            if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                                $select_user_stmt->bind_param("ss", $user_id, $is_testdata);
                                                $select_user_stmt->execute();
                                                $select_user_stmt->store_result();
                                                if ($select_user_stmt->num_rows > 0) {
                                                    while ($post = fetch_assoc_all_values($select_user_stmt)) {

                                                        $get_guid = "";
                                                        if ($post['guid'] == null || $post['guid'] == '') {
                                                            $get_guid = updateGuidForUser($this->connection, $post['id'], $is_testdata);
                                                        } else {
                                                            $get_guid = $post['guid'];
                                                        }

                                                        //Update Token for user
                                                        $tokenData = new stdClass;
                                                        $tokenData->GUID = $get_guid;
                                                        $tokenData->userId = $post['id'];
                                                        $tokenData->deviceType = $device_type;
                                                        $tokenData->deviceToken = $device_token;
                                                        $user_token = $security->updateTokenforUser($tokenData);
                                                        if ($user_token['status'] == SUCCESS) {
                                                            $data['userToken'] = $user_token['userToken'];
                                                        }
                                                        //End Code for token

                                                        $post['guid'] = $get_guid;
                                                        $posts['User'] = $post;
                                                    }
                                                    $status = 1;
                                                    $errorMsg = "Login Successfully.";
                                                }
                                                $select_user_stmt->close();
                                            }
                                        } else {
                                            $status = 2;
                                        }
                                    } else {
                                        $status = 2;
                                        $errorMsg = "";
                                    }
                                } else {
                                    $is_do_insert = 1;
                                }
                            }

                        } else {
                            //Do insert
                            $is_do_insert = 1;
                        }

                        if ($is_do_insert == "1") {
                            $generate_guid = $security->gen_uuid();

                            //Update Null deviceToken If already Exist
                            //$this->checkAndUpdateNullDeviceToken($device_token);

                            $insertFields = "firstname, lastname, email, facebookid, is_delete ,is_testdata ,created_date, guid";
                            $is_delete = DELETE_STATUS::NOT_DELETE;

                            $insert_query = "Insert into " . TABLE_USER . " (" . $insertFields . ") values(?,?,?,?,?,?,?,?)";
                            if ($insertStmt = $this->connection->prepare($insert_query)) {
                                $insertStmt->bind_param('ssssssss', $first_name, $last_name, $userData->email_id, $facebook_id, $is_delete, $is_testdata, $current_date, $generate_guid);
                                if ($insertStmt->execute()) {
                                    $user_inserted_id = $insertStmt->insert_id;
                                    $insertStmt->close();

                                    // $insertFields1 = "id, is_testdata, created_date";
                                    // $insert_query1 = "Insert into " . TABLE_USER. " (" . $insertFields1 . ") values(?,?,?)";
                                    // if ($insertStmt1 = $this->connection->prepare($insert_query1)) {
                                    //     $insertStmt1->bind_param('sss', $user_inserted_id, $is_testdata, $getCurrentDate);
                                    //     if ($insertStmt1->execute()) {
                                    //     }
                                    // }

                                    // $insertFields = "user_id, is_testdata, created_date";
                                    // $insert_query = "Insert into " . TABLE_SETTING . " (" . $insertFields . ") values(?,?,?)";
                                    // if ($insertStmt = $this->connection->prepare($insert_query)) {
                                    //     $insertStmt->bind_param('sss', $user_inserted_id, $is_testdata, $getCurrentDate);
                                    //     if ($insertStmt->execute()) {
                                    //     }
                                    // }

                                    $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                    from " . TABLE_USER . " as u where u.id = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                    if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                        $select_user_stmt->bind_param("ss", $user_inserted_id, $is_testdata);
                                        $select_user_stmt->execute();
                                        $select_user_stmt->store_result();
                                        if ($select_user_stmt->num_rows > 0) {
                                            while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                                //Update Token for user
                                                $tokenData = new stdClass;
                                                $tokenData->GUID = $post['guid'];
                                                $tokenData->userId = $post['id'];
                                                $tokenData->deviceType = $device_type;
                                                $tokenData->deviceToken = $device_token;
                                                $user_token = $security->updateTokenforUser($tokenData);
                                                if ($user_token['status'] == SUCCESS) {
                                                    $data['userToken'] = $user_token['userToken'];
                                                }
                                                //End Code for token

                                                $posts['User'] = $post;
                                            }
                                            $status = 1; //success
                                            $errorMsg = "Login Successfully.";

                                        } else {
                                            $status = 2;
                                            $errorMsg = "Error select user query " . $select_user_stmt->error;
                                        }
                                        $select_user_stmt->close();
                                    }
                                } else {
                                    $status = 2;
                                    $errorMsg = "Error execute insert user query " . $insertStmt->error;
                                }
                            } else {
                                $status = 2;
                                $errorMsg = "Error prepare insert user query " . $this->connection->error;
                            }
                        }

                    }
                    $select_fbid_stmt->close();
                }

                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
                $data['data'] = $posts;
            }
        }

        return $data;
    }

    public function loginWithGoogle($userData)
    {
        $connection = $this->connection;
        $status = 2;

        $role_id = "USER";

        $first_name = validateObject($userData, 'first_name', "");
        //$first_name = addslashes($first_name);

        $last_name = validateObject($userData, 'last_name', "");
        //$last_name = addslashes($last_name);

        $profile_image = validateObject($userData, 'profile_image', "");

        $device_token = validateObject($userData, 'device_token', 0);
        $device_token = addslashes($device_token);

        $device_type = validateObject($userData, 'device_type', 0);
        $device_type = addslashes($device_type);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $google_id = validateObject($userData, 'google_id', "");
        //$facebook_id = addslashes($facebook_id);

        $secret_key = validateObject($userData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);

        $is_active = 1;

        $security = new SecurityFunctions($connection);
        $isSecure = $security->checkForSecurityNew($access_key, $secret_key, $device_type);
        //$isSecure=YES;
        if ($isSecure == NO) {
            $data['message'] = MALICIOUS_SOURCE;
            $data['status'] = FAILED;
        } else {
            if ($google_id == "") {
                $data['status'] = FAILED;
                $data['message'] = DEV_ERROR;
            } else {
                $posts = array();
                $errorMsg = "";

                $profile_image_name = "";

                $current_date = getDefaultDate();

                $select_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
              from " . TABLE_USER . " as u where u.googleid = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                if ($select_fbid_stmt = $this->connection->prepare($select_query)) {
                    $select_fbid_stmt->bind_param("ss", $google_id, $is_testdata);
                    $select_fbid_stmt->execute();
                    $select_fbid_stmt->store_result();
                    if ($select_fbid_stmt->num_rows > 0) {

                        //Update Null deviceToken If already Exist
                        //$this->checkAndUpdateNullDeviceToken($device_token);

                        $email_stmt = "";
//                        if ($userData->email_id) {
                        //                            $email_stmt = " email ='" . $userData->email_id . "', ";
                        //                        }
                        //$profile_image="";

                        $update_query = "Update " . TABLE_USER . " set
                            " . $email_stmt . "modified_date = ?
                            where googleid = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

                        if ($updateStmt = $this->connection->prepare($update_query)) {

                            $updateStmt->bind_param('sss', $current_date, $google_id, $is_testdata);

                            if ($updateStmt->execute()) {
                                $updateStmt->close();
                                $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                from " . TABLE_USER . " as u where u.googleid = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                    $select_user_stmt->bind_param("ss", $google_id, $is_testdata);
                                    $select_user_stmt->execute();
                                    $select_user_stmt->store_result();
                                    if ($select_user_stmt->num_rows > 0) {
                                        while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                            $get_guid = "";
                                            if ($post['guid'] == null || $post['guid'] == '') {
                                                $get_guid = updateGuidForUser($this->connection, $post['id'], $is_testdata);
                                            } else {
                                                $get_guid = $post['guid'];
                                            }

                                            //Update Token for user
                                            $tokenData = new stdClass;
                                            $tokenData->GUID = $get_guid;
                                            $tokenData->userId = $post['id'];
                                            $tokenData->deviceType = $device_type;
                                            $tokenData->deviceToken = $device_token;
                                            $user_token = $security->updateTokenforUser($tokenData);
                                            if ($user_token['status'] == SUCCESS) {
                                                $data['userToken'] = $user_token['userToken'];
                                            }
                                            //End Code for token
                                            $post['guid'] = $get_guid;

                                            $posts['User'] = $post;
                                        }
                                        $status = 1;
                                        $errorMsg = "Login Successfully.";

                                    }
                                    $select_user_stmt->close();
                                }
                            } else {
                                $status = 2;
                            }
                        }
                    } else {
                        $is_do_insert = '';
                        if (isset($userData->email_id)) {
                            $email_id = $userData->email_id;
                            $select_user_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                            from " . TABLE_USER . " as u where u.email = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->bind_param("ss", $email_id, $is_testdata);
                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {

                                    $get_user_details = fetch_assoc_all_values($select_user_stmt);
                                    //Update Null deviceToken If already Exist
                                    $this->checkAndUpdateNullDeviceToken($device_token);
                                    $user_id = $get_user_details['id'];

                                    $update_query = "Update " . TABLE_USER . " set googleid = ?, modified_date =?
                                        where id = ? and is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

                                    if ($updateStmt = $this->connection->prepare($update_query)) {

                                        $updateStmt->bind_param('ssss', $google_id, $current_date, $user_id, $is_testdata);

                                        if ($updateStmt->execute()) {

                                            $updateStmt->close();
                                            $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                            from " . TABLE_USER . " as u where u.id = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                            if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                                $select_user_stmt->bind_param("ss", $user_id, $is_testdata);
                                                $select_user_stmt->execute();
                                                $select_user_stmt->store_result();
                                                if ($select_user_stmt->num_rows > 0) {
                                                    while ($post = fetch_assoc_all_values($select_user_stmt)) {

                                                        $get_guid = "";
                                                        if ($post['guid'] == null || $post['guid'] == '') {
                                                            $get_guid = updateGuidForUser($this->connection, $post['id'], $is_testdata);
                                                        } else {
                                                            $get_guid = $post['guid'];
                                                        }

                                                        //Update Token for user
                                                        $tokenData = new stdClass;
                                                        $tokenData->GUID = $get_guid;
                                                        $tokenData->userId = $post['id'];
                                                        $tokenData->deviceType = $device_type;
                                                        $tokenData->deviceToken = $device_token;
                                                        $user_token = $security->updateTokenforUser($tokenData);
                                                        if ($user_token['status'] == SUCCESS) {
                                                            $data['userToken'] = $user_token['userToken'];
                                                        }
                                                        //End Code for token

                                                        $post['guid'] = $get_guid;
                                                        $posts['User'] = $post;
                                                    }
                                                    $status = 1;
                                                    $errorMsg = "Login Successfully.";
                                                }
                                                $select_user_stmt->close();
                                            }
                                        } else {
                                            $status = 2;
                                        }
                                    } else {
                                        $status = 2;
                                        $errorMsg = "";
                                    }
                                } else {
                                    $is_do_insert = 1;
                                }
                            }

                        } else {
                            //Do insert
                            $is_do_insert = 1;
                        }

                        if ($is_do_insert == "1") {
                            $generate_guid = $security->gen_uuid();

                            //Update Null deviceToken If already Exist
                            //$this->checkAndUpdateNullDeviceToken($device_token);

                            $insertFields = "firstname, lastname, email, googleid, is_delete ,is_testdata ,created_date, guid";
                            $is_delete = DELETE_STATUS::NOT_DELETE;

                            $insert_query = "Insert into " . TABLE_USER . " (" . $insertFields . ") values(?,?,?,?,?,?,?,?)";
                            if ($insertStmt = $this->connection->prepare($insert_query)) {
                                $insertStmt->bind_param('ssssssss', $first_name, $last_name, $userData->email_id, $google_id, $is_delete, $is_testdata, $current_date, $generate_guid);
                                if ($insertStmt->execute()) {
                                    $user_inserted_id = $insertStmt->insert_id;
                                    $insertStmt->close();

                                    // $insertFields1 = "id, is_testdata, created_date";
                                    // $insert_query1 = "Insert into " . TABLE_USER. " (" . $insertFields1 . ") values(?,?,?)";
                                    // if ($insertStmt1 = $this->connection->prepare($insert_query1)) {
                                    //     $insertStmt1->bind_param('sss', $user_inserted_id, $is_testdata, $getCurrentDate);
                                    //     if ($insertStmt1->execute()) {
                                    //     }
                                    // }

                                    // $insertFields = "user_id, is_testdata, created_date";
                                    // $insert_query = "Insert into " . TABLE_SETTING . " (" . $insertFields . ") values(?,?,?)";
                                    // if ($insertStmt = $this->connection->prepare($insert_query)) {
                                    //     $insertStmt->bind_param('sss', $user_inserted_id, $is_testdata, $getCurrentDate);
                                    //     if ($insertStmt->execute()) {
                                    //     }
                                    // }

                                    $select_fb_user = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                                    from " . TABLE_USER . " as u where u.id = ? and u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
                                    if ($select_user_stmt = $this->connection->prepare($select_fb_user)) {
                                        $select_user_stmt->bind_param("ss", $user_inserted_id, $is_testdata);
                                        $select_user_stmt->execute();
                                        $select_user_stmt->store_result();
                                        if ($select_user_stmt->num_rows > 0) {
                                            while ($post = fetch_assoc_all_values($select_user_stmt)) {
                                                //Update Token for user
                                                $tokenData = new stdClass;
                                                $tokenData->GUID = $post['guid'];
                                                $tokenData->userId = $post['id'];
                                                $tokenData->deviceType = $device_type;
                                                $tokenData->deviceToken = $device_token;
                                                $user_token = $security->updateTokenforUser($tokenData);
                                                if ($user_token['status'] == SUCCESS) {
                                                    $data['userToken'] = $user_token['userToken'];
                                                }
                                                //End Code for token

                                                $posts['User'] = $post;
                                            }
                                            $status = 1; //success
                                            $errorMsg = "Login Successfully.";

                                        } else {
                                            $status = 2;
                                            $errorMsg = "Error select user query " . $select_user_stmt->error;
                                        }
                                        $select_user_stmt->close();
                                    }
                                } else {
                                    $status = 2;
                                    $errorMsg = "Error execute insert user query " . $insertStmt->error;
                                }
                            } else {
                                $status = 2;
                                $errorMsg = "Error prepare insert user query " . $this->connection->error;
                            }
                        }

                    }
                    $select_fbid_stmt->close();
                }

                $data['status'] = ($status > 1) ? FAILED : SUCCESS;
                $data['message'] = $errorMsg;
                $data['data'] = $posts;
            }
        }

        return $data;
    }

    public function getTemplates($userData)
    {
        $connection = $this->connection;
        $posts = array();

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $is_testdata = addslashes($is_testdata);

        $status = 2;
        $errorMsg = "";

        $select_query = "Select id as template_id, name from " . TABLE_TEMPLATE . " where  is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND is_testdata = ?";

        if ($select_stmt = $connection->prepare($select_query)) {
            $select_stmt->bind_param("i", $is_testdata);
            $select_stmt->execute();
            $select_stmt->store_result();
            if ($select_stmt->num_rows > 0) {
                $status = 1;
                $errorMsg = "Listing successfully";
                while ($post = fetch_assoc_all_values($select_stmt)) {
                    $post['template_path'] = URL_TEMPLATE_IMAGE;
                    $posts['Templates'][] = $post;
                }
            } else {
                $status = 2;
                $errorMsg = "No records found";
            }
        }

        $data['status'] = ($status > 1) ? FAILED : SUCCESS;
        $data['message'] = $errorMsg;
        $data['data'] = $posts;

        return $data;
    }

    public function unLinkImageFolder($folder, $image_name)
    {
        $uploadDir = $folder . $image_name;
        if (!unlink($uploadDir)) {
            //echo ("Error deleting ");
        } else {
            //echo ("Deleted ");
        }
    }

    public function resizeImage($SrcImage, $DestImage, $MaxWidth, $MaxHeight, $Quality)
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

    public function uploadUserProfile($profile_image_name, $base64_image)
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

    public function checkAndUpdateNullDeviceToken($device_token)
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

    public function uploadMediasToFeed(&$isQuerySuccess, $keyMedia)
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

                    $thumbImgName = null;

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

    public function getMediaPrefixLocalPathBasedOnType($mediaType)
    {

        switch ($mediaType) {
            case FEED_MEDIA::IMAGE:{
                    return SERVER_CHAT_IMAGES_PATH;
                }
            case FEED_MEDIA::VIDEO:{
                    return SERVER_CHAT_VIDEOS_PATH;
                }
            default:{
                    return "";
                }
        }
    }

}
