<?php
include_once 'FCM.php';
include_once 'FeedFunctions.php';

class ChallengesFunctions
{
    protected $connection;

    function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {

            case "AddCategory": {
                return $this->addCategory($postData);
            }
                break;
            case "GetCategoryList": {
                return $this->getCategoryList($postData);
            }
                break;
            case "AddChallenges": {
                return $this->addChallenges();
            }
                break;

            case "UpdateChallenge": {
                return $this->updateChallenges();
            }
                break;
            case "GetChallengeDetails": {
                return $this->getChallengeDetails($postData);
            }
                break;
            case "GetAllChallengeList": {
                return $this->getAllChallengeList($postData);
                break;
            }
            case "AcceptOrDeclineChallenges": {

                return $this->acceptOrDeclineChallenges($postData);
                break;
            }
            case "AddCommentsToChallenge": {
                return $this->addCommentsToChallenge($postData);
                break;
            }
            case "LikeUnlikeChallenges": {
                return $this->likeUnlikeChallenges($postData);
                break;
            }
            case "AddRateToChallenge": {
                return $this->addRateToChallenge($postData);
                break;
            }
            case "GetFilterChallengeList": {
                return $this->getFilterChallengeList($postData);
                break;
            }
            case "AddFavoriteChallenge": {
                return $this->addFavoriteChallenge($postData);
                break;
            }
            case "ReportChallenge": {
                return $this->reportChallenge($postData);
                break;
            }
            case "LoadLikeChallenges": {
                return $this->loadLikeChallenges($postData);
                break;
            }
            case "GetCategoryChallenges": {
                return $this->getCategoryChallenges($postData);
                break;
            }
            case "GetRelevantChallenges": {
                return $this->getRelevantChallenges($postData);
                break;
            }
            default: {
                $data[DATA] = 'No Service Found';
                $data[MESSAGE] = $_REQUEST['Service'];
                return $data;
            }
                break;
        }
    }


    private function getCategoryList($postData)
    {


        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);


        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        $selectQuery = "SELECT id as category_id,user_id,category_name
                        FROM " . TABLE_CATEGORY . "
                        WHERE is_testdata=? AND is_delete=? GROUP BY id";

        $stmt = $this->connection->prepare($selectQuery);
        $stmt->bind_param("ii", $isTestData, $deleteStatus);

        $category = array();
        if ($stmt->execute()) {
            $stmt->store_result();

            while ($val = fetch_assoc_all_values($stmt)) {
                $category[] = $val;
            }
            $stmt->close();
        }

        $data[STATUS] = SUCCESS;
        $data[MESSAGE] = "Successfully fetched categories.";
        $data['categories'] = $category;

        return $data;
    }

    private function addCategory($postData)
    {
        $categoryName = validateObject($postData, 'category_name', 0);
        $categoryName = addslashes($categoryName);

        $userID = validateObject($postData, 'user_id', 0);
        $userID = addslashes($userID);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $insertQuery = "INSERT INTO " . TABLE_CATEGORY . "(user_id,category_name, created_date, is_testdata) VALUES(?,?,?,?)";

        if ($insertStmt = $this->connection->prepare($insertQuery)) {
            $createdDate = getDefaultDate();

            $insertStmt->bind_param('issi', $userID, $categoryName, $createdDate, $isTestData);
            if ($insertStmt->execute()) {
                $isQuerySuccess = true;
                $message = "success";
                $insertStmt->close();
            } else {
                $isQuerySuccess = false;
                $message = "Execute error-> " . $insertStmt->error;
            }
        } else {

            $isQuerySuccess = false;
            $message = "prepare error-> " . $this->connection->error;
        }

        if ($isQuerySuccess) {
            $message = "Category added successfully.";
            $status = SUCCESS;
        } else {
            $message = SERVER_ERROR . $message;
            $status = FAILED;
        }
        $data[STATUS] = $status;
        $data[MESSAGE] = $message;


        return $data;
    }

    function addCommentsToChallenge($postData)
    {

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $isDelete = DELETE_STATUS::NOT_DELETE;

        $userId = validateObject($postData, 'user_id', 0);

        $challengeId = validateObject($postData, 'challenge_id', 0);

        $comment = validateObject($postData, 'comment_message', "");
        $comment = utf8_decode($comment);

        $createdDate = getDefaultDate();
        $isQuerySuccess = true;
        if ($userId > 0 && $challengeId > 0 && strlen($comment) > 0) {

            $this->connection->begin_transaction();
            $insertQuery = "INSERT INTO " . TABLE_COMMENT_CHALLENGE . " (user_id,challenge_id,comment_message,is_testdata,created_date) VALUES(?,?,?,?,?)";
            $insertStmt = $this->connection->prepare($insertQuery);
            $insertStmt->bind_param('iisis', $userId, $challengeId, $comment, $is_testdata, $createdDate);

            if ($insertStmt->execute()) {
                $insertStmt->close();
                $commentId = mysqli_insert_id($this->connection);

                $selectFeed = "SELECT user_id FROM " . TABLE_COMMENT_CHALLENGE . " WHERE id=? AND is_delete=? AND is_testdata=?";
                $stmt = $this->connection->prepare($selectFeed);
                $feedOwner = -1;
                $stmt->bind_param("iss", $challengeId, $isDelete, $is_testdata);
                $stmt->execute();
                $stmt->bind_result($feedOwner);
                $stmt->fetch();
                $stmt->close();

                $notificationType = NOTIFICATION_TYPE_COMMENT;
                $notificationMsg = "NOTIFICATION_COMMENT_ON_FEED";
                $notificationData = '{"challenge_id":"' . $challengeId . '","comment_id":"' . $commentId . '","message":"' . $notificationMsg . '"}';
                $arrayNotification = array();

                $notificationObj['sender_id'] = (int)$userId;
                $notificationObj['receiver_id'] = (int)$feedOwner;
                $notificationObj['notification_type_id'] = (int)$challengeId;
                $notificationObj['notification_type'] = $notificationType;
                $notificationObj['is_testdata'] = (int)$is_testdata;
                // $notificationObj['notification_text'] = $notificationData;

                $arrayNotification[] = $notificationObj;
                if ($userId != $feedOwner) {
                    addEntryInNotificationTable($this->connection, $arrayNotification);


                    //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                    $select_query1 = "SELECT * FROM " . TABLE_USER . " WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                    $select_stmt1 = $this->connection->prepare($select_query1);
                    $select_stmt1->bind_param("is", $userId, $is_testdata);
                    if ($select_stmt1->execute()) {
                        $select_stmt1->store_result();
                        $val = fetch_assoc_all_values($select_stmt1);

                        $select_query2 = "SELECT * FROM " . TABLE_APP_TOKENS . " WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                        $select_stmt2 = $this->connection->prepare($select_query2);
                        $select_stmt2->bind_param('is', $notificationObj['receiver_id'], $is_testdata);
                        if ($select_stmt2->execute()) {
                            $select_stmt2->store_result();
                            if ($select_stmt2->num_rows() > 0) {
                                $getUserArr = fetch_assoc_all_values($select_stmt2);
                                $dataNotiArr['sender_firstname'] = $val['firstname'];
                                $dataNotiArr['sender_lastname'] = $val['lastname'];
                                $dataNotiArr['notification_type'] = '1';
                                $dataNotiArr['created_date'] = $createdDate;
                                $dataNotiArr['challenge_id'] = $challengeId;
                                $extraArr['title'] = "";
                                $extraArr['body'] = $val['firstname'] . ' ' . $val['lastname'] . ' commented on your photo';

                                if ($getUserArr['device_token'] != "") {
                                    $fcm = new FCM();
                                    $fcm->send_gcm_notify($getUserArr['device_token'], false, $dataNotiArr, $extraArr);
                                }
                            }
                        }

                    }
                    //<<<<<<<<----------- push notification code end ------------>>>>>>>>


                }

                if ($isQuerySuccess) {
                    $selectComment = "SELECT np.id ,np.user_id,np.challenge_id,np.comment_message,np.created_date,
                                          u.firstname,u.lastname,u.profilepic,u.description as user_description
                                          FROM " . TABLE_COMMENT_CHALLENGE . " np
                                          LEFT JOIN " . TABLE_USER . " u ON u.id=np.user_id
                                          WHERE np.id=?";


                    $stmt = $this->connection->prepare($selectComment);
                    $stmt->bind_param("i", $commentId);
                    $post = array();
                    if ($stmt->execute()) {
                        $stmt->store_result();
                        while ($val = fetch_assoc_all_values($stmt)) {
                            unset($val['is_delete']);
                            unset($val['is_testdata']);
                            //$val['profile_pic'] = URL_PROFILE_PIC . $val['profile_pic'];
                            $post[] = $val;
                        }
                        $stmt->close();
                    }

                    $this->connection->commit();

                    $data['comments'] = $post;
                    $data[STATUS] = SUCCESS;
                    $data[MESSAGE] = "Comment added successfully.";
                } else {
                    $this->connection->rollback();
                    $data[STATUS] = FAILED;
                    $data[MESSAGE] = "Please try again.";
                }


            } else {
                $this->connection->rollback();
                $data[STATUS] = FAILED;
                $data[MESSAGE] = "Please try again.";
            }
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }


        return $data;
    }

    function likeUnlikeChallenges($postData)
    {

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $userId = validateObject($postData, 'user_id', 0);

        $challengeId = validateObject($postData, 'challenge_id', 0);

        $createdDate = getDefaultDate();

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $posts = array();

        if ($userId > 0 && $challengeId > 0) {

            $select_like_query = "SELECT * FROM " . TABLE_LIKE_CHALLENGE . " WHERE challenge_id = ? AND user_id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";
            $select_like_stmt = $this->connection->prepare($select_like_query);
            $select_like_stmt->bind_param("iii", $challengeId, $userId, $is_testdata);
            if ($select_like_stmt->execute()) {
                $select_like_stmt->store_result();
                if ($select_like_stmt->num_rows > 0) {
                    $tbl_like = fetch_assoc_all_values($select_like_stmt);
                    $delete_query = "DELETE FROM " . TABLE_LIKE_CHALLENGE . " WHERE challenge_id = ? AND user_id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";
                    $delete_stmt = $this->connection->prepare($delete_query);
                    $delete_stmt->bind_param("iii", $challengeId, $userId, $is_testdata);
                    $delete_stmt->execute();

                    $select_query = "SELECT user_id,like_count FROM " . TABLE_CHALLENGE . " WHERE id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";

                    $select_stmt = $this->connection->prepare($select_query);
                    $select_stmt->bind_param("ii", $challengeId, $is_testdata);
                    if ($select_stmt->execute()) {
                        $select_stmt->store_result();
                        $status = 1;

                        $like_arr = fetch_assoc_all_values($select_stmt);


                        $notificationType = NOTIFICATION_TYPE_LIKE;
                        $notificationMsg = "NOTIFICATION_LIKE_ON_FEED";
                        $notificationData = '{"challenge_id":"' . $challengeId . '","like_id":"' . $tbl_like['id'] . '","message":"' . $notificationMsg . '"}';
                        $arrayNotification = array();

                        $notificationObj['sender_id'] = $userId;
                        $notificationObj['receiver_id'] = $like_arr['user_id'];
                        $notificationObj['notification_type_id'] = $challengeId;
                        $notificationObj['notification_type'] = $notificationType;
                        $notificationObj['is_testdata'] = $is_testdata;
                        // $notificationObj['notification_text'] = $notificationData;

                        $arrayNotification[] = $notificationObj;
                        // print_r($arrayNotification);

                        if ($userId != $like_arr['user_id']) {
                            removeEntryInNotificationTable($this->connection, $arrayNotification);
                        }


                        $posts['challenge_id'] = $challengeId;
                        $posts['like_count'] = $like_arr['like_count'];
                        $posts['like_status'] = 0;

                        $errorMsg = "Un-like post Successfully";
                    }

                } else {
                    $insert_query = "INSERT INTO " . TABLE_LIKE_CHALLENGE . " (challenge_id, user_id, created_date, is_testdata) VALUES(?,?,?,?)";
                    $insertStmt = $this->connection->prepare($insert_query);
                    $insertStmt->bind_param('iisi', $challengeId, $userId, $createdDate, $is_testdata);

                    if ($insertStmt->execute()) {
                        $insertStmt->close();
                        $likeId = mysqli_insert_id($this->connection);


                        $select_query = "SELECT user_id,like_count FROM " . TABLE_CHALLENGE . " WHERE id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";

                        $select_stmt = $this->connection->prepare($select_query);
                        $select_stmt->bind_param("ii", $challengeId, $is_testdata);
                        if ($select_stmt->execute()) {
                            $status = 1;

                            $select_stmt->store_result();
                            $like_arr = fetch_assoc_all_values($select_stmt);

                            $notificationType = NOTIFICATION_TYPE_LIKE;
                            $notificationMsg = "NOTIFICATION_LIKE_ON_FEED";
                            $notificationData = '{"challenge_id":"' . $challengeId . '","like_id":"' . $likeId . '","message":"' . $notificationMsg . '"}';
                            $arrayNotification = array();

                            $notificationObj['sender_id'] = (int)$userId;
                            $notificationObj['receiver_id'] = (int)$like_arr['user_id'];
                            $notificationObj['notification_type_id'] = (int)$challengeId;
                            $notificationObj['notification_type'] = $notificationType;
                            $notificationObj['is_testdata'] = (int)$is_testdata;
                            // $notificationObj['notification_text'] = $notificationData;

                            $arrayNotification[] = $notificationObj;

                            if ($userId != $like_arr['user_id']) {
                                addEntryInNotificationTable($this->connection, $arrayNotification);

                                //<<<<<<<<----------- push notification code start ------------>>>>>>>>

                                $select_query1 = "SELECT * FROM " . TABLE_USER . " WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
                                $select_stmt1 = $this->connection->prepare($select_query1);
                                $select_stmt1->bind_param("is", $userId, $is_testdata);
                                if ($select_stmt1->execute()) {
                                    $select_stmt1->store_result();
                                    $val = fetch_assoc_all_values($select_stmt1);

                                    $select_query2 = "SELECT * FROM " . TABLE_APP_TOKENS . " WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
                                    $select_stmt2 = $this->connection->prepare($select_query2);
                                    $select_stmt2->bind_param('is', $notificationObj['receiver_id'], $is_testdata);
                                    if ($select_stmt2->execute()) {
                                        $select_stmt2->store_result();
                                        if ($select_stmt2->num_rows() > 0) {
                                            $getUserArr = fetch_assoc_all_values($select_stmt2);
                                            $dataNotiArr['sender_firstname'] = $val['firstname'];
                                            $dataNotiArr['sender_lastname'] = $val['lastname'];
                                            $dataNotiArr['notification_type'] = '1';
//                                            $dataNotiArr['show_in_foreground']= true;
                                            $dataNotiArr['created_date'] = $createdDate;
                                            $dataNotiArr['challenge_id'] = $challengeId;
                                            $extraArr['title'] = "";
                                            $extraArr['body'] = $val['firstname'] . ' ' . $val['lastname'] . ' like your photo';

                                            if ($getUserArr['device_token'] != "") {
                                                $fcm = new FCM();
                                                $fcm->send_gcm_notify($getUserArr['device_token'], false, $dataNotiArr, $extraArr);
                                            }
                                        }
                                    }

                                }
                                //<<<<<<<<----------- push notification code end ------------>>>>>>>>
                            }

                            //for push notification by nitesh


                            $posts['challenge_id'] = $challengeId;
                            $posts['like_count'] = $like_arr['like_count'];
                            $posts['like_status'] = 1;

                            $errorMsg = "Like challenge Successfully";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something went wrong in insert query. " . $insertStmt->error;
                    }
                }
            }
            $data[STATUS] = ($status > 1 ? FAILED : SUCCESS);
            $data[MESSAGE] = $errorMsg;
            $data['like_post'] = $posts;
            return $data;
        } else {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        }
    }

    function addRateToChallenge($postData)
    {

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $isDelete = DELETE_STATUS::NOT_DELETE;

        $userId = validateObject($postData, 'user_id', 0);

        $challengeId = validateObject($postData, 'challenge_id', 0);

        $rating = validateObject($postData, 'rating', 0.00);

        $createdDate = getDefaultDate();
        $isQuerySuccess = true;
        if ($userId > 0 && $challengeId > 0 && $rating > 0) {

            $this->connection->begin_transaction();
            $insertQuery = "INSERT INTO " . TABLE_RATE_CHALLENGE . " (user_id,challenge_id,rate,is_testdata,created_date) VALUES(?,?,?,?,?)";
            $insertStmt = $this->connection->prepare($insertQuery);
            // echo $userId." ". $challengeId." ". $rating." ". $is_testdata." ". $createdDate;
            $insertStmt->bind_param('iisss', $userId, $challengeId, $rating, $is_testdata, $createdDate);

            if ($insertStmt->execute()) {
                $insertStmt->close();
                $commentId = mysqli_insert_id($this->connection);

                $selectFeed = "SELECT user_id FROM " . TABLE_CHALLENGE . " WHERE id=? AND is_delete=? AND is_testdata=?";
                $stmt = $this->connection->prepare($selectFeed);
                $feedOwner = -1;
                $stmt->bind_param("iss", $challengeId, $isDelete, $is_testdata);
                $stmt->execute();
                $stmt->bind_result($feedOwner);
                $stmt->fetch();
                $stmt->close();

//                $notificationType = NOTIFICATION_TYPE_COMMENT;
//                $notificationMsg = "NOTIFICATION_COMMENT_ON_FEED";
//                $notificationData = '{"challenge_id":"' . $challengeId . '","comment_id":"' . $commentId . '","message":"' . $notificationMsg . '"}';
//                $arrayNotification = array();
//
//                $notificationObj['sender_id'] = (int)$userId;
//                $notificationObj['receiver_id'] = (int)$feedOwner;
//                $notificationObj['notification_type_id'] = (int)$challengeId;
//                $notificationObj['notification_type'] = $notificationType;
//                $notificationObj['is_testdata'] = (int)$is_testdata;
//                // $notificationObj['notification_text'] = $notificationData;
//
//                $arrayNotification[] = $notificationObj;
//                if ($userId != $feedOwner) {
//                    addEntryInNotificationTable($this->connection, $arrayNotification);
//
//
//                    //<<<<<<<<----------- push notification code start ------------>>>>>>>>
//
//                    $select_query1 = "SELECT * FROM " . TABLE_USER . " WHERE id = ? AND is_testdata = ? AND is_delete = '0' ";
//                    $select_stmt1 = $this->connection->prepare($select_query1);
//                    $select_stmt1->bind_param("is", $userId, $is_testdata);
//                    if ($select_stmt1->execute()) {
//                        $select_stmt1->store_result();
//                        $val = fetch_assoc_all_values($select_stmt1);
//
//                        $select_query2 = "SELECT * FROM " . TABLE_APP_TOKENS . " WHERE user_id = ? AND is_delete = '0' AND is_testdata = ?";
//                        $select_stmt2 = $this->connection->prepare($select_query2);
//                        $select_stmt2->bind_param('is', $notificationObj['receiver_id'], $is_testdata);
//                        if ($select_stmt2->execute()) {
//                            $select_stmt2->store_result();
//                            if ($select_stmt2->num_rows() > 0) {
//                                $getUserArr = fetch_assoc_all_values($select_stmt2);
//                                $dataNotiArr['sender_firstname'] = $val['firstname'];
//                                $dataNotiArr['sender_lastname'] = $val['lastname'];
//                                $dataNotiArr['notification_type'] = '1';
//                                $dataNotiArr['created_date'] = $createdDate;
//                                $dataNotiArr['challenge_id'] = $challengeId;
//                                $extraArr['title'] = "";
//                                $extraArr['body'] = $val['firstname'] . ' ' . $val['lastname'] . ' commented on your photo';
//
//                                if ($getUserArr['device_token'] != "") {
//                                    $fcm = new FCM();
//                                    $fcm->send_gcm_notify($getUserArr['device_token'], false, $dataNotiArr, $extraArr);
//                                }
//                            }
//                        }
//
//                    }
//                    //<<<<<<<<----------- push notification code end ------------>>>>>>>>
//
//
//                }

                if ($isQuerySuccess) {
                    $selectComment = "SELECT np.id ,np.user_id,np.challenge_id,np.created_date,np.rate,
                                          u.firstname,u.lastname,u.profilepic,u.description as user_description
                                          FROM " . TABLE_RATE_CHALLENGE . " np
                                          LEFT JOIN " . TABLE_USER . " u ON u.id=np.user_id
                                          WHERE np.id=?";


                    $stmt = $this->connection->prepare($selectComment);
                    $stmt->bind_param("i", $commentId);
                    $post = array();
                    if ($stmt->execute()) {
                        $stmt->store_result();
                        while ($val = fetch_assoc_all_values($stmt)) {
                            unset($val['is_delete']);
                            unset($val['is_testdata']);
                            //$val['profile_pic'] = URL_PROFILE_PIC . $val['profile_pic'];
                            $post[] = $val;
                        }
                        $stmt->close();
                    }

                    $this->connection->commit();

                    $data['rate'] = $post;
                    $data[STATUS] = SUCCESS;
                    $data[MESSAGE] = "rate added successfully.";
                } else {
                    $this->connection->rollback();
                    $data[STATUS] = FAILED;
                    $data[MESSAGE] = "Please try again.";
                }


            } else {
                $this->connection->rollback();
                $data[STATUS] = FAILED;
                $data[MESSAGE] = "Please try again.";
            }
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }


        return $data;
    }

    function updateChallenges()
    {
        $userId = validatePostValue('user_id', 0);

        $inviteUserId = validatePostValue('invite_user_id', 0);

        $challengeId = validatePostValue('challenge_id', 0);

        $categoryId = validatePostValue('category_id', 0);

        $description = validatePostValue('description', "");

        $challengeName = validatePostValue('challenge_name', "");

        $longitude = validatePostValue('longitude', "");

        $latitude = validatePostValue('latitude', "");

        $address = validatePostValue('address', "");

        $isTestData = validatePostValue('is_testdata', IS_TEST_DATA);

        $postType = validatePostValue('post_type', IS_TEST_DATA);

        $keyMedia = 'post_media';
        if (!empty($_FILES[$keyMedia]['name'])) {
            $mediaCount = count(array_filter($_FILES[$keyMedia]['name']));
        } else {
            $mediaCount = 0;
        }
        $createdDate = getDefaultDate();

        $mediaRemoveId = validatePostValue('product_remove_media_id', "");

        $hasMedia = $mediaCount > 0 ? 1 : 0;

        if ($userId > 0) {
            $feed = new FeedFunctions($this->connection);
            $this->connection->begin_transaction();
            $isQuerySuccess = true;

            $updateQuery = "UPDATE " . TABLE_CHALLENGE . " SET category_id=?, description=?,challenge_name=?,longitude=?,latitude=?,address=?,created_date=? WHERE id=? AND user_id=? AND is_testdata=? AND is_delete=" . DELETE_STATUS::NOT_DELETE . "";
            // echo "\n".$categoryId." ". $description." ". $challengeName." ". $latitude." ". $longitude, $address." ". $challengeId." ". $userId." ". $isTestData;
            $updateStmt = $this->connection->prepare($updateQuery);

            $updateStmt->bind_param('issssssiii', $categoryId, $description, $challengeName, $latitude, $longitude, $address,$createdDate, $challengeId, $userId, $isTestData);

            if ($updateStmt->execute()) {
                $row = $updateStmt->affected_rows;
                if ($row > 0) {
                    $updateStmt->close();

                    if ($isQuerySuccess) {
                        $isQuerySuccess = $this->inviteUserForChallenge($inviteUserId, $challengeId, $isTestData);
                    }

                    $categoryIdArr[] = $challengeId;

                    if ($hasMedia > 0 && $isQuerySuccess) {
                        $feed->createFeedMediaDirectory();

                        $imageInfo = array();
                        for ($i = 0; $i < $mediaCount; $i++) {
                            if ($isQuerySuccess) {
                                $imageInfo[] = $feed->uploadMediasToFeed($i, $categoryIdArr, $userId, $isQuerySuccess, $keyMedia, $postType);
                            } else {
                                $data[MESSAGE] = $imageInfo[$i - 1][MESSAGE];
                                break;
                            }
                        }
                    }


                    if ($isQuerySuccess && strlen($mediaRemoveId) > 0) {

                        $deleteStatus = DELETE_STATUS::NOT_DELETE;
                        $selectMedia = "SELECT media_name,media_type,id as media_id  FROM " . TABLE_MEDIA . " WHERE id IN (" . $mediaRemoveId . ") AND is_delete=?";
                        $stmtMedia = $this->connection->prepare($selectMedia);
                        $stmtMedia->bind_param("i", $deleteStatus);

                        if ($stmtMedia->execute()) {
                            $stmtMedia->store_result();
                            $isDelete = DELETE_STATUS::IS_DELETE;

                            $removeMediaQuery = "UPDATE " . TABLE_MEDIA . " SET is_delete=? WHERE id IN (" . $mediaRemoveId . ")";
                            $removeStmt = $this->connection->prepare($removeMediaQuery);

                            $removeStmt->bind_param('i', $isDelete);

                            if ($removeStmt->execute()) {
                                $removeStmt->close();
                                while ($valMedia = fetch_assoc_all_values($stmtMedia)) {

                                    //delete previous image
                                    $uploadDirImg = $feed->getMediaPrefixLocalPathBasedOnType($valMedia['media_type']);
                                    if (file_exists($uploadDirImg . $valMedia['media_name'])) {
                                        unlink($uploadDirImg . $valMedia['media_name']);
                                    }
                                }
                            } else {
                                $data[MESSAGE] = "Error Occurred while removing feed media.";
                                $isQuerySuccess = false;
                            }
                        } else {
                            $data[MESSAGE] = "Error Occurred while removing feed media.";
                            $isQuerySuccess = false;
                        }
                    }
                }else
                {
                    $isQuerySuccess=false;
                    $data[MESSAGE] = DEV_ERROR;
                }

            }

            if ($isQuerySuccess) {
                $this->connection->commit();
                $challengeObj = (object)array('user_id' => $userId, 'challenge_id' => $challengeId);
                $challengeDetails = $this->getChallengeDetails($challengeObj);
                $data['challenges'] = $challengeDetails['challenges'];
                $data[MESSAGE] = "Challenge updated successfully";

            } else {
                $this->connection->rollback();
            }
            $data[STATUS] = $isQuerySuccess ? SUCCESS : FAILED;

            return $data;
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Please provide data.";
            return $data;
        }
    }

    private function addChallenges()
    {

        $userId = validatePostValue('user_id', 0);

        $inviteUserId = validatePostValue('invite_user_id', 0);

        $categoryId = validatePostValue('category_id', 0);

        $description = validatePostValue('description', "");

        $challengeName = validatePostValue('challenge_name', "");

        $longitude = validatePostValue('longitude', "");

        $latitude = validatePostValue('latitude', "");

        $address = validatePostValue('address', "");

        $isTestData = validatePostValue('is_testdata', IS_TEST_DATA);

        $postType = validatePostValue('post_type', IS_TEST_DATA);

        $keyMedia = 'post_media';
        if (!empty($_FILES[$keyMedia]['name'])) {
            $mediaCount = count(array_filter($_FILES[$keyMedia]['name']));
        } else {
            $mediaCount = 0;
        }

        $hasMedia = $mediaCount > 0 ? 1 : 0;

        if ($userId == 0) {

            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;

        } else {

            $feed = new FeedFunctions($this->connection);

            $deleteStatus = DELETE_STATUS::NOT_DELETE;

            $isQuerySuccess = false;
            $this->connection->begin_transaction();
            $message = "";
            $challengeId = 0;
            $createdDate = getDefaultDate();

            $categoryIdArr = array();

            $insertQuery = "INSERT INTO " . TABLE_CHALLENGE . "(user_id, category_id, description,challenge_name,longitude,latitude,address,created_date,is_delete,is_testdata) VALUES(?,?,?,?,?,?,?,?,?,?)";

            if ($insertStmt = $this->connection->prepare($insertQuery)) {

                $insertStmt->bind_param('iisssssssi', $userId, $categoryId, $description, $challengeName, $longitude, $latitude, $address, $createdDate, $deleteStatus, $isTestData);
                if ($insertStmt->execute()) {
                    $challengeId = $insertStmt->insert_id;

                    $categoryIdArr[] = $challengeId;

                    //$feedId = $insertStmt->insert_id;
                    $isQuerySuccess = true;

                    $insertStmt->close();
                } else {
                    $message = "Execute error-> " . $insertStmt->error;
                }
            } else {

                $isQuerySuccess = false;
                $message = "prepare error-> " . $this->connection->error;
            }

            if ($isQuerySuccess) {
                $isQuerySuccess = $this->inviteUserForChallenge($inviteUserId, $challengeId, $isTestData);
            }

            if ($hasMedia > 0 && $isQuerySuccess) {
                $feed->createFeedMediaDirectory();

                $imageInfo = array();
                for ($i = 0; $i < $mediaCount; $i++) {
                    if ($isQuerySuccess) {
                        $imageInfo[] = $feed->uploadMediasToFeed($i, $categoryIdArr, $userId, $isQuerySuccess, $keyMedia, $postType);
                    } else {
                        $message = $imageInfo[$i - 1][MESSAGE];
                        break;
                    }
                }
            }

            if ($isQuerySuccess) {

                $this->connection->commit();
                $message = "Challenge posted successfully.";
                $challengeObj = (object)array('user_id' => $userId, 'challenge_id' => $challengeId);
                $challengeDetails = $this->getChallengeDetails($challengeObj);
                $data['challenges'] = $challengeDetails['challenges'];
                $status = SUCCESS;
            } else {
                $this->connection->rollback();
                $message = SERVER_ERROR . $message;
                $status = FAILED;
            }
            $data[STATUS] = $status;
            $data[MESSAGE] = $message;

        }
        return $data;
    }

    private function inviteUserForChallenge($user_ids, $challenge_id, $is_testdata)
    {
        $userIdArray = explode(',', $user_ids);

        $createdDate = getDefaultDate();

        $isDelete = NOTDELTE;
        foreach ($userIdArray as $userId) {
            $insertQuery = "INSERT INTO " . TABLE_CHALLENGE_USER . "(challenge_id, user_id,created_date,is_delete,is_testdata) VALUES(?,?,?,?,?)";
            if ($insertStmt = $this->connection->prepare($insertQuery)) {
                $insertStmt->bind_param('iisii', $challenge_id, $userId, $createdDate, $isDelete, $is_testdata);
                if ($insertStmt->execute()) {
                    $isQuerySuccess = true;
                    $insertStmt->close();
                } else {
                    $isQuerySuccess = false;
                }
            } else {
                $isQuerySuccess = false;
            }
        }


        return $isQuerySuccess;
    }

    private function getAllChallengeList($postData)
    {
        $categoryId = validateObject($postData, 'category_id', IS_TEST_DATA);

        $userId = validateObject($postData, 'user_id', '');

        $is_get_all=validateObject($postData, 'is_get_all', '1');

        $status = validateObject($postData, 'challenge_detail_status', CHALLENGE_DETAIL_STATUS::ALL);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($postData, 'offset', 0);
        $limit = 500;


        //$limit = 20;


        $deleteStatus = DELETE_STATUS::NOT_DELETE;


        if ($status == CHALLENGE_DETAIL_STATUS::ACCEPT) {
            $selectQuery = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE /*f.category_id=? AND*/ f.is_delete=? AND f.is_testdata=? AND (f.user_id=? || cu.user_id=?) AND cu.challenge_status=" . CHALLENGE_STATUS::ACCEPT . " GROUP BY f.id  ORDER BY f.id DESC LIMIT ? OFFSET ?";

            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiss", $deleteStatus, $isTestData, $userId, $userId, $limit, $offset);

        } else if ($status == CHALLENGE_DETAIL_STATUS::DECLINE) {
            $selectQuery = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE /* f.category_id=? AND*/ f.is_delete=? AND f.is_testdata=? AND (f.user_id=? || cu.user_id=?) AND cu.challenge_status=" . CHALLENGE_STATUS::DECLINE . " GROUP BY f.id ORDER BY f.id DESC LIMIT ? OFFSET ?";
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiss", $deleteStatus, $isTestData, $userId, $userId, $limit, $offset);

        } else {
            if($categoryId == 0)
            {

//                $data["cat"] = $categoryId;

            $selectQuery = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE f.is_delete=? AND f.is_testdata=? AND (f.user_id=? || cu.user_id=?) GROUP BY f.id ORDER BY f.id DESC  LIMIT ? OFFSET ? ";
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiss",$deleteStatus, $isTestData, $userId, $userId, $limit, $offset);
            }
            else
            {
                $data["cat"] = $categoryId;
                $selectQuery = "SELECT f.id as challenge_id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE f.category_id=? AND f.is_delete=? AND f.is_testdata=? AND (f.user_id=? || cu.user_id=?) GROUP BY f.id ORDER BY f.id DESC  LIMIT ? OFFSET ? ";
                $stmt = $this->connection->prepare($selectQuery);
                $stmt->bind_param("iiiiiss",$categoryId,$deleteStatus, $isTestData, $userId, $userId, $limit, $offset);
            }
            //echo $categoryId." ". $deleteStatus." ". $isTestData." ". $userId." ". $userId." ".$limit." ". $offset;

        }
        $post = array();
        if ($stmt->execute()) {
            $stmt->store_result();
            while ($val = fetch_assoc_all_values($stmt)) {
                $userDetail = getUserInformation($this->connection, $val["cerated_user_id"], $isTestData);
                $val['created_user'] = $userDetail;

                //=============>>>>>>>>
                $selectMedia = "SELECT m.media_name,m.id as media_id,m.media_type,post_type,m.challenge_id
                FROM " . TABLE_MEDIA . " m
                WHERE m.challenge_id=? AND m.is_delete=? AND m.is_testdata=?";
                $stmtMedia = $this->connection->prepare($selectMedia);
                $stmtMedia->bind_param("iii", $val['challenge_id'], $deleteStatus, $isTestData);

                $postMedia = array();
                if ($stmtMedia->execute()) {
                    $stmtMedia->store_result();
                    while ($valMedia = fetch_assoc_all_values($stmtMedia)) {
                        unset($valMedia['is_delete']);
                        unset($valMedia['is_testdata']);
                        $valMedia['media_url'] = $valMedia['media_name'];
                        unset($valMedia['media_name']);
                        $postMedia[] = $valMedia;
                    }
                }
                $val['challenge_media'] = $postMedia;

//                $this->addMediaObjectToChallenges($val);



//                $select_media = "SELECT id,media_type,post_type,media_name FROM " . TABLE_MEDIA . " where challenge_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $isTestData . "";
//                $stmt_media = $this->connection->prepare($select_media);
//                $stmt_media->bind_param("i", $val['id']);
//                if ($stmt_media->execute()) {
//                    $stmt_media->store_result();
//                    if ($stmt_media->num_rows > 0) {
//                        $i = 0;
//                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
//                            $val['media'][$i]['media_id'] = $val_media['id'];
//                            $val['media'][$i]['post_image'] = $val_media['media_name'];
//                            $val['media'][$i]['type'] = $val_media['media_type'];
//                            $val['media'][$i]['post_type'] = $val_media['post_type'];
//                            $i++;
//                        }
//                    }
//
//                }

                if($is_get_all=='1')
                {
                    $post[]=$val;

                }else{
                    if ($val["cerated_user_id"] == $userId) {
                        $post["created_challeges"][] = $val;
                    } else {
                        $post["others_challeges"][] = $val;
                    }
                }

            }
        }
        $data[STATUS] = SUCCESS;
        $data[MESSAGE] = "Successfully fetched challenges.";
        $data['challenges'] = $post;

        return $data;

    }

    private function getFilterChallengeList($postData)
    {
        $categoryId = validateObject($postData, 'category_id', IS_TEST_DATA);

        $userId = validateObject($postData, 'user_id', '');

        $status = validateObject($postData, 'challenge_detail_status', CHALLENGE_DETAIL_STATUS::ALL);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;


        //$limit = 20;


        $deleteStatus = DELETE_STATUS::NOT_DELETE;


        if ($status == CHALLENGE_DETAIL_STATUS::ACCEPT) {
            $selectQuery = "SELECT f.id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE f.category_id=? AND f.is_delete=? AND f.is_testdata=? AND  cu.user_id=? AND cu.challenge_status=" . CHALLENGE_STATUS::ACCEPT . " GROUP BY f.id  ORDER BY f.id LIMIT ? OFFSET ?";
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiss", $categoryId, $deleteStatus, $isTestData, $userId, $limit, $offset);


        } else {
            $selectQuery = "SELECT f.id, f.user_id as cerated_user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.created_date,f.category_id,cu.challenge_status,f.like_count,f.comment_count,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_CHALLENGE_USER . " cu ON cu.challenge_id=f.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE f.category_id=? AND f.is_delete=? AND f.is_testdata=? AND f.user_id=? GROUP BY f.id ORDER BY f.id LIMIT ? OFFSET ?";
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiss", $categoryId, $deleteStatus, $isTestData, $userId, $limit, $offset);

        }

        $post = array();
        if ($stmt->execute()) {
            $stmt->store_result();
            while ($val = fetch_assoc_all_values($stmt)) {
                $userDetail = getUserInformation($this->connection, $val["cerated_user_id"], $isTestData);
                $val['created_user'] = $userDetail;
                if ($status == CHALLENGE_DETAIL_STATUS::ACCEPT) {
                    $post["accepted_challeges"][] = $val;
                } else {
                    $post["sended_challeges"][] = $val;
                }
            }
        }
        $data[STATUS] = SUCCESS;
        $data[MESSAGE] = "Successfully fetched challenges.";
        $data['challenges'] = $post;

        return $data;

    }

    private function getChallengeDetails($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $challengeId = validateObject($postData, 'challenge_id', 0);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        if ($userId == 0 || $challengeId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {

            $deleteStatus = DELETE_STATUS::NOT_DELETE;

            $selectQuery = "SELECT f.id as challenge_id, f.user_id,f.description, f.challenge_name , f.latitude,f.longitude,f.address,f.like_count,f.comment_count,
                        u.firstname,u.lastname,u.profilepic,u.description as user_description,f.created_date,
                        IF(tr.id IS NULL,0,1) as is_rate,
                        IF(tl.id IS NULL,0,1) as is_like
                        FROM " . TABLE_CHALLENGE . " f
                        LEFT JOIN " . TABLE_USER . " u ON f.user_id=u.id
                        LEFT JOIN " . TABLE_RATE_CHALLENGE . " tr ON tr.challenge_id=f.id
                        LEFT JOIN " . TABLE_LIKE_CHALLENGE . " tl ON tl.challenge_id=f.id
                        WHERE f.id=? AND f.is_delete=? AND u.is_delete=? AND f.is_testdata=? AND u.is_testdata=? GROUP BY f.id";

            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiii", $challengeId, $deleteStatus, $deleteStatus, $isTestData, $isTestData);

            $post = array();
            if ($stmt->execute()) {
                $stmt->store_result();

                while ($val = fetch_assoc_all_values($stmt)) {
                    $val['profilepic'] = ($val['profilepic'] ? $val['profilepic'] : "");

//                    $select_media = "SELECT id,media_type,post_type,media_name FROM " . TABLE_MEDIA . " where challenge_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $isTestData . "";
//                    $stmt_media = $this->connection->prepare($select_media);
//                    $stmt_media->bind_param("i", $challengeId);
//                    if ($stmt_media->execute()) {
//                        $stmt_media->store_result();
//                        $i = 0;
//                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
//                            $val['media'][$i]['media_id'] = $val_media['id'];
//                            $val['media'][$i]['post_image'] = $val_media['media_name'];
//                            $val['media'][$i]['type'] = $val_media['media_type'];
//                            $val['media'][$i]['post_type'] = $val_media['post_type'];
//                            $i++;
//                        }
//                    }


                    $this->addMediaObjectToChallenges($val);

                    $select_challenge_user = "SELECT cu.user_id,u.firstname,u.lastname,u.profilepic,u.description as user_description,cu.challenge_status FROM " . TABLE_CHALLENGE_USER . " cu
                                    LEFT JOIN " . TABLE_USER . " u on u.id=cu.user_id
                                    where cu.challenge_id= ? AND cu.is_delete=" . $deleteStatus . " AND cu.is_testdata = " . $isTestData . "";
                    $stmt_user = $this->connection->prepare($select_challenge_user);
                    $stmt_user->bind_param("i", $challengeId);
                    if ($stmt_user->execute()) {
                        $stmt_user->store_result();
                        $i = 0;
                        //echo $challengeId;
                        //  print_r(fetch_assoc_all_values($stmt_user));
                        while ($val_challenge_user = fetch_assoc_all_values($stmt_user)) {

                            $val_challenge_user['profilepic'] = ($val_challenge_user['profilepic'] ? $val_challenge_user['profilepic'] : "");

                            $val['challenge_user'][$i]['firstname'] = $val_challenge_user['firstname'];
                            $val['challenge_user'][$i]['lastname'] = $val_challenge_user['lastname'];
                            $val['challenge_user'][$i]['profilepic'] = $val_challenge_user['profilepic'];
                            $val['challenge_user'][$i]['challenge_status'] = $val_challenge_user['challenge_status'];
                            $val['challenge_user'][$i]['user_id'] = $val_challenge_user['user_id'];
                            $i++;
                        }
                    }

                    $post[] = $val;
                }
                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched challenges.";
            $data['challenges'] = $post;

        }
        return $data;
    }


    private function acceptOrDeclineChallenges($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $challengeId = validateObject($postData, 'challenge_id', 0);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $challengeStatus = validateObject($postData, 'challenge_status', CHALLENGE_DETAIL_STATUS::ACCEPT);

        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($challengeStatus == CHALLENGE_DETAIL_STATUS::ACCEPT) {
            $challengeStatus = CHALLENGE_STATUS::ACCEPT;
            $message = "Challenge accept.";
        } else {
            $challengeStatus = CHALLENGE_STATUS::DECLINE;
            $message = "Challenge decline.";
        }
        // echo  $challengeStatus;

        $update_challenge_status = "UPDATE " . TABLE_CHALLENGE_USER . " SET challenge_status=? WHERE user_id=? AND challenge_id=? AND is_testdata=? AND is_delete=? ";
        $stmt_user = $this->connection->prepare($update_challenge_status);
        $stmt_user->bind_param("siiii", $challengeStatus, $userId, $challengeId, $isTestData, $deleteStatus);
        if ($stmt_user->execute()) {
            $stmt_user->store_result();

            $challengeObj = (object)array('user_id' => $userId, 'challenge_id' => $challengeId);
            $challengeDetails = $this->getChallengeDetails($challengeObj);
            $status = SUCCESS;
            //$message = "Challenge accepted successfully.";
            $data['challenges'] = $challengeDetails['challenges'];

        } else {
            $status = FAILED;
            $message = SERVER_ERROR;
            $data['challenges'] = array();

        }
        $data[STATUS] = $status;
        $data[MESSAGE] = $message;

        return $data;
    }

//    function acceptOrDeclineChallenges($postData)
//    {
//
//        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);
//
//        $userId = validateObject($postData, 'user_id', 0);
//
//        $challengeId = validateObject($postData, 'challenge_id', 0);
//
//        $challengeStatus = validateObject($postData, 'challenge_status', CHALLENGE_DETAIL_STATUS::DECLINE);
//
//        $is_delete = DELETE_STATUS::NOT_DELETE;
//
//        $status=0;
//        $message=DEV_ERROR;
//
//
//        if ($userId > 0 && $challengeId > 0) {
//
//            $select_like_query = "SELECT challenge_status FROM " . TABLE_CHALLENGE_USER . " WHERE challenge_id = ? AND user_id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";
//            $select_like_stmt = $this->connection->prepare($select_like_query);
//            $select_like_stmt->bind_param("iii", $challengeId, $userId, $is_testdata);
//            if ($select_like_stmt->execute()) {
//                $select_like_stmt->store_result();
//                if ($select_like_stmt->num_rows > 0) {
//
//                    while($val=fetch_assoc_all_values($select_like_stmt))
//                    {
//
//                        if($val['challenge_status']==CHALLENGE_STATUS::ACCEPT)
//                        {
//                            $challengeStatus=CHALLENGE_STATUS::DECLINE;
//                            $message = "Challenge decline.";
//                        }
//                        else{
//                            $challengeStatus=CHALLENGE_STATUS::ACCEPT;
//                            $message = "Challenge accept.";
//                        }
//
//                        $update_challenge_status = "UPDATE " . TABLE_CHALLENGE_USER . " SET challenge_status=? WHERE user_id=? AND challenge_id=? AND is_testdata=? AND is_delete=? ";
//                        $stmt_user = $this->connection->prepare($update_challenge_status);
//                        $stmt_user->bind_param("siiii", $challengeStatus, $userId, $challengeId, $is_testdata, $is_delete);
//
//                        //echo  $challengeStatus." ". $userId." ". $challengeId." ". $is_testdata." ". $is_delete;
//                        if ($stmt_user->execute()) {
//                            $stmt_user->store_result();
//                            $challengeObj = (object)array('user_id' => $userId, 'challenge_id' => $challengeId);
//                            $challengeDetails = $this->getChallengeDetails($challengeObj);
//                            $status = SUCCESS;
//                            $data['challenges'] = $challengeDetails['challenges'];
//
//                        } else {
//                            $status = FAILED;
//                            $message = SERVER_ERROR;
//                            $data['challenges'] = array();
//
//                        }
//                    }
//
//                } else {
//                    $status = FAILED;
//                    $message = "No any challenges found";
//                    $data['challenges'] = array();
//
//                }
//                $data[STATUS] = ($status > 1 ? FAILED : SUCCESS);
//                $data[MESSAGE] = $message;
//                return $data;
//            }
//
//        } else {
//            $data[MESSAGE] = DEV_ERROR;
//            $data[STATUS] = FAILED;
//            return $data;
//        }
//    }

    private function addFavoriteChallenge($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $challengeId = validateObject($postData, 'challenge_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', 0);
        $getCurrentDate = getDefaultDate();

        if ($userId == 0 || $challengeId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $status = 2;
            $posts = array();
            $is_delete = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $select_query = "SELECT challenge_id FROM " . TABLE_FAVORITE_CHALLENGE . " WHERE user_id = ? AND challenge_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("iii", $userId, $challengeId, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();

                if ($select_stmt->num_rows > 0) {
                    $delete_query = "DELETE FROM " . TABLE_FAVORITE_CHALLENGE . " WHERE user_id = ? AND challenge_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";
                    if ($delete_stmt = $this->connection->prepare($delete_query)) {
                        $delete_stmt->bind_param("iii", $userId, $challengeId, $is_testdata);
                        if ($delete_stmt->execute()) {

                            $posts['challenge_id'] = (int)$challengeId;
                            $posts['saved_status'] = 0;
                            $status = 1;
                            $errorMsg = "Challenge un-saved successfully";
                            $data['challenge'] = $posts;

                        } else {
                            $status = 2;
                            $errorMsg = "Something wrong with delete query 1";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something wrong with delete query 1";
                    }
                } else {
                    $insert_query = "INSERT INTO " . TABLE_FAVORITE_CHALLENGE . " (user_id, challenge_id,created_date,is_testdata) VALUES(?,?,?,?)";
                    if ($insert_stmt = $this->connection->prepare($insert_query)) {
                        $insert_stmt->bind_param("iisi", $userId, $challengeId, $getCurrentDate, $is_testdata);
                        if ($insert_stmt->execute()) {
                            $inserted_save_challenge_id = $insert_stmt->insert_id;

                            $posts['challenge_id'] = (int)$challengeId;
                            $posts['saved_status'] = 1;
                            $status = 1;
                            $errorMsg = "Challenge saved successfully";
                            $data['challenge'] = $posts;

                        } else {
                            $status = 2;
                            $errorMsg = "Something wrong with insert query 1";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something wrong with insert query 1";
                    }
                }
            } else {
                $status = 2;
                $errorMsg = "Something wrong with select query 1";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;

        }

        return $data;
    }

    private function reportChallenge($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $challengeId = validateObject($postData, 'challenge_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', 0);
        $getCurrentDate = getDefaultDate();

        if ($userId == 0 || $challengeId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $status = 2;
            $posts = array();
            $is_delete = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $select_query = "SELECT challenge_id FROM " . TABLE_REPORT_CHALLENGE . " WHERE user_id = ? AND challenge_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("iii", $userId, $challengeId, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();

                if ($select_stmt->num_rows > 0) {
                    $delete_query = "DELETE FROM " . TABLE_REPORT_CHALLENGE . " WHERE user_id = ? AND challenge_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";
                    if ($delete_stmt = $this->connection->prepare($delete_query)) {
                        $delete_stmt->bind_param("iii", $userId, $challengeId, $is_testdata);
                        if ($delete_stmt->execute()) {

                            $posts['challenge_id'] = (int)$challengeId;
                            $posts['report_status'] = 0;
                            $status = 1;
                            $errorMsg = "Challenge un-reported successfully";
                            $data['post'] = $posts;

                        } else {
                            $status = 2;
                            $errorMsg = "Something wrong with delete query 1";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something wrong with delete query 1";
                    }
                } else {
                    $insert_query = "INSERT INTO " . TABLE_REPORT_CHALLENGE . " (user_id, challenge_id,created_date,is_testdata) VALUES(?,?,?,?)";
                    if ($insert_stmt = $this->connection->prepare($insert_query)) {
                        $insert_stmt->bind_param("iisi", $userId, $challengeId, $getCurrentDate, $is_testdata);
                        if ($insert_stmt->execute()) {
                            $inserted_save_challenge_id = $insert_stmt->insert_id;


                            $posts['challenge_id'] = (int)$challengeId;
                            $posts['report_status'] = 1;
                            $status = 1;
                            $errorMsg = "Challenge reported successfully";
                            $data['post'] = $posts;

                        } else {
                            $status = 2;
                            $errorMsg = "Something wrong with insert query 1";
                        }
                    } else {
                        $status = 2;
                        $errorMsg = "Something wrong with insert query 1";
                    }
                }
            } else {
                $status = 2;
                $errorMsg = "Something wrong with select query 1";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;

        }

        return $data;
    }

    function loadLikeChallenges($postData)
    {

        $offset = validateObject($postData, 'offset', 0);
        $userId = validateObject($postData, 'user_id', 0);
        $challengeId = validateObject($postData, 'challenge_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_OFFSET_CONTACT + 5;
        $offset = $offset * $limit;


        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($userId > 0 && $challengeId > 0) {
            $select_query_like = "SELECT l.id,l.challenge_id,l.user_id,u.firstname,u.lastname,u.profilepic,u.description as user_description FROM " . TABLE_LIKE_CHALLENGE . " as l LEFT JOIN " . TABLE_USER . " as u ON u.id=l.user_id
            WHERE l.challenge_id = ? AND l.is_delete = " . $deleteStatus . " AND u.is_delete = " . $deleteStatus . " AND l.is_testdata = ? AND u.is_testdata = ? ORDER BY l.id DESC LIMIT ? OFFSET ?";

            $select_stmt_like = $this->connection->prepare($select_query_like);
            $select_stmt_like->bind_param("iiiii", $challengeId, $is_testdata, $is_testdata, $limit, $offset);

            $postLikes = array();

            if ($select_stmt_like->execute()) {
                $select_stmt_like->store_result();
                while ($val = fetch_assoc_all_values($select_stmt_like)) {
                    $select_following = "SELECT sender_id FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $deleteStatus . " AND is_testdata = " . $is_testdata;

                    $select_stmt_following = $this->connection->prepare($select_following);

                    $select_stmt_following->bind_param("ii", $userId, $val['user_id']);

                    if ($select_stmt_following->execute()) {
                        $select_stmt_following->store_result();
                        if ($select_stmt_following->num_rows > 0) {
                            $val['following_status'] = 1;
                        } else {
                            $val['following_status'] = 0;
                        }
                    }
                    if ($val['user_id'] == $userId) {
                        $val['following_status'] = 2;
                    }
                    $postLikes[] = $val;
                }
                $select_stmt_like->close();
            }
            //uasort($postLikes, 'cmp');
            // $i = 0;
            // $tempArr = array();
            // foreach ($postLikes as $value) {
            //     $tempArr[$i] = $value;
            //     $i++;
            // }
            $data['likes'] = $postLikes;
            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Load likes Successfully.";
            $data['load_more'] = count($postLikes) == $limit;
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }
        return $data;
    }

    private function getCategoryChallenges($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $category_id = validateObject($postData, 'category_id', 0);
        $category_id = addslashes($category_id);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;

        // $offset = $offset * $limit;

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);
        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {

            $flag = 1;
            $selectQueryFirstHalf = "SELECT
            p.id AS challenge_id,
            p.user_id,
            p.description,
            p.like_count,
            p.comment_count,
            u.firstname,
            u.lastname,
            p.challenge_name,
            u.profilepic,u.description as user_description,
            p.created_date,
            p.category_id,
            ca.category_name
            FROM " . TABLE_CHALLENGE . " p
            LEFT JOIN " . TABLE_USER . " u ON u.id=p.user_id
            LEFT JOIN " . TABLE_CATEGORY . " ca ON ca.id=p.category_id
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON p.user_id = uf.receiver_id
            WHERE p.is_delete=? AND u.is_delete=? AND p.is_testdata=? AND p.category_id = " . $category_id . "  AND (p.user_id = ? OR (uf.sender_id = ? AND uf.request_status = 'ACCEPT')) AND
            ((uf.is_delete = " . $deleteStatus . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))";

            if ($flag == 1) {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.id DESC LIMIT ? OFFSET ?";
            } else {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.id DESC LIMIT ? OFFSET ?";
            }

            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiiiii", $deleteStatus, $deleteStatus, $isTestData, $userId, $userId, $isTestData, $limit, $offset);

            $post = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt)) {

                        $comment_query = "SELECT pc.id as comment_id,user_id, challenge_id, comment_message,pc.created_date, u.first_name,u.last_name,u.description as user_description FROM " . TABLE_COMMENT_CHALLENGE . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.challenge_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";

                        if ($comment_stmt = $this->connection->prepare($comment_query)) {
                            $comment_stmt->bind_param("is", $val['challenge_id'], $isTestData);
                            $comment_stmt->execute();
                            $comment_stmt->store_result();

                            if ($comment_stmt->num_rows > 0) {
                                while ($val2 = fetch_assoc_all_values($comment_stmt)) {
                                    $val['comment'][] = $val2;
                                }
                            } else {

                            }
                        }

                        $like_query = "SELECT id FROM " . TABLE_LIKE_CHALLENGE . " WHERE challenge_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($like_stmt = $this->connection->prepare($like_query)) {
                            $like_stmt->bind_param("iis", $val['challenge_id'], $userId, $isTestData);
                            $like_stmt->execute();
                            $like_stmt->store_result();

                            if ($like_stmt->num_rows > 0) {
                                $val['like_status'] = 1;
                            } else {
                                $val['like_status'] = 0;
                            }
                        }


                        $this->addMediaObjectToChallenges($val);
                        $post['challenges'][] = $val;
                    }
                } else {
                    $post['challenges'] = array();
                }

                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched challenges.";
            $data[DATA] = $post;
            $data['load_more'] = count($post['challenges']) == $limit;

        }
        return $data;
    }


    private function getRelevantChallenges($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;

        $post_trending = validateObject($postData, 'post_trending', 0);
        $post_ranked = validateObject($postData, 'post_ranked', 0);
        $challenge_recent = validateObject($postData, 'challenge_recent', 0);
        $challenge_follow_user = validateObject($postData, 'challenge_follow_user', 0);


        // $offset = $offset * $limit;

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);
        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {

            $flag = 1;

            $selectQueryFirstHalf = "SELECT
            p.id AS challenge_id,
            p.user_id,
            p.description,
            p.like_count,
            p.comment_count,
            u.firstname,
            u.lastname,
            u.profilepic,u.description as user_description,
            p.created_date,
            p.category_id,
            ca.category_name
            FROM " . TABLE_CHALLENGE . " p
            LEFT JOIN " . TABLE_USER . " u ON u.id=p.user_id
            LEFT JOIN " . TABLE_CATEGORY . " ca ON ca.id=p.category_id
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON p.user_id = uf.receiver_id
            WHERE p.is_delete=? AND u.is_delete=? AND p.is_testdata=?  AND
            ((uf.is_delete = " . $deleteStatus . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))";


            if ($challenge_recent != 0) {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.created_date DESC LIMIT ? OFFSET ?";
            } else if ($challenge_follow_user != 0) {
                $selectQuerySecondHalf = "  AND (p.user_id != " . $userId . " OR (uf.sender_id  = " . $userId . " AND uf.request_status = 'ACCEPT')) GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
            } else {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
            }


            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiii", $deleteStatus, $deleteStatus, $isTestData, $isTestData, $limit, $offset);

            //  echo  $deleteStatus." ". $deleteStatus." ". $isTestData." ". $userId." ". $userId." ".  $isTestData." ".  $limit." ".  $offset;
            $post = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt)) {

                        $comment_query = "SELECT pc.id as comment_id,user_id, challenge_id, comment_message,pc.created_date, u.first_name,u.last_name,u.description as user_description
                                          FROM " . TABLE_COMMENT_CHALLENGE . " as pc
                                          LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id
                                          WHERE pc.challenge_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ?
                                          ORDER BY pc.created_date DESC LIMIT 1";

                        if ($comment_stmt = $this->connection->prepare($comment_query)) {
                            $comment_stmt->bind_param("is", $val['challenge_id'], $isTestData);
                            $comment_stmt->execute();
                            $comment_stmt->store_result();

                            if ($comment_stmt->num_rows > 0) {
                                while ($val2 = fetch_assoc_all_values($comment_stmt)) {
                                    $val['comment'][] = $val2;
                                }
                            } else {

                            }
                        }

                        $like_query = "SELECT id FROM " . TABLE_LIKE_CHALLENGE . " WHERE challenge_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($like_stmt = $this->connection->prepare($like_query)) {
                            $like_stmt->bind_param("iis", $val['challenge_id'], $userId, $isTestData);
                            $like_stmt->execute();
                            $like_stmt->store_result();

                            if ($like_stmt->num_rows > 0) {
                                $val['like_status'] = 1;
                            } else {
                                $val['like_status'] = 0;
                            }
                        }


                        $this->addMediaObjectToChallenges($val);
                        //$post['posts'][] = $val;
                        $post['challenges'][] = $val;
                    }
                } else {
                    $post['challenges'] = array();
                }

                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched challenges.";
            $data[DATA] = $post;
            $data['load_more'] = count($post['challenges']) == $limit;

        }
        return $data;
    }

    function addMediaObjectToChallenges(&$val)
    {
        $isTestData = IS_TEST_DATA;
        $deleteStatus = DELETE_STATUS::NOT_DELETE;
        $selectMedia = "SELECT m.media_name,m.id as media_id,m.media_type,post_type,m.challenge_id
        FROM " . TABLE_MEDIA . " m
        WHERE m.challenge_id=? AND m.is_delete=? AND m.is_testdata=?";
        $stmtMedia = $this->connection->prepare($selectMedia);
        $stmtMedia->bind_param("iii", $val['challenge_id'], $deleteStatus, $isTestData);

        $postMedia = array();
        if ($stmtMedia->execute()) {
            $stmtMedia->store_result();
            while ($valMedia = fetch_assoc_all_values($stmtMedia)) {

                unset($valMedia['is_delete']);
                unset($valMedia['is_testdata']);

                $valMedia['media_url'] = $valMedia['media_name'];

                unset($valMedia['media_name']);
                $postMedia[] = $valMedia;
            }
        }
        $val['challenge_media'] = $postMedia;

        $stmtMedia->close();
    }


}

?>