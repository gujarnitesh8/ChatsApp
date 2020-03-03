<?php

//require_once('class.phpmailer.php');
include_once 'FCM.php';
include_once 'HelperFunctions.php';
include_once 'TableVars.php';
include_once 'SendEmail.php';

class ProfileModule
{
    protected $connection;

    function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {

            case "FollowUnfollowUser": {
                return $this->followUnfollowUser($postData);
            }
                break;
            case "ShowFollowers": {
                return $this->showFollowers($postData);
            }
                break;

            case "ShowFollowing": {
                return $this->showFollowingUsers($postData);
            }
                break;

            case "SearchUsers": {
                return $this->searchUsers($postData);
            }
                break;

            case "ShowAllUser": {
                return $this->showAllUser($postData);
            }
                break;
            case "ViewProfile": {
                return $this->viewProfile($postData);
            }
                break;

            case "NotificationList": {
                return $this->notificationList($postData);
            }
                break;

            /*Not used*/

            case "ShowFollowersFollowing": {
                return $this->showFollowersFollowing($postData);
            }
                break;

            case "AcceptRejectRequest": {
                return $this->acceptRejectRequest($postData);
            }
                break;

            case "PendingRequest": {
                return $this->pendingRequest($postData);
            }
                break;

            case "BlockUnBlockUser": {
                return $this->blockUnBlockUser($postData);
            }
                break;
            case "BlocklistUser": {
                return $this->blocklistUser($postData);
            }
                break;

            case "IsOPenNotification": {
                return $this->isOPenNotification($postData);
            }
                break;

            case "FollowerUserListing": {
                return $this->followerUserListing($postData);
            }
                break;
        }
        return null;
    }

    public function searchUsers($userData)
    {
        $status = 2;

        $posts = array();

        $limit = LIMIT_OFFSET_CONTACT;

        $user_id = validateObject($userData, 'user_id', "");

        $search_txt = validateObject($userData, 'serach_txt', "");

        $offset = validateObject($userData, 'offset', 0);

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

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
            $deleteStatus = DELETE_STATUS::NOT_DELETE;

            $errorMsg = "";
            $select_query = "Select
            u.id as user_id, 
            u.firstname,
            u.lastname,
            u.post_count, 
            u.username,
            u.follower_count, 
            u.following_count,
            u.profilepic,u.description as user_description
            from " . TABLE_USER . " AS u
           /* LEFT JOIN " . TABLE_USER_BLOCKED . " AS bu ON(u.id = bu.other_user_id AND bu.user_id = ?)*/
            where u.id != ? AND u.firstname LIKE CONCAT('%',?,'%') OR u.lastname LIKE CONCAT('%',?,'%') OR CONCAT( u.firstname,  ' ', u.lastname ) LIKE CONCAT('%',?,'%') AND u.is_delete='" . $deleteStatus . "' AND u.is_testdata = ?/* AND bu.id IS NULL*/ LIMIT 50";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("isssi", $user_id, $search_txt, $search_txt, $search_txt, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {

                    while ($user_arr = fetch_assoc_all_values($select_stmt)) {

                        $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) AND (request_status = 'ACCEPT' OR request_status = 'PENDING') AND is_delete = " . $deleteStatus . " AND is_testdata = " . $is_testdata;

                        $select_stmt_following = $this->connection->prepare($select_following);

                        $select_stmt_following->bind_param("iiii", $user_id, $user_arr['user_id'], $user_arr['user_id'], $user_id);

                        if ($select_stmt_following->execute()) {
                            $select_stmt_following->store_result();
                            if ($select_stmt_following->num_rows > 0) {
                                while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {

                                    if ($stmt_arr['request_status'] == 'PENDING') {
                                        $user_arr['following_status'] = 3;
                                    } else {
                                        $user_arr['following_status'] = 1;
                                    }
                                }

                            } else {
                                $user_arr['following_status'] = 0;
                            }
                        }
                        if ($user_arr['user_id'] == $user_id) {
                            $user_arr['following_status'] = 2;
                        }

                        $posts['user_listing'][] = $user_arr;
                        $errorMsg = "User listing successfully.";
                        $status = 1;
                    }

                } else {
                    $errorMsg = "User not found.";
                    $status = 2;
                    $posts['user_listing'] = array();
                }
            } else {
                $errorMsg = "Something wrong with select query 1.";
                $status = 2;
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
            $data['data'] = $posts;
        }
        return $data;
    }







    public function followUnfollowUser($userData)
    {
        $status = 2;

        $user_id = validateObject($userData, 'self_user_id', "");

        $other_user_id = validateObject($userData, 'other_user_id', "");

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($user_id == "" || $other_user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            /*============== Un Follow query ================*/

            $select_unfollow_query = "SELECT * FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id = ? AND receiver_id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?"; //AND (request_status = 'ACCEPT' OR request_status = 'PENDING')

            if ($select_unfollow_stmt = $this->connection->prepare($select_unfollow_query)) {
                $select_unfollow_stmt->bind_param("iii", $user_id, $other_user_id, $is_testdata);
                $select_unfollow_stmt->execute();
                $select_unfollow_stmt->store_result();

                if ($select_unfollow_stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($select_unfollow_stmt)) {
                        if ($val['request_status'] == 'ACCEPT' || $val['request_status'] == 'PENDING') {

                            $update_query = "UPDATE " . TABLE_USER_FOLLOWERS . " SET request_status = 'REJECT' WHERE sender_id = ? AND receiver_id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";
                            if ($update_stmt = $this->connection->prepare($update_query)) {

                                $update_stmt->bind_param('iii', $user_id, $other_user_id, $is_testdata);
                                $update_stmt->execute();

                                if ($val['request_status'] == 'ACCEPT') {
                                    $notificationType = NOTIFICATION_TYPE_FOLLOW;
                                } else {
                                    $notificationType = NOTIFICATION_TYPE_REQUEST_PENDING;
                                }

                                $arrayNotification = array();

                                $notificationObj['sender_id'] = $user_id;
                                $notificationObj['receiver_id'] = $other_user_id;
                                $notificationObj['notification_type_id'] = $val['id'];
                                $notificationObj['notification_type'] = $notificationType;
                                $notificationObj['is_testdata'] = $is_testdata;
                                // $notificationObj['notification_text'] = $notificationData;

                                $arrayNotification[] = $notificationObj;
                                removeEntryInNotificationTable($this->connection, $arrayNotification);

                                $selectQuery = "" . "SELECT u.firstname, u.lastname, u.id as user_id, u.profilepic,u.description as user_description, u.post_count, u.follower_count, u.following_count
                                    FROM " . TABLE_USER_FOLLOWERS . " uf," . TABLE_USER . " u
                                    WHERE uf.receiver_id = '" . $other_user_id . "' AND uf.sender_id = '" . $user_id . "' AND request_status = 'REJECT'
                                    AND u.id = uf.receiver_id AND uf.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND uf.is_testdata = ? AND u.is_testdata = ?";

                                if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
                                    $select_query_stmt->bind_param("ii", $is_testdata, $is_testdata);
                                    $select_query_stmt->execute();
                                    $select_query_stmt->store_result();
                                }

                                if ($select_query_stmt->num_rows > 0) {
                                    while ($val = fetch_assoc_all_values($select_query_stmt)) {

                                        $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                                        $select_stmt_following = $this->connection->prepare($select_following);

                                        $select_stmt_following->bind_param("ii", $user_id, $val['user_id']);

                                        if ($select_stmt_following->execute()) {
                                            $select_stmt_following->store_result();
                                            if ($select_stmt_following->num_rows > 0) {
                                                while ($val_stmt = fetch_assoc_all_values($select_stmt_following)) {
                                                    if ($val_stmt['request_status'] == 'PENDING') {
                                                        $val['following_status'] = 3;
                                                    } else {
                                                        $val['following_status'] = 1;
                                                    }
                                                }
                                            } else {
                                                $val['following_status'] = 0;
                                            }
                                        }
                                        if ($val['user_id'] == $user_id) {
                                            $val['following_status'] = 2;
                                        }

                                        $data['data']['user_listing'][] = $val;
                                    }
                                }


                                $data['status'] = SUCCESS;
                                $data['message'] = "UnFollow user successfully.";
                                return $data;
                            } else {
                                $data['status'] = FAILED;
                                $data['message'] = "Something wrong with update query.";
                                return $data;
                            }
                        } else {

                            /*============== Follow user profile privte or public ================*/
                            $request_status = "ACCEPT";
                            $user_arr = array();
                            $select_query = "SELECT follower_count, is_private FROM " . TABLE_USER . " WHERE id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

                            if ($select_stmt = $this->connection->prepare($select_query)) {
                                $select_stmt->bind_param("ii", $other_user_id, $is_testdata);
                                $select_stmt->execute();
                                $select_stmt->store_result();

                                if ($select_stmt->num_rows > 0) {
                                    $user_arr = fetch_assoc_all_values($select_stmt);
                                    if ($user_arr['is_private'] == 1) {
                                        $request_status = 'PENDING';
                                    } else {
                                        $request_status = 'ACCEPT';
                                    }
                                } else {
                                    $data['status'] = FAILED;
                                    $data['message'] = "No user found.";
                                    return $data;
                                }
                            } else {
                                $data['status'] = FAILED;
                                $data['message'] = "Something wrong with query 1.";
                                return $data;
                            }

                            $update_query = "UPDATE " . TABLE_USER_FOLLOWERS . " SET request_status = ? WHERE sender_id = ? AND receiver_id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";
                            if ($update_stmt = $this->connection->prepare($update_query)) {
                                $update_stmt->bind_param('siii', $request_status, $user_id, $other_user_id, $is_testdata);
                                $update_stmt->execute();

                                $status = 1;

                                $arrayNotification = array(
                                    array(
                                        'sender_id' => $user_id,
                                        'receiver_id' => $other_user_id,
                                        'notification_type_id' => $val['id'],
                                        'is_testdata' => $is_testdata
                                    )
                                );

                                if ($request_status == 'ACCEPT') {

                                    $data['data']['user_account'] = 'PUBLIC';

                                    $arrayNotification[0]['notification_type'] = NOTIFICATION_TYPE_FOLLOW;
                                    addEntryInNotificationTable($this->connection, $arrayNotification);
                                    //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                                    $select_query1 = "SELECT * FROM ".TABLE_USER." WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                                    $select_stmt1 = $this->connection->prepare($select_query1);
                                    $select_stmt1->bind_param("is", $user_id, $is_testdata);
                                    if($select_stmt1->execute()) {
                                        $select_stmt1->store_result();
                                        $val = fetch_assoc_all_values($select_stmt1);
                                        $select_query2 = "SELECT * FROM ".TABLE_APP_TOKENS." WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                                        $select_stmt2 = $this->connection->prepare($select_query2);
                                        $select_stmt2->bind_param('is',$other_user_id,$is_testdata);
                                        if ($select_stmt2->execute()) {
                                            $select_stmt2->store_result();
                                            if ($select_stmt2->num_rows() > 0) {

                                                $getUserArr = fetch_assoc_all_values($select_stmt2);
                                                $dataNotiArr['sender_firstname'] = $val['firstname'];
                                                $dataNotiArr['sender_lastname'] = $val['lastname'];
                                                $dataNotiArr['notification_type'] = NOTIFICATION_TYPE_FOLLOW;
                                                $dataNotiArr['created_date'] = '';
                                                $dataNotiArr['userId'] = $other_user_id;
                                                $extraArr['title'] = "";
                                                $extraArr['body'] = $val['firstname'].' '.$val['lastname'].' started following you';

                                                if($getUserArr['device_token'] != ""){
                                                    $fcm = new FCM();
                                                    $fcm->send_gcm_notify($getUserArr['device_token'],false,$dataNotiArr,$extraArr);
                                                }
                                            }
                                        }

                                    }
                                    //<<<<<<<<----------- push notification code end ------------>>>>>>>>

                                    $errorMsg = "Follow user successfully";
                                } else {
                                    $data['data']['user_account'] = 'PRIVATE';

                                    $arrayNotification[0]['notification_type'] = NOTIFICATION_TYPE_REQUEST_PENDING;
                                    addEntryInNotificationTable($this->connection, $arrayNotification);

                                    $errorMsg = "Request sent successfully";
                                    //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                                    $select_query1 = "SELECT * FROM ".TABLE_USER." WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                                    $select_stmt1 = $this->connection->prepare($select_query1);
                                    $select_stmt1->bind_param("is", $user_id, $is_testdata);
                                    if($select_stmt1->execute()) {
                                        $select_stmt1->store_result();
                                        $val = fetch_assoc_all_values($select_stmt1);

                                        $select_query2 = "SELECT * FROM ".TABLE_APP_TOKENS." WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                                        $select_stmt2 = $this->connection->prepare($select_query2);
                                        $select_stmt2->bind_param('is',$other_user_id,$is_testdata);
                                        if ($select_stmt2->execute()) {
                                            $select_stmt2->store_result();
                                            if ($select_stmt2->num_rows() > 0) {
                                                $getUserArr = fetch_assoc_all_values($select_stmt2);
                                                $dataNotiArr['sender_firstname'] = $val['firstname'];
                                                $dataNotiArr['sender_lastname'] = $val['lastname'];
                                                $dataNotiArr['notification_type'] = NOTIFICATION_TYPE_REQUEST_PENDING;
                                                $dataNotiArr['created_date'] = '';
                                                $dataNotiArr['userId'] = $other_user_id;
                                                $extraArr['title'] = "";
                                                $extraArr['body'] = $val['firstname'].' '.$val['lastname'].' has sent you following request';

                                                if($getUserArr['device_token'] != ""){
                                                    $fcm = new FCM();
                                                    $fcm->send_gcm_notify($getUserArr['device_token'],false,$dataNotiArr,$extraArr);
                                                }
                                            }
                                        }

                                    }
                                    //<<<<<<<<----------- push notification code end ------------>>>>>>>>
                                }
                            } else {
                                $status = 2;
                                $errorMsg = "Something wrong with update query.";
                            }
                        }
                    }
                } else {
                    /*============== Follow user profile privte or public ================*/
                    $request_status = "ACCEPT";
                    $select_query = "SELECT is_private FROM " . TABLE_USER . " WHERE id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

                    if ($select_stmt = $this->connection->prepare($select_query)) {
                        $select_stmt->bind_param("ii", $other_user_id, $is_testdata);
                        $select_stmt->execute();
                        $select_stmt->store_result();

                        if ($select_stmt->num_rows > 0) {
                            $val = fetch_assoc_all_values($select_stmt);
                            if ($val['is_private'] == 1) {
                                $request_status = 'PENDING';
                            } else {
                                $request_status = 'ACCEPT';
                            }
                        } else {
                            $data['status'] = FAILED;
                            $data['message'] = "No user found.";
                            return $data;
                        }
                    } else {
                        $data['status'] = FAILED;
                        $data['message'] = "Something wrong with query 1.";
                        return $data;
                    }

                    /*============== Follow query ================*/

                    $insertFields1 = " sender_id, receiver_id, request_status, is_delete ,is_testdata, created_date";

                    $getCurrentDate = getDefaultDate();
                    $insert_query1 = "Insert into " . TABLE_USER_FOLLOWERS . " (" . $insertFields1 . ") values(?,?,?,?,?,?)";

                    if ($insertStmt1 = $this->connection->prepare($insert_query1)) {

                        $insertStmt1->bind_param('ssssss', $user_id, $other_user_id, $request_status, $is_delete, $is_testdata, $getCurrentDate);
                        if ($insertStmt1->execute()) {
                            $user_inserted_id = $insertStmt1->insert_id;
                            $status = 1;

                            $arrayNotification = array(
                                array(
                                    'sender_id' => $user_id,
                                    'receiver_id' => $other_user_id,
                                    'notification_type_id' => $user_inserted_id,
                                    'is_testdata' => $is_testdata
                                )
                            );

                            if ($request_status == 'ACCEPT') {
                                $data['data']['user_account'] = 'PUBLIC';

                                $arrayNotification[0]['notification_type'] = NOTIFICATION_TYPE_FOLLOW;
                                addEntryInNotificationTable($this->connection, $arrayNotification);
                                //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                                $select_query1 = "SELECT * FROM ".TABLE_USER." WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                                $select_stmt1 = $this->connection->prepare($select_query1);
                                $select_stmt1->bind_param("is", $user_id, $is_testdata);
                                if($select_stmt1->execute()) {
                                    $select_stmt1->store_result();
                                    $val = fetch_assoc_all_values($select_stmt1);

                                    $select_query2 = "SELECT * FROM ".TABLE_APP_TOKENS." WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                                    $select_stmt2 = $this->connection->prepare($select_query2);
                                    $select_stmt2->bind_param('is',$other_user_id,$is_testdata);
                                    if ($select_stmt2->execute()) {
                                        $select_stmt2->store_result();
                                        if ($select_stmt2->num_rows() > 0) {
                                            $getUserArr = fetch_assoc_all_values($select_stmt2);
                                            $dataNotiArr['sender_firstname'] = $val['firstname'];
                                            $dataNotiArr['sender_lastname'] = $val['lastname'];
                                            $dataNotiArr['notification_type'] = NOTIFICATION_TYPE_FOLLOW;
                                            $dataNotiArr['created_date'] = '';
                                            $dataNotiArr['userId'] = $other_user_id;
                                            $extraArr['title'] = "";
                                            $extraArr['body'] = $val['firstname'].' '.$val['lastname'].' started following you';

                                            if($getUserArr['device_token'] != ""){
                                                $fcm = new FCM();
                                                $fcm->send_gcm_notify($getUserArr['device_token'],false,$dataNotiArr,$extraArr);
                                            }
                                        }
                                    }

                                }
                                //<<<<<<<<----------- push notification code end ------------>>>>>>>>
                                $errorMsg = "Follow user successfully";
                            } else {
                                $data['data']['user_account'] = 'PRIVATE';

                                $arrayNotification[0]['notification_type'] = NOTIFICATION_TYPE_REQUEST_PENDING;
                                addEntryInNotificationTable($this->connection, $arrayNotification);

                                $errorMsg = "Request sent successfully";
                                //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                                $select_query1 = "SELECT * FROM ".TABLE_USER." WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                                $select_stmt1 = $this->connection->prepare($select_query1);
                                $select_stmt1->bind_param("is", $user_id, $is_testdata);
                                if($select_stmt1->execute()) {
                                    $select_stmt1->store_result();
                                    $val = fetch_assoc_all_values($select_stmt1);

                                    $select_query2 = "SELECT * FROM ".TABLE_APP_TOKENS." WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                                    $select_stmt2 = $this->connection->prepare($select_query2);
                                    $select_stmt2->bind_param('is',$other_user_id,$is_testdata);
                                    if ($select_stmt2->execute()) {
                                        $select_stmt2->store_result();
                                        if ($select_stmt2->num_rows() > 0) {
                                            $getUserArr = fetch_assoc_all_values($select_stmt2);
                                            $dataNotiArr['sender_firstname'] = $val['firstname'];
                                            $dataNotiArr['sender_lastname'] = $val['lastname'];
                                            $dataNotiArr['notification_type'] = NOTIFICATION_TYPE_REQUEST_PENDING;
                                            $dataNotiArr['created_date'] = '';
                                            $dataNotiArr['userId'] = $other_user_id;
                                            $extraArr['title'] = "";
                                            $extraArr['body'] = $val['firstname'].' '.$val['lastname'].' has sent you following request';

                                            if($getUserArr['device_token'] != ""){
                                                $fcm = new FCM();
                                                $fcm->send_gcm_notify($getUserArr['device_token'],false,$dataNotiArr,$extraArr);
                                            }
                                        }
                                    }

                                }
                                //<<<<<<<<----------- push notification code end ------------>>>>>>>>
                            }

                        } else {
                            $status = 2;
                            $errorMsg = "Failed to register users." . $insertStmt1->error;
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Failed to register users." . $this->connection->error;
                    }
                }
            } else {
                $data['status'] = FAILED;
                $data['message'] = "Something wrong with query.";
                return $data;
            }

            $selectQuery = "" . "SELECT u.firstname, u.lastname, u.id as user_id, u.profilepic,u.description as user_description, u.post_count, u.follower_count, u.following_count
                FROM " . TABLE_USER_FOLLOWERS . " uf," . TABLE_USER . " u
                WHERE uf.receiver_id = '" . $other_user_id . "' AND uf.sender_id = '" . $user_id . "' AND (request_status = 'ACCEPT' OR request_status = 'PENDING') AND u.id = uf.receiver_id AND uf.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND uf.is_testdata = ? AND u.is_testdata = ?";

            if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
                $select_query_stmt->bind_param("ii", $is_testdata, $is_testdata);
                $select_query_stmt->execute();
                $select_query_stmt->store_result();
            }

            if ($select_query_stmt->num_rows > 0) {
                while ($val = fetch_assoc_all_values($select_query_stmt)) {

                    $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                    $select_stmt_following = $this->connection->prepare($select_following);

                    $select_stmt_following->bind_param("ii", $user_id, $val['user_id']);

                    if ($select_stmt_following->execute()) {
                        $select_stmt_following->store_result();
                        if ($select_stmt_following->num_rows > 0) {
                            while ($val_stmt = fetch_assoc_all_values($select_stmt_following)) {
                                if ($val_stmt['request_status'] == 'PENDING') {
                                    $val['following_status'] = 3;
                                } else {
                                    $val['following_status'] = 1;
                                }
                            }

                        } else {
                            $val['following_status'] = 0;
                        }
                    }
                    if ($val['user_id'] == $user_id) {
                        $val['following_status'] = 2;
                    }

                    $data['data']['user_listing'][] = $val;
                }
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function acceptRejectRequest($userData)
    {
        $status = 2;

        $user_id = validateObject($userData, 'self_user_id', "");

        $other_user_id = validateObject($userData, 'other_user_id', "");

        $request_status = validateObject($userData, 'request_status', "");

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($user_id == "" || $other_user_id == "" || $request_status == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_unfollow_query = "SELECT * FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id = ? AND receiver_id = ? AND request_status = 'PENDING' AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

            if ($select_unfollow_stmt = $this->connection->prepare($select_unfollow_query)) {
                $select_unfollow_stmt->bind_param("iii", $other_user_id, $user_id, $is_testdata);
                $select_unfollow_stmt->execute();
                $select_unfollow_stmt->store_result();

                if ($select_unfollow_stmt->num_rows > 0) {
                    $update_query = "UPDATE " . TABLE_USER_FOLLOWERS . " SET request_status = ? WHERE sender_id = ? AND receiver_id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

                    if ($update_stmt = $this->connection->prepare($update_query)) {
                        $update_stmt->bind_param('siii', $request_status, $other_user_id, $user_id, $is_testdata);
                        $update_stmt->execute();

                        $status = 1;

                        if ($request_status == 'ACCEPT') {

                            $data['data']['following_status'] = 1;
                            $errorMsg = "Request accepted";
                        } else {
                            $data['data']['following_status'] = 0;
                            $errorMsg = "Request rejected";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something wrong with update query.";
                    }
                }
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function pendingRequest($userData)
    {
        $status = 2;
        $posts = array();

        $user_id = validateObject($userData, 'user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            //$select_pending_query = "SELECT u.firstname, u.lastname,u.tag_name, u.id as user_id, u.profilepic, u.post_count, u.follower_count, u.following_count,uf.created_date FROM ".TABLE_USER_FOLLOWERS." uf,".TABLE_USER." u WHERE uf.receiver_id = ? AND uf.request_status = 'PENDING' AND uf.is_delete = '".$is_delete."' AND uf.is_testdata = ? AND u.is_delete = '".$is_delete."' AND u.is_testdata = ? ORDER BY created_date DESC";

            $select_pending_query = "SELECT u.firstname, u.lastname,u.tag_name, u.id as user_id, u.profilepic, u.post_count, u.follower_count, u.following_count,uf.created_date,u.description as user_description FROM " . TABLE_USER_FOLLOWERS . " AS uf LEFT JOIN " . TABLE_USER . " AS u ON uf.sender_id=u.id WHERE uf.receiver_id = ? AND uf.request_status = 'PENDING' AND uf.is_delete = '" . $is_delete . "' AND uf.is_testdata = ? AND u.is_delete = '" . $is_delete . "' AND u.is_testdata = ? ORDER BY created_date DESC";

            if ($select_pending_stmt = $this->connection->prepare($select_pending_query)) {
                $select_pending_stmt->bind_param("iii", $user_id, $is_testdata, $is_testdata);
                $select_pending_stmt->execute();
                $select_pending_stmt->store_result();

                if ($select_pending_stmt->num_rows > 0) {
                    $status = 1;
                    $errorMsg = "Listed successfully";
                    while ($val = fetch_assoc_all_values($select_pending_stmt)) {
                        $val['following_status'] = 3;
                        $posts['pending_request'][] = $val;
                    }
                } else {
                    $status = 2;
                    $errorMsg = "no records found";
                    $posts['pending_request'] = array();
                }
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
            $data['data'] = $posts;
        }

        return $data;
    }

    public function showFollowersFollowing($userData)
    {
        $message = "Show Followers Following";
        $status = SUCCESS;

        $allDetails = array();

        $show_followers = validateObject($userData, 'show_followers', "");

        if ($show_followers == "1") {
            $allDetails = $this->showFollowers($userData);
        } else {
            $allDetails = $this->showFollowingUsers($userData);
        }

        return $allDetails;
    }

    public function showFollowingUsers($userData)
    {

        $message = "Show Following Users";
        $status = SUCCESS;

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $allDetails = array();
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = validateObject($userData, 'offset', 0);
        $self_user_id = validateObject($userData, 'self_user_id', "");
        $other_user_id = validateObject($userData, 'other_user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($other_user_id == "") {
            $other_user_id = $self_user_id;
        }

        $offset = $offset * $limit;

        $selectQuery = "" . "SELECT u.firstname, u.lastname, u.id as user_id, u.profilepic,u.description as user_description, u.post_count, u.follower_count, u.following_count
                    FROM " . TABLE_USER_FOLLOWERS . " uf," . TABLE_USER . " u
                    WHERE uf.sender_id = '" . $other_user_id . "'
                    AND u.id = uf.receiver_id AND (request_status = 'ACCEPT' OR  request_status = 'PENDING') AND uf.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND uf.is_testdata = ? AND u.is_testdata = ? LIMIT " . $limit . " OFFSET " . $offset . "";

        if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
            $select_query_stmt->bind_param("ii", $is_testdata, $is_testdata);
            $select_query_stmt->execute();
            $select_query_stmt->store_result();
        }

        $allDetails['user_listing'] = array();

        if ($select_query_stmt->num_rows > 0) {
            while ($val = fetch_assoc_all_values($select_query_stmt)) {

                $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                $select_stmt_following = $this->connection->prepare($select_following);

                $select_stmt_following->bind_param("ii", $self_user_id, $val['user_id']);

                if ($select_stmt_following->execute()) {
                    $select_stmt_following->store_result();
                    if ($select_stmt_following->num_rows > 0) {
                        while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
                            if ($stmt_arr['request_status'] == 'PENDING') {
                                $val['following_status'] = 3;
                            } else {
                                $val['following_status'] = 1;
                            }
                        }

                    } else {
                        $val['following_status'] = 0;
                    }
                }
                if ($val['user_id'] == $self_user_id) {
                    $val['following_status'] = 2;
                }

                $allDetails['user_listing'][] = $val;
            }
        } else {
            $allDetails['user_listing'] = array();
        }

        $data['status'] = $status;
        $data['message'] = $message;
        $data['data'] = $allDetails;
        $data['load_more'] = count($allDetails['user_listing']) == $limit;
        return $data;
    }

    public function showFollowers($userData)
    {

        $message = "Show Followers Users";
        $status = SUCCESS;

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $allDetails = array();
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = validateObject($userData, 'offset', 0);
        $self_user_id = validateObject($userData, 'self_user_id', "");
        $other_user_id = validateObject($userData, 'other_user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($other_user_id == "") {
            $other_user_id = $self_user_id;
        }

        $offset = $offset * $limit;

        $selectQuery = "" . "SELECT u.firstname, u.lastname, u.id as user_id, u.profilepic,u.description as user_description, u.post_count, u.follower_count, u.following_count
                    FROM " . TABLE_USER_FOLLOWERS . " uf," . TABLE_USER . " u
                    WHERE uf.receiver_id = '" . $other_user_id . "' AND request_status = 'ACCEPT'
                    AND u.id = uf.sender_id AND uf.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND uf.is_testdata = ? AND u.is_testdata = ? LIMIT " . $limit . " OFFSET " . $offset . "";

        if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
            $select_query_stmt->bind_param("ii", $is_testdata, $is_testdata);
            $select_query_stmt->execute();
            $select_query_stmt->store_result();
        }
        $allDetails['user_listing'] = array();

        if ($select_query_stmt->num_rows > 0) {
            while ($val = fetch_assoc_all_values($select_query_stmt)) {
                //$val['relation_status'] = $this->getRelation($self_user_id,$val['user_id']);

                $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                $select_stmt_following = $this->connection->prepare($select_following);

                $select_stmt_following->bind_param("ii", $self_user_id, $val['user_id']);

                if ($select_stmt_following->execute()) {
                    $select_stmt_following->store_result();
                    if ($select_stmt_following->num_rows > 0) {
                        while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
                            if ($stmt_arr['request_status'] == "PENDING") {
                                $val['following_status'] = 3;
                            } else {
                                $val['following_status'] = 1;
                            }
                        }

                    } else {
                        $val['following_status'] = 0;
                    }
                }
                if ($val['user_id'] == $self_user_id) {
                    $val['following_status'] = 2;
                }

                $allDetails['user_listing'][] = $val;

            }
        } else {
            $allDetails['user_listing'] = array();
        }

        $data['status'] = $status;
        $data['message'] = $message;
        $data['data'] = $allDetails;
        $data['load_more'] = count($allDetails['user_listing']) == $limit;
        return $data;
    }

    public function showAllUser($userData)
    {
        $message = "Show Followers Users";
        $status = SUCCESS;

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $allDetails = array();
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = validateObject($userData, 'offset', 0);
        $self_user_id = validateObject($userData, 'self_user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $offset = $offset * $limit;

        $selectQuery = "
        SELECT
        id as user_id,
        u.firstname,
        u.lastname,
        u.profilepic,u.description as user_description,
        u.post_count,
        u.follower_count,
        u.following_count 
        FROM " . TABLE_USER . " AS u WHERE u.id != ? AND u.is_delete = '" . $is_delete . "' AND u.is_testdata = ?
        ORDER BY u.firstname ASC LIMIT " . $limit . " OFFSET " . $offset . "";

        if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
            $select_query_stmt->bind_param("is", $self_user_id, $is_testdata);
            $select_query_stmt->execute();
            $select_query_stmt->store_result();
        }
        $allDetails['user_listing'] = array();
        if ($select_query_stmt->num_rows > 0) {
            while ($val = fetch_assoc_all_values($select_query_stmt)) {

                $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                $select_stmt_following = $this->connection->prepare($select_following);

                $select_stmt_following->bind_param("ii", $self_user_id, $val['user_id']);

                if ($select_stmt_following->execute()) {
                    $select_stmt_following->store_result();
                    if ($select_stmt_following->num_rows > 0) {
                        while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
                            if ($stmt_arr['request_status'] == "PENDING") {
                                $val['following_status'] = 3;
                            } else {
                                $val['following_status'] = 1;
                            }
                        }

                    } else {
                        $val['following_status'] = 0;
                    }
                }
                if ($val['user_id'] == $self_user_id) {
                    $val['following_status'] = 2;
                }


                $allDetails['user_listing'][] = $val;
            }
        }

        $data['status'] = $status;
        $data['message'] = $message;
        $data['data'] = $allDetails;
        $data['load_more'] = count($allDetails['user_listing']) == $limit;
        return $data;
    }

    //For Post

//    public function viewProfile($userData)
//    {
//
//        $message = "View Profile";
//        $status = SUCCESS;
//
//        $self_user_id = validateObject($userData, 'self_user_id', "");
//        $other_user_id = validateObject($userData, 'other_user_id', "");
//        $offset = validateObject($userData, 'offset', 0);
//
//        $loadingType = validateObject($userData, 'loading_type', 0);//check for load more or not
//        $lastFeedId = validateObject($userData, 'last_post_id', 0);
//        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
//
//        $limit = LIMIT_RANDOM_FEED_LOAD;
//
//        if ($self_user_id == '') {
//            $data['status'] = FAILED;
//            $data['message'] = DEV_ERROR;
//        } else {
//            $deleteStatus = DELETE_STATUS::NOT_DELETE;
//            $errorMsg = "";
//
//            $offset = $offset * $limit;
//
//
//            if ($other_user_id == '') {
//                $other_user_id = $self_user_id;
//            }
//
//
//            $selectQuery = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
//                        u.possition as user_possition, u.profilepic,u.description as user_description,u.firstname, u.lastname,
//                        IF(tr.id IS NULL,0,1) as is_rate,
//                        IF(tl.id IS NULL,0,1) as is_like
//                        FROM " . TABLE_CHALLENGE . " f
//                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
//                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
//                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
//                        LEFT JOIN " . TABLE_USER . " as u ON p.user_id = u.id
//                        WHERE /*f.category_id=? AND*/ f.is_delete=? AND f.is_testdata=? AND f.user_id=? GROUP BY f.id  ORDER BY f.id LIMIT ? OFFSET ?";
//
//
//
//            $selectQueryFirstHalf = "SELECT p.id as post_id,p.description,p.like_count,p.category_id,p.comment_count,u.possition as user_possition, u.profilepic,u.description as user_description, p.user_id,u.firstname, u.lastname,p.created_date
//                                    FROM " . TABLE_POST . " as p
//                                    LEFT JOIN " . TABLE_USER . " as u ON p.user_id = u.id
//                                    WHERE p.user_id = ? AND p.is_delete = " . $deleteStatus . " AND u.is_delete = " . $deleteStatus . " AND p.is_testdata = ? AND u.is_testdata = ?";
//
//            if ($lastFeedId == 0) {
//                $selectQuerySecondHalf = " ORDER BY p.id DESC LIMIT ?";
//                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
//                $stmt = $this->connection->prepare($selectQuery);
//                $stmt->bind_param("iiii", $other_user_id, $is_testdata, $is_testdata, $limit);
//            } else {
//                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
//                    $selectQuerySecondHalf = " AND p.id < ? ORDER BY p.id DESC LIMIT ?";
//                } else {
//                    $selectQuerySecondHalf = " AND p.id > ? ORDER BY p.id DESC LIMIT ?";
//                }
//
//                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
//                $stmt = $this->connection->prepare($selectQuery);
//                $stmt->bind_param("iiiii", $other_user_id, $is_testdata, $is_testdata, $lastFeedId, $limit);
//            }
//            $post = array();
//
//            if ($stmt->execute()) {
//                $stmt->store_result();
//                while ($val = fetch_assoc_all_values($stmt)) {
//
//                    $val['profilepic'] = ($val['profilepic'] ? $val['profilepic'] : "");
//
//                    $select_media = "SELECT id,media_type,media_name FROM " . TABLE_MEDIA . " where post_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
//                    $stmt_media = $this->connection->prepare($select_media);
//                    $stmt_media->bind_param("i", $val['post_id']);
//                    if ($stmt_media->execute()) {
//                        $stmt_media->store_result();
//                        $i = 0;
//                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
//                            $val['post_media'][$i]['media_id'] = $val_media['id'];
//                            $val['post_media'][$i]['media_url'] = $val_media['media_name'];
//                            $val['post_media'][$i]['media_type'] = $val_media['media_type'];
//                            $i++;
//                        }
//                    }
//
//
//                    $comment_query = "SELECT pc.id as comment_id,user_id, post_id, comment_message,pc.created_date, u.firstname,u.lastname,u.description as user_description,possition as user_possition, FROM " . TABLE_COMMENTS . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.post_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";
//
//                    if ($comment_stmt = $this->connection->prepare($comment_query)) {
//                        $comment_stmt->bind_param("is", $val['post_id'], $is_testdata);
//                        $comment_stmt->execute();
//                        $comment_stmt->store_result();
//
//                        if ($comment_stmt->num_rows > 0) {
//                            while ($val2 = fetch_assoc_all_values($comment_stmt)) {
//                                $val['comment'][] = $val2;
//                            }
//                        } else {
//
//                        }
//                    }
//
//                    $select_like = "SELECT id FROM " . TABLE_LIKES . " where post_id= ? AND user_id = ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
//                    $stmt_like = $this->connection->prepare($select_like);
//                    $stmt_like->bind_param("ii", $val['post_id'], $other_user_id);
//                    if ($stmt_like->execute()) {
//                        $stmt_like->store_result();
//                        if ($stmt_like->num_rows > 0) {
//                            $val['like_status'] = 1;
//                        } else {
//                            $val['like_status'] = 0;
//                        }
//                    }
//
//                    $post[] = $val;
//                }
//                $stmt->close();
//
//                $user_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.possition as user_possition, u.profilepic, u.is_private
//                              from " . TABLE_USER . " as u
//                              where u.id = ?  AND u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";
//
//                if ($select_user_stmt = $this->connection->prepare($user_query)) {
//                    $select_user_stmt->bind_param("ss", $other_user_id, $is_testdata);
//                    $select_user_stmt->execute();
//                    $select_user_stmt->store_result();
//                    if ($select_user_stmt->num_rows > 0) {
//                        while ($user = fetch_assoc_all_values($select_user_stmt)) {
//
//                            $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . DELETE_STATUS::NOT_DELETE . " AND is_testdata = " . $is_testdata;
//
//                            $select_stmt_following = $this->connection->prepare($select_following);
//
//                            $select_stmt_following->bind_param("ii", $self_user_id, $user['id']);
//
//                            if ($select_stmt_following->execute()) {
//                                $select_stmt_following->store_result();
//                                if ($select_stmt_following->num_rows > 0) {
//                                    while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
//                                        if ($stmt_arr['request_status'] == "PENDING") {
//                                            $user['following_status'] = 3;
//                                        } else {
//                                            $user['following_status'] = 1;
//                                        }
//                                    }
//
//                                } else {
//                                    $user['following_status'] = 0;
//                                }
//                            }
//                            if ($user['id'] == $self_user_id) {
//                                $user['following_status'] = 2;
//                            }
//
//                            $data['data']['User'] = $user;
//                        }
//                    }
//                    $select_user_stmt->close();
//                }
//
//            }
//
//            $data[STATUS] = SUCCESS;
//            $data[MESSAGE] = "Successfully fetched feeds.";
//            $data['data']['posts'] = $post;
//        }
//
//        return $data;
//    }


//For Challenge
    public function viewProfile($userData)
    {

        $message = "View Profile";
        $status = SUCCESS;

        $self_user_id = validateObject($userData, 'self_user_id', "");
        $other_user_id = validateObject($userData, 'other_user_id', "");
        $offset = validateObject($userData, 'offset', 0);

        $loadingType = validateObject($userData, 'loading_type', 0);//check for load more or not
        $lastChallengeId = validateObject($userData, 'last_challenge_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $limit = LIMIT_RANDOM_FEED_LOAD;

        if ($self_user_id == '') {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $deleteStatus = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $offset = $offset * $limit;


            if ($other_user_id == '') {
                $other_user_id = $self_user_id;
            }


            $selectQueryFirstHalf = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        u.possition as user_possition, u.profilepic,u.description as user_description,u.firstname, u.lastname,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        LEFT JOIN " . TABLE_USER . " as u ON f.user_id = u.id
                        WHERE /*f.category_id=? AND*/ f.is_delete=".DELETE_STATUS::NOT_DELETE." AND f.is_testdata=? AND f.user_id=? ";



            if ($lastChallengeId == 0) {
                $selectQuerySecondHalf = " ORDER BY f.id DESC LIMIT ?";
                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
                $stmt = $this->connection->prepare($selectQuery);
                $stmt->bind_param("iii", $is_testdata,$other_user_id, $limit);
            } else {
                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
                    $selectQuerySecondHalf = " AND f.id < ? ORDER BY f.id DESC LIMIT ?";
                } else {
                    $selectQuerySecondHalf = " AND f.id > ? ORDER BY f.id DESC LIMIT ?";
                }

                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
                $stmt = $this->connection->prepare($selectQuery);
                $stmt->bind_param("iiii", $is_testdata,$other_user_id , $lastChallengeId, $limit);
            }
            $post = array();

            if ($stmt->execute()) {
                $stmt->store_result();
                while ($val = fetch_assoc_all_values($stmt)) {

                    $val['profilepic'] = ($val['profilepic'] ? $val['profilepic'] : "");

                    $select_media = "SELECT id,media_type,media_name FROM " . TABLE_MEDIA . " where challenge_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
                    $stmt_media = $this->connection->prepare($select_media);
                    $stmt_media->bind_param("i", $val['challenge_id']);
                    if ($stmt_media->execute()) {
                        $stmt_media->store_result();
                        $i = 0;
                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
                            $val['post_media'][$i]['media_id'] = $val_media['id'];
                            $val['post_media'][$i]['media_url'] = $val_media['media_name'];
                            $val['post_media'][$i]['media_type'] = $val_media['media_type'];
                            $i++;
                        }
                    }


//                    $comment_query = "SELECT pc.id as comment_id,user_id, post_id, comment_message,pc.created_date, u.firstname,u.lastname,u.description as user_description,possition as user_possition, FROM " . TABLE_COMMENTS . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.challenge_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";
//
//                    if ($comment_stmt = $this->connection->prepare($comment_query)) {
//                        $comment_stmt->bind_param("is", $val['post_id'], $is_testdata);
//                        $comment_stmt->execute();
//                        $comment_stmt->store_result();
//
//                        if ($comment_stmt->num_rows > 0) {
//                            while ($val2 = fetch_assoc_all_values($comment_stmt)) {
//                                $val['comment'][] = $val2;
//                            }
//                        } else {
//
//                        }
//                    }
//
//                    $select_like = "SELECT id FROM " . TABLE_LIKES . " where post_id= ? AND user_id = ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
//                    $stmt_like = $this->connection->prepare($select_like);
//                    $stmt_like->bind_param("ii", $val['post_id'], $other_user_id);
//                    if ($stmt_like->execute()) {
//                        $stmt_like->store_result();
//                        if ($stmt_like->num_rows > 0) {
//                            $val['like_status'] = 1;
//                        } else {
//                            $val['like_status'] = 0;
//                        }
//                    }

                    $post[] = $val;
                }
                $stmt->close();

                $user_query = "Select u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.possition as user_possition, u.profilepic, u.is_private
                              from " . TABLE_USER . " as u
                              where u.id = ?  AND u.is_delete='" . DELETE_STATUS::NOT_DELETE . "' AND u.is_testdata = ?";

                if ($select_user_stmt = $this->connection->prepare($user_query)) {
                    $select_user_stmt->bind_param("ss", $other_user_id, $is_testdata);
                    $select_user_stmt->execute();
                    $select_user_stmt->store_result();
                    if ($select_user_stmt->num_rows > 0) {
                        while ($user = fetch_assoc_all_values($select_user_stmt)) {

                            $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . DELETE_STATUS::NOT_DELETE . " AND is_testdata = " . $is_testdata;

                            $select_stmt_following = $this->connection->prepare($select_following);

                            $select_stmt_following->bind_param("ii", $self_user_id, $user['id']);

                            if ($select_stmt_following->execute()) {
                                $select_stmt_following->store_result();
                                if ($select_stmt_following->num_rows > 0) {
                                    while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
                                        if ($stmt_arr['request_status'] == "PENDING") {
                                            $user['following_status'] = 3;
                                        } else {
                                            $user['following_status'] = 1;
                                        }
                                    }

                                } else {
                                    $user['following_status'] = 0;
                                }
                            }
                            if ($user['id'] == $self_user_id) {
                                $user['following_status'] = 2;
                            }

                            $data['data']['User'] = $user;
                        }
                    }
                    $select_user_stmt->close();
                }

            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data['data']['challenges'] = $post;
        }

        return $data;
    }

    public function blockUnBlockUser($userData)
    {
        $status = 2;

        $user_id = validateObject($userData, 'self_user_id', "");

        $other_user_id = validateObject($userData, 'other_user_id', "");

        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $flag = 0;

        if ($user_id == "" || $other_user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $errorMsg = "";
            $is_delete = DELETE_STATUS::NOT_DELETE;

            $select_unfollow_query = "SELECT * FROM " . TABLE_USER_BLOCKED . " WHERE user_id = ? AND other_user_id = ? AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

            if ($select_unfollow_stmt = $this->connection->prepare($select_unfollow_query)) {

                $select_unfollow_stmt->bind_param("iii", $user_id, $other_user_id, $is_testdata);
                $select_unfollow_stmt->execute();
                $select_unfollow_stmt->store_result();

                if ($select_unfollow_stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($select_unfollow_stmt)) {

                        $delete_query = "DELETE FROM " . TABLE_USER_BLOCKED . " WHERE (user_id = ? AND other_user_id = ? OR other_user_id = ? AND user_id = ?) AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

                        if ($delete_stmt = $this->connection->prepare($delete_query)) {

                            $delete_stmt->bind_param('iiiii', $user_id, $other_user_id, $other_user_id, $user_id, $is_testdata);
                            $delete_stmt->execute();

                            $selectQuery = "" . "SELECT firstname,lastname,id as user_id, profilepic, post_count, follower_count, following_count,description as user_description
                                    FROM " . TABLE_USER . "
                                    WHERE id = ? AND is_delete=" . $is_delete . " AND is_testdata = ?";

                            if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
                                $select_query_stmt->bind_param("ii", $other_user_id, $is_testdata);
                                $select_query_stmt->execute();
                                $select_query_stmt->store_result();

                                if ($select_query_stmt->num_rows > 0) {
                                    while ($val = fetch_assoc_all_values($select_query_stmt)) {

                                        $select_following = "SELECT sender_id FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                                        $select_stmt_following = $this->connection->prepare($select_following);

                                        $select_stmt_following->bind_param("ii", $user_id, $val['user_id']);

                                        if ($select_stmt_following->execute()) {
                                            $select_stmt_following->store_result();
                                            if ($select_stmt_following->num_rows > 0) {
                                                $val['following_status'] = 1;
                                            } else {
                                                $val['following_status'] = 0;
                                            }
                                        }
                                        if ($val['user_id'] == $user_id) {
                                            $val['following_status'] = 2;
                                        }

                                        $data['data']['blockuser_listing'][] = $val;
                                    }
                                }

                                $data['status'] = SUCCESS;
                                $data['message'] = "UnBlock user successfully.";
                                return $data;
                            }
                        }
                    }
                } else {
                    $request_status = "REJECT";

                    $insertFields1 = " user_id, other_user_id ,is_testdata, created_date";

                    $getCurrentDate = getDefaultDate();
                    $insert_query1 = "Insert into " . TABLE_USER_BLOCKED . " (" . $insertFields1 . ") values(?,?,?,?)";

                    if ($insertStmt1 = $this->connection->prepare($insert_query1)) {

                        $insertStmt1->bind_param('iiis', $user_id, $other_user_id, $is_testdata, $getCurrentDate);
                        if ($insertStmt1->execute()) {

                            $update_query = "UPDATE " . TABLE_USER_FOLLOWERS . " SET request_status = 'REJECT' WHERE (sender_id = ? AND receiver_id = ?) OR (receiver_id = ? AND sender_id = ?) AND is_delete = '" . $is_delete . "' AND is_testdata = ?";

                            if ($update_stmt = $this->connection->prepare($update_query)) {
                                $update_stmt->bind_param('iiiii', $user_id, $other_user_id, $other_user_id, $user_id, $is_testdata);
                                $update_stmt->execute();
                            }


                            $user_inserted_id = $insertStmt1->insert_id;
                            $status = 1;
                            $flag = 1;
                            $errorMsg = "Block user successfully";
                        } else {
                            $status = 2;
                            $errorMsg = "Failed to register users." . $insertStmt1->error;
                        }

                    } else {
                        $status = 2;
                        $errorMsg = "Failed to register users." . $this->connection->error;
                    }
                }
            } else {

            }

            $selectQuery = "" . "SELECT firstname, lastname, id as user_id, profilepic, post_count, follower_count, following_count,description as user_description
                FROM " . TABLE_USER . "
                WHERE id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";

            if ($select_user_stmt = $this->connection->prepare($selectQuery)) {
                $select_user_stmt->bind_param("ii", $other_user_id, $is_testdata);
                $select_user_stmt->execute();
                $select_user_stmt->store_result();
            }

            if ($select_user_stmt->num_rows > 0) {
                while ($val = fetch_assoc_all_values($select_user_stmt)) {

                    if ($flag == 1) {
                        $val['following_status'] = 2;
                    }

                    $data['data']['blockuser_listing'][] = $val;
                }
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
        }

        return $data;
    }

    public function blocklistUser($userData)
    {
        $message = "Show Block Users";
        $status = SUCCESS;

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $allDetails = array();
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = validateObject($userData, 'offset', 0);
        $user_id = validateObject($userData, 'user_id', "");
        //$other_user_id = validateObject($userData , 'other_user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        // if($other_user_id == ""){
        //     $other_user_id = $self_user_id;
        // }

        $offset = $offset * $limit;

        $selectQuery = "" . "SELECT u.firstname, u.lastname, u.id as user_id, u.profilepic, u.post_count, u.follower_count, u.following_count,u.description as user_description
                    FROM " . TABLE_USER_BLOCKED . " ub," . TABLE_USER . " u
                    WHERE ub.user_id = '" . $user_id . "'
                    AND u.id = ub.other_user_id AND ub.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND ub.is_testdata = ? AND u.is_testdata = ? LIMIT " . $limit . " OFFSET " . $offset . "";

        if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
            $select_query_stmt->bind_param("ii", $is_testdata, $is_testdata);
            $select_query_stmt->execute();
            $select_query_stmt->store_result();
        }

        if ($select_query_stmt->num_rows > 0) {
            while ($val = fetch_assoc_all_values($select_query_stmt)) {
                //$val['relation_status'] = $this->getRelation($self_user_id,$val['user_id']);

                $allDetails['blockuser_listing'][] = $val;

            }
        } else {
            $allDetails['blockuser_listing'] = array();
        }

        $data['status'] = $status;
        $data['message'] = $message;
        $data['data'] = $allDetails;
        $data['load_more'] = count($allDetails['blockuser_listing']) == $limit;
        return $data;
    }

//    public function notificationList($userData)
//    {
//
//        $userId = validateObject($userData, 'user_id', 0);
//        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
//
//        $offset = validateObject($userData, 'offset', 0);
//
//        $limit = LIMIT_OFFSET_CONTACT;
//
//        $offset = $offset * $limit;
//
//        if ($userId == 0) {
//            $data[MESSAGE] = DEV_ERROR;
//            $data[STATUS] = FAILED;
//        } else {
//            $deleteStatus = DELETE_STATUS::NOT_DELETE;
//            $status = 0;
//            $errorMsg = '';
//
//            $select_query = "SELECT id as notification_id,sender_id,notification_type_id,notification_type,created_date FROM " . TABLE_NOTIFICATION . " WHERE receiver_id = ? AND is_delete = " . $deleteStatus . "  AND is_testdata = ? ORDER BY id DESC LIMIT " . $limit . " OFFSET " . $offset . "";
//
//            if ($select_stmt = $this->connection->prepare($select_query)) {
//                $select_stmt->bind_param("ii", $userId, $is_testdata);
//                if ($select_stmt->execute()) {
//                    $select_stmt->store_result();
//
//                    if ($select_stmt->num_rows > 0) {
//                        $allDetails = array();
//                        while ($notification_arr = fetch_assoc_all_values($select_stmt)) {
//                            $notification_arr['is_testdata'] = $is_testdata;
//                            $allDetails[] = notificationMsgList($this->connection, $notification_arr);
//                        }
//                        $status = 1;
//                        $errorMsg = "Notification listed successfully";
//                        $data['data']['notification_listing'] = $allDetails;
//
//
////                        $update_query = "UPDATE " . TABLE_NOTIFICATION . " SET is_read = 1 WHERE received_by = ? AND is_delete = " . $deleteStatus . " AND is_testdata = ?";
////                        if ($update_stmt = $this->connection->prepare($update_query)) {
////                            $update_stmt->bind_param("ss", $userId, $is_testdata);
////                            $update_stmt->execute();
////                        }
//
//                    } else {
//                        $data['data']['notification_listing'] = array();
//                        $status = 2;
//                        $errorMsg = "No notification found.";
//                    }
//                }
//            } else {
//                $status = 2;
//                $errorMsg = "Something went wrong in select query.";
//            }
//            $data[STATUS] = ($status > 1) ? FAILED : SUCCESS;
//            $data[MESSAGE] = $errorMsg;
//        }
//
//        return $data;
//    }

    public function notificationList($userData)
    {

        $userId = validateObject($userData, 'user_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($userData, 'offset', 0);
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = $offset * $limit;

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
        } else {
            $deleteStatus = DELETE_STATUS::NOT_DELETE;
            $status = 0;
            $errorMsg = '';

//            $selectQueryFirstHalf = "SELECT id as notification_id,sender_id,notification_type_id,notification_type,created_date FROM " . TABLE_NOTIFICATION . " WHERE receiver_id = ? AND is_delete = " . $deleteStatus . "  AND is_testdata = ?";
            $selectQueryFirstHalf = "SELECT id as notification_id,sender_id,notification_type_id,notification_type,created_date FROM " . TABLE_NOTIFICATION . " WHERE receiver_id = ? AND is_delete = " . $deleteStatus . "  AND is_testdata = ? ORDER BY id DESC LIMIT ? OFFSET ? ";


            $selectQuerySecondHalf = "  ORDER BY id DESC LIMIT ? OFFSET ?";

            $select_query = $selectQueryFirstHalf;
            if ($select_stmt = $this->connection->prepare($select_query)) {
//                $select_stmt->bind_param("ii", $userId, $is_testdata);
                $select_stmt->bind_param("iiii", $userId, $is_testdata, $limit, $offset);
                if ($select_stmt->execute()) {
                    $select_stmt->store_result();

                    if ($select_stmt->num_rows > 0) {
                        $allDetails = array();
                        while ($notification_arr = fetch_assoc_all_values($select_stmt)) {
                            $notification_arr['is_testdata'] = $is_testdata;
                            $allDetails[] = notificationMsgList($this->connection, $notification_arr);
                        }
                        $status = 1;
                        $errorMsg = "Notification listed successfully";
                        $data['data']['notification_listing'] = $allDetails;


//                        $update_query = "UPDATE " . TABLE_NOTIFICATION . " SET is_read = 1 WHERE received_by = ? AND is_delete = " . $deleteStatus . " AND is_testdata = ?";
//                        if ($update_stmt = $this->connection->prepare($update_query)) {
//                            $update_stmt->bind_param("ss", $userId, $is_testdata);
//                            $update_stmt->execute();
//                        }

                    } else {
                        $data['data']['notification_listing'] = array();
                        $status = 2;
                        $errorMsg = "No notification found.";
                    }
                }
            } else {
                $status = 2;
                $errorMsg = "Something went wrong in select query.";
            }

            $data[STATUS] = ($status > 1) ? FAILED : SUCCESS;
            $data[MESSAGE] = $errorMsg;
            $data['Limit'] = $limit;
            $data['Offset'] = $offset;
            $data['load_more'] = count($data['data']['notification_listing']) == $limit;
        }

        return $data;
    }


    public function isOPenNotification($userData)
    {
        $userId = validateObject($userData, 'user_id', 0);
        $notifyId = validateObject($userData, 'notification_id', 0);
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);
        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        $errorMsg = "";
        $status = 2;

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
        } else {

            if ($notifyId == 0) {
                $update_query = "UPDATE " . TABLE_NOTIFICATION . " SET is_open = 1 WHERE received_by = ? AND is_delete = " . $deleteStatus . " AND is_testdata = ?";
                if ($update_stmt = $this->connection->prepare($update_query)) {
                    $update_stmt->bind_param("ii", $userId, $is_testdata);
                    $update_stmt->execute();

                    $status = 1;
                    $errorMsg = "Notification open successfully";
                } else {
                    $status = 2;
                    $errorMsg = "Something went wrong on update query";
                }
            } else {
                $update_query = "UPDATE " . TABLE_NOTIFICATION . " SET is_open = 1 WHERE notification_id = ? AND is_delete = " . $deleteStatus . " AND is_testdata = ?";
                if ($update_stmt = $this->connection->prepare($update_query)) {
                    $update_stmt->bind_param("ii", $notifyId, $is_testdata);
                    $update_stmt->execute();

                    $status = 1;
                    $errorMsg = "Notification open successfully";
                } else {
                    $status = 2;
                    $errorMsg = "Something went wrong on update query";
                }
            }

            $data[STATUS] = ($status > 1) ? FAILED : SUCCESS;
            $data[MESSAGE] = $errorMsg;
        }
        return $data;
    }

    public function followerUserListing($userData)
    {

        $message = "Show Follower Users";
        $status = SUCCESS;

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $allDetails = array();
        $limit = LIMIT_OFFSET_CONTACT;

        $offset = validateObject($userData, 'offset', 0);
        $self_user_id = validateObject($userData, 'self_user_id', "");
        $search_user = validateObject($userData, 'search_user', "");
        $other_user_id = validateObject($userData, 'other_user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($other_user_id == "") {
            $other_user_id = $self_user_id;
        }

        $offset = $offset * $limit;

        $where = '';
        if ($search_user != '') {
            $where .= "AND (u.firstname LIKE CONCAT('%','" . $search_user . "','%') OR u.lastname LIKE CONCAT('%','" . $search_user . "','%') OR CONCAT( u.firstname,  ' ', u.lastname ) LIKE CONCAT('%','" . $search_user . "','%'))";
        }

        $selectQuery = "" . "SELECT u.firstname, u.lastname, u.user_id, m.media_name as profilepic, u.post_count, u.follower_count, u.following_count,u.description as user_description
                    FROM " . TABLE_USER_FOLLOWERS . " uf," . TABLE_USER . " u
                    LEFT JOIN " . TABLE_MEDIA . " as m ON u.user_id = m.post_id
                    WHERE uf.receiver_id = '" . $other_user_id . "'
                    AND u.user_id = uf.sender_id AND (request_status = 'ACCEPT' OR  request_status = 'PENDING') AND m.post_type = 1 AND uf.is_delete='" . $is_delete . "' AND u.is_delete='" . $is_delete . "' AND m.is_delete='" . $is_delete . "' AND uf.is_testdata = ? AND u.is_testdata = ? AND m.is_testdata = ? " . $where . " ORDER BY u.firstname LIMIT " . $limit . " OFFSET " . $offset . "";

        if ($select_query_stmt = $this->connection->prepare($selectQuery)) {
            $select_query_stmt->bind_param("iii", $is_testdata, $is_testdata, $is_testdata);
            $select_query_stmt->execute();
            $select_query_stmt->store_result();
        }
        if ($select_query_stmt->num_rows > 0) {
            while ($val = fetch_assoc_all_values($select_query_stmt)) {

                $select_following = "SELECT sender_id FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                $select_stmt_following = $this->connection->prepare($select_following);

                $select_stmt_following->bind_param("ii", $self_user_id, $val['user_id']);

                if ($select_stmt_following->execute()) {
                    $select_stmt_following->store_result();
                    if ($select_stmt_following->num_rows > 0) {
                        $val['following_status'] = 1;
                    } else {
                        $val['following_status'] = 0;
                    }
                }
                if ($val['user_id'] == $self_user_id) {
                    $val['following_status'] = 2;
                }

                $allDetails[] = $val;
            }
        }

        $data['status'] = $status;
        $data['message'] = $message;
        $data['user_listing'] = $allDetails;
        $data['load_more'] = count($allDetails) == $limit;
        return $data;
    }


}

?>