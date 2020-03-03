<?php
include_once 'FCM.php';

class ChatFunctions
{
    protected $connection;

    public function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {

            case "GetChatList":{
                    return $this->getChatList($postData);
                }
                break;

            case "GetMessagesList":{
                    return $this->getMessagesList($postData);
                }
                break;
            case "LikeUnlikePosts":{
                    return $this->likeUnlikePosts($postData);
                }
                break;
            case "GetPostDetails":{
                    return $this->getPostDetails($postData);
                }
                break;

            case "AddComment":{
                    return $this->addCommentsToFeed($postData);
                }
                break;

            case "SearchUserByHashTag":{
                    return $this->searchUserByHashTag($postData);
                }
                break;
            case "LoadComments":{
                    return $this->loadComments($postData);
                }
                break;

            case "AddFavoritePost":{
                    return $this->addFavoritePost($postData);
                }
                break;

            case "ReportPost":{
                    return $this->reportPost($postData);
                }
                break;

            case "LoadLikes":{
                    return $this->loadLikes($postData);
                }
                break;

            case "GetCategoryPost":{
                    return $this->getCategoryPost($postData);
                }
                break;
            case "GetRelevantPost":{
                    return $this->getRelevantPost($postData);
                }
                break;

            /*Not Used*/
            case "UpdateFeed":{
                    return $this->updateFeed();
                }
                break;

            case "GetFollowUserFeeds":{
                    return $this->getFollowUserFeeds($postData);
                }
                break;

            case "LikeMedias":{
                    return $this->likeMedias($postData);
                }
                break;

            case "LikeComments":{
                    return $this->likeComments($postData);
                }
                break;

            case "DeleteComment":{
                    return $this->deleteComment($postData);
                }
                break;

            case "DeletePost":{
                    return $this->deletePost($postData);
                }
                break;

            case "ListSavedPost":{
                    return $this->listSavedPost($postData);
                }
                break;

            default:{
                    $data[DATA] = 'No Service Found';
                    $data[MESSAGE] = $_REQUEST['Service'];
                    return $data;
                }
                break;
        }
    }

    public function getChatList($userData)
    {
        $status = 2;
        $user_id = validateObject($userData, 'user_id', "");
        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $deleteStatus = DELETE_STATUS::NOT_DELETE;
            $selectQuery = "select * from chat_room where receiver_id=" . $user_id . " OR sender_id=" . $user_id . "";
            $select_stmt = $this->connection->prepare($selectQuery);
            if ($select_stmt->execute()) {
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {
                    while ($conversion_arr = fetch_assoc_all_values($select_stmt)) {
                        $select_unread_counter_query = "SELECT id as message_id FROM chat_messages WHERE is_read = 0  AND receiver_id = " . $user_id . " AND crid = '" . $conversion_arr['id'] . "' AND is_delete=0 ORDER BY id DESC";
                        if ($select_unread_counter_stmt = $this->connection->prepare($select_unread_counter_query)) {
                            $select_unread_counter_stmt->execute();
                            $select_unread_counter_stmt->store_result();
                            $conversion_arr['un_read_counter'] = $select_unread_counter_stmt->num_rows;
                        }
                        if ($user_id != $conversion_arr['sender_id']) {
                            $select_user_query = "SELECT id as userId,name,mobile_number,photo,user_country_code,is_active,is_private FROM users AS u WHERE u.id = " . $conversion_arr['sender_id'] . " AND u.is_delete='0'";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {
                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $conversion_arr['senderDetails'] = $user_post;
                                        $status = SUCCESS;
                                        $errorMsg = "List fetched successfully";
                                    }
                                }
                            }
                            $conversion_arr['user_id'] = (int) $user_id;
                            $conversion_arr['other_user_id'] = $conversion_arr['sender_id'];
                        } else {
                            $select_user_query = "SELECT id as userId,name,mobile_number,photo,user_country_code,is_active,is_private FROM users AS u WHERE u.id = " . $conversion_arr['receiver_id'] . " AND u.is_delete='0'";
                            if ($select_user_stmt = $this->connection->prepare($select_user_query)) {

                                $select_user_stmt->execute();
                                $select_user_stmt->store_result();

                                if ($select_user_stmt->num_rows > 0) {
                                    while ($user_post = fetch_assoc_all_values($select_user_stmt)) {
                                        $conversion_arr['senderDetails'] = $user_post;
                                        $status = SUCCESS;
                                        $errorMsg = "List fetched successfully";
                                    }
                                }
                            }
                            $conversion_arr['user_id'] = (int) $user_id;
                            $conversion_arr['other_user_id'] = $conversion_arr['receiver_id'];
                        }
                        $conversion_arr['conversion_id'] = $conversion_arr['id'];
                        unset($conversion_arr['id']);
                        $user_arr[] = $conversion_arr;
                    }
                } else {
                    $user_arr = [];
                    $status = SUCCESS;
                    $errorMsg = "List fetched sucscessfully";
                }
            } else {
                $errorMsg = $select_stmt->error;
            }
        }
        $data['status'] = ($status > 1) ? FAILED : SUCCESS;
        $data['message'] = $errorMsg;
        $data['data'] = $user_arr;
        return $data;
    }

    public function getMessagesList($userData)
    {

        $status = 2;
        $sender_id = validateObject($userData, 'sender_id', "");
        $receiver_id = validateObject($userData, 'receiver_id', "");
        $crid = validateObject($userData, 'crid', "");

        if ($sender_id == "" || $receiver_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $deleteStatus = DELETE_STATUS::NOT_DELETE;
            $select_crid_Query = "select id from chat_room where (sender_id=" . $sender_id . " and receiver_id=" . $receiver_id . ") OR (sender_id=" . $receiver_id . " and receiver_id=" . $sender_id . ") ";
            $selectCrid_stmt = $this->connection->prepare($select_crid_Query);
            if ($selectCrid_stmt->execute()) {
                $selectCrid_stmt->store_result();
                if ($selectCrid_stmt->num_rows > 0) {
                    while ($room = fetch_assoc_all_values($selectCrid_stmt)) {
                        $crid = $room['id'];
                        $select_Query = "select * from chat_messages where crid=" . $crid . " ";
                        $select_stmt = $this->connection->prepare($select_Query);
                        if ($select_stmt->execute()) {
                            $select_stmt->store_result();
                            if ($select_stmt->num_rows > 0) {
                                while ($conversion_arr = fetch_assoc_all_values($select_stmt)) {
                                    $user_arr[] = $conversion_arr;
                                    $status = SUCCESS;
                                    $errorMsg = "List fetched successfully";
                                }
                            } else {
                                $user_arr = [];
                                $status = SUCCESS;
                                $errorMsg = "List fetched successfully";
                            }
                        } else {
                            $status = FAILED;
                            $errorMsg = $select_stmt->error;
                        }
                        $select_stmt->close();
                    }
                } else {
                    $user_arr = [];
                    $status = SUCCESS;
                    $errorMsg = "List fetched successfully";
                }

            } else {
                $status = FAILED;
                $errorMsg = $selectCrid_stmt->error;
            }
            $selectCrid_stmt->close();
        }
        $data['status'] = ($status > 1) ? FAILED : SUCCESS;
        $data['message'] = $errorMsg;
        $data['data'] = $user_arr;
        return $data;
    }

    public function searchUserByHashTag($userData)
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
//            $select_query = "Select
            //            u.id as user_id,
            //            u.firstname,
            //            u.lastname,
            //            u.post_count,
            //            u.username,
            //            u.follower_count,
            //            u.following_count,
            //            u.profilepic,u.description as user_description
            //            from " . TABLE_USER . " AS u
            //           /* LEFT JOIN " . TABLE_USER_BLOCKED . " AS bu ON(u.id = bu.other_user_id AND bu.user_id = ?)*/
            //            where u.id != ? AND u.firstname LIKE CONCAT('%',?,'%') OR u.lastname LIKE CONCAT('%',?,'%') OR CONCAT( u.firstname,  ' ', u.lastname ) LIKE CONCAT('%',?,'%') AND u.is_delete='" . $deleteStatus . "' AND u.is_testdata = ?/* AND bu.id IS NULL*/ LIMIT 50";

            $select_query = "
                Select * from " . TABLE_POST . "
                where description like CONCAT('%',?,'%') LIMIT 50
            ";
            $flag = 1;

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("s", $search_txt);
//                $select_stmt->bind_param("iiiiiiii", $deleteStatus, $deleteStatus, $is_testdata, $user_id, $user_id, $is_testdata, $limit, $offset);
                $select_stmt->execute();
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {

                    while ($user_arr = fetch_assoc_all_values($select_stmt)) {

                        $select_media = "SELECT id,media_type,post_type,media_name FROM " . TABLE_MEDIA . " where post_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
                        $stmt_media = $this->connection->prepare($select_media);
                        $stmt_media->bind_param("i", $user_arr['id']);
                        if ($stmt_media->execute()) {
                            $stmt_media->store_result();
                            $i = 0;
                            while ($val_media = fetch_assoc_all_values($stmt_media)) {
                                $user_arr['media']['media_id'] = $val_media['id'];
                                $user_arr['media']['post_image'] = $val_media['media_name'];
//                                $user_arr['media']['description'] = $val_media['description'];
                                $user_arr['media']['type'] = $val_media['media_type'];
                                $user_arr['media']['post_type'] = $val_media['post_type'];
                                $i++;
                            }
                        }

                        $select_user = "SELECT * FROM " . TABLE_USER . " where id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $is_testdata . "";
                        $stmt_user = $this->connection->prepare($select_user);
                        $stmt_user->bind_param("i", $user_arr['user_id']);

                        if ($stmt_user->execute()) {
                            $stmt_user->store_result();
                            $i = 0;
                            while ($val_user = fetch_assoc_all_values($stmt_user)) {
                                $user_arr['createdBy']['profilepic'] = $val_user['profilepic'];
                                $user_arr['createdBy']['firstname'] = $val_user['firstname'];
                                $user_arr['createdBy']['lastname'] = $val_user['lastname'];
                                $user_arr['createdBy']['username'] = $val_user['username'];
                                $user_arr['createdBy']['email'] = $val_user['email'];
                                $user_arr['createdBy']['user_id'] = $val_user['id'];
                                $i++;
                            }
                        }

//                        $select_following = "SELECT sender_id,request_status FROM " . TABLE_USER_FOLLOWERS . " WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) AND (request_status = 'ACCEPT' OR request_status = 'PENDING') AND is_delete = " . $deleteStatus . " AND is_testdata = " . $is_testdata;
                        //
                        //                        $select_stmt_following = $this->connection->prepare($select_following);
                        //
                        //                        $select_stmt_following->bind_param("iiii", $user_id, $user_arr['user_id'], $user_arr['user_id'], $user_id);
                        //
                        //                        if ($select_stmt_following->execute()) {
                        //                            $select_stmt_following->store_result();
                        //                            if ($select_stmt_following->num_rows > 0) {
                        //                                while ($stmt_arr = fetch_assoc_all_values($select_stmt_following)) {
                        //
                        //                                    if ($stmt_arr['request_status'] == 'PENDING') {
                        //                                        $user_arr['following_status'] = 3;
                        //                                    } else {
                        //                                        $user_arr['following_status'] = 1;
                        //                                    }
                        //                                }
                        //
                        //                            } else {
                        //                                $user_arr['following_status'] = 0;
                        //                            }
                        //                        }
                        //                        if ($user_arr['user_id'] == $user_id) {
                        //                            $user_arr['following_status'] = 2;
                        //                        }

                        $posts['post_listing'][] = $user_arr;
                        $errorMsg = "Post listing successfully.";
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

    public function addPost()
    {

        $userId = validatePostValue('user_id', 0);

        $categoryId = validatePostValue('category_id', 0);

        $description = validatePostValue('description', "");

        $isTestData = validatePostValue('is_testdata', IS_TEST_DATA);

        $postType = validatePostValue('post_type', IS_TEST_DATA);

        $keyMedia = 'post_media';
        if (!empty($_FILES[$keyMedia]['name'])) {
            $mediaCount = count(array_filter($_FILES[$keyMedia]['name']));
        } else {
            $mediaCount = 0;
        }

        $hasMedia = $mediaCount > 0 ? 1 : 0;

        if ($userId == 0 || $hasMedia == 0) {

            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;

        } else {

            $deleteStatus = DELETE_STATUS::NOT_DELETE;

            $isQuerySuccess = false;
            $this->connection->begin_transaction();
            $message = "";
            $feedId = 0;
            $createdDate = getDefaultDate();

            $feedIdArr = array();

            $insertQuery = "INSERT INTO " . TABLE_POST . "(user_id, category_id, description, created_date, is_testdata) VALUES(?,?,?,?,?)";

            if ($insertStmt = $this->connection->prepare($insertQuery)) {

                $insertStmt->bind_param('iissi', $userId, $categoryId, $description, $createdDate, $isTestData);
                if ($insertStmt->execute()) {
                    $feedId = $insertStmt->insert_id;

                    $feedIdArr[] = $feedId;

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

            if ($hasMedia > 0 && $isQuerySuccess) {
                $this->createFeedMediaDirectory();

                $imageInfo = array();
                for ($i = 0; $i < $mediaCount; $i++) {
                    if ($isQuerySuccess) {
                        $imageInfo[] = $this->uploadMediasToFeed($i, $feedIdArr, $userId, $isQuerySuccess, $keyMedia, $postType);
                    } else {
                        $message = $imageInfo[$i - 1][MESSAGE];
                        break;
                    }
                }
            }

            if ($isQuerySuccess) {

                $this->connection->commit();
                $message = "Feed posted successfully.";
                $feedObj = (object) array('user_id' => $userId, 'post_id' => $feedId);
                $feedDetails = $this->getPostDetails($feedObj);
                $data['home_screen'] = $feedDetails['home_screen'];
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

    public function getPostDetails($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $postId = validateObject($postData, 'post_id', 0);

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        if ($userId == 0 || $postId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {

            $deleteStatus = DELETE_STATUS::NOT_DELETE;

            $selectQuery = "SELECT f.id, f.user_id,f.description, f.like_count , f.comment_count,
                        u.firstname,u.lastname,u.profilepic,u.description as user_description,f.created_date
                        FROM " . TABLE_POST . " f
                        LEFT JOIN " . TABLE_USER . " u ON f.user_id=u.id
                        WHERE f.id=? AND f.is_delete=? AND u.is_delete=? AND f.is_testdata=? AND u.is_testdata=? GROUP BY f.id";

            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiii", $postId, $deleteStatus, $deleteStatus, $isTestData, $isTestData);

            $post = array();
            if ($stmt->execute()) {
                $stmt->store_result();

                while ($val = fetch_assoc_all_values($stmt)) {
                    $val['profilepic'] = ($val['profilepic'] ? $val['profilepic'] : "");

                    $select_media = "SELECT id,media_type,post_type,media_name FROM " . TABLE_MEDIA . " where post_id= ? AND is_delete=" . $deleteStatus . " AND is_testdata = " . $isTestData . "";
                    $stmt_media = $this->connection->prepare($select_media);
                    $stmt_media->bind_param("i", $postId);
                    if ($stmt_media->execute()) {
                        $stmt_media->store_result();
                        $i = 0;
                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
                            $val['media'][$i]['media_id'] = $val_media['id'];
                            $val['media'][$i]['post_image'] = $val_media['media_name'];
                            $val['media'][$i]['type'] = $val_media['media_type'];
                            $val['media'][$i]['post_type'] = $val_media['post_type'];
                            $i++;
                        }
                    }
                    $fav_query = "SELECT id FROM " . TABLE_POST_FAVORITE . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                    if ($fav_stmt = $this->connection->prepare($fav_query)) {
                        $fav_stmt->bind_param("iis", $val['id'], $userId, $isTestData);
                        $fav_stmt->execute();
                        $fav_stmt->store_result();

                        if ($fav_stmt->num_rows > 0) {
                            $val['isFavourite'] = 1;
                        } else {
                            $val['isFavourite'] = 0;
                        }
                    }
                    $like_query = "SELECT id FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                    if ($like_stmt = $this->connection->prepare($like_query)) {
                        $like_stmt->bind_param("iis", $val['id'], $userId, $isTestData);
                        $like_stmt->execute();
                        $like_stmt->store_result();

                        if ($like_stmt->num_rows > 0) {
                            $val['like_status'] = 1;
                        } else {
                            $val['like_status'] = 0;
                        }
                    }

                    $post[] = $val;
                }
                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data['home_screen'] = $post;

        }
        return $data;
    }

    public function updateFeed()
    {
        $feedId = validatePostValue('feed_id', 0);
        $userId = validatePostValue('user_id', 0);

        $feedText = validatePostValue('feed_text', "");
        $feedText = addslashes($feedText);

        $mediaRemoveId = validatePostValue('product_remove_media_id', "");

        $keyMedia = 'feed_media';
        if (!empty($_FILES[$keyMedia]['name'])) {
            $createMediaCount = count(array_filter($_FILES[$keyMedia]['name']));
        } else {
            $createMediaCount = 0;
        }
        $hasMedia = validatePostValue('has_media', 0);

        if ($userId > 0) {

            $this->connection->begin_transaction();
            $isQuerySuccess = true;

            $updateQuery = "UPDATE " . TABLE_FEED . " SET feed_text=?, has_media=? WHERE id=? AND user_id=?";
            $updateStmt = $this->connection->prepare($updateQuery);

            $updateStmt->bind_param('siii', $feedText, $hasMedia, $feedId, $userId);

            if ($updateStmt->execute()) {
                $updateStmt->close();

                if ($createMediaCount > 0) {

                    $this->createFeedMediaDirectory();

                    $imageInfo = array();
                    for ($i = 0; $i < $createMediaCount; $i++) {
                        if ($isQuerySuccess) {
                            $imageInfo[] = $this->uploadMediasToFeed($i, $feedText, $userId, $isQuerySuccess, $keyMedia);
                        } else {
                            $data[MESSAGE] = $imageInfo[$i - 1][MESSAGE];
                            break;
                        }
                    }
                }

                if ($isQuerySuccess && strlen($mediaRemoveId) > 0) {

                    $deleteStatus = DELETE_STATUS::NOT_DELETE;
                    $selectMedia = "SELECT media_name,media_type,id as media_id  FROM " . TABLE_FEED_MEDIA . " WHERE id IN (" . $mediaRemoveId . ") AND is_delete=?";
                    $stmtMedia = $this->connection->prepare($selectMedia);
                    $stmtMedia->bind_param("i", $deleteStatus);

                    if ($stmtMedia->execute()) {
                        $stmtMedia->store_result();
                        $isDelete = DELETE_STATUS::IS_DELETE;

                        $removeMediaQuery = "UPDATE " . TABLE_FEED_MEDIA . " SET is_delete=? WHERE id IN (" . $mediaRemoveId . ")";
                        $removeStmt = $this->connection->prepare($removeMediaQuery);

                        $removeStmt->bind_param('i', $isDelete);

                        if ($removeStmt->execute()) {
                            $removeStmt->close();
                            while ($valMedia = fetch_assoc_all_values($stmtMedia)) {

                                //delete previous image
                                $uploadDirImg = $this->getMediaPrefixLocalPathBasedOnType($valMedia['media_type']);
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
            }

            if ($isQuerySuccess) {
                $this->connection->commit();
                $data[MESSAGE] = "Feed updated successfully";

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

    public function uploadMediasToFeed($index, $feedIdArr, $userId, &$isQuerySuccess, $keyMedia, $postType)
    {
        $status = FAILED;
        $posts = array();

        if ($_FILES[$keyMedia]['name'][$index] != '') {

            if ($_FILES["post_media"]["error"][$index] > 0) {

                $posts['media_name'] = null;
                $errorMsg = fileUploadCodeToMessage($_FILES["post_media"]["error"][$index]);

            } else {
                $ext = pathinfo($_FILES[$keyMedia]['name'][$index], PATHINFO_EXTENSION);
                $file = $milliseconds = round(microtime(true) * 1000) . generateRandomString(7) . "." . $ext;

                $mime = $_FILES[$keyMedia]['type'][$index];

                if (strstr($mime, "image/")) {
                    $feedMediaType = FEED_MEDIA::IMAGE;
                } else if (strstr($mime, "video/")) {
                    $feedMediaType = FEED_MEDIA::VIDEO;

                } else if (strstr($mime, "audio/")) {
                    $feedMediaType = FEED_MEDIA::AUDIO;

                } else {
                    $status = FAILED;
                    $errorMsg = "Only images are allowed to upload.";
                    $posts['media_name'] = null;
                    $isQuerySuccess = false;
                    $data[STATUS] = $status;
                    $data[MESSAGE] = $errorMsg;
                    $data[DATA] = $posts;
                    return $data;
                }
                $uploadFile = $this->getMediaPrefixLocalPathBasedOnType($feedMediaType) . $file;

                if (move_uploaded_file($_FILES[$keyMedia]['tmp_name'][$index], $uploadFile)) {

                    if ($feedMediaType == FEED_MEDIA::IMAGE) {
                        createThumbnailImage($uploadFile, $file);
                        //createThumbnailImage($uploadFile,$file);
                    } else if ($feedMediaType = FEED_MEDIA::VIDEO) {
                        createVideoThumbnail($uploadFile, $file, SERVER_THUMB_IMAGE);
                    }

                    $thumbImgName = null;

                } else {
                    $status = FAILED;
                    $errorMsg = "Failed to upload image on server.";
                }
            }

            // echo $feedMediaType;

            //print_r($_FILES);

            $testData = IS_TEST_DATA;

            $createdDate = getDefaultDate();

            if ($postType == POST_TYPE::POST) {
                $insertQuery = "INSERT INTO " . TABLE_MEDIA . "(post_id,media_name,post_type,media_type,created_date,is_testdata)
                VALUES(?,?,?,?,?,?)";
            } else {
                $insertQuery = "INSERT INTO " . TABLE_MEDIA . "(challenge_id,media_name,post_type,media_type,created_date,is_testdata)
                VALUES(?,?,?,?,?,?)";
            }

            foreach ($feedIdArr as $key => $feedId) {
                $insertStmt = $this->connection->prepare($insertQuery);
                $insertStmt->bind_param('issssi', $feedId, $file, $postType, $feedMediaType, $createdDate, $testData);
                //echo $insertQuery." ".$feedId." ". $file." ".$postType ." ".$feedMediaType." ". $createdDate." ". $testData;
                if ($insertStmt->execute()) {
                    $insertStmt->close();
                    $errorMsg = "Successfully changed.";
                    $status = SUCCESS;
                } else {
                    $status = FAILED;
                    $errorMsg = "Error in inserting image.";

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

    public function getAllPosts($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $todayTrend = validateObject($postData, 'today_trend', 0);
        $weekTrend = validateObject($postData, 'week_trend', 0);
        $monthTrend = validateObject($postData, 'month_trend', 0);
        $quaterTrend = validateObject($postData, 'quater_trend', 0);
        $yearTrend = validateObject($postData, 'year_trend', 0);

        $loadingType = validateObject($postData, 'loading_type', 0);
        $lastPostId = validateObject($postData, 'last_post_id', 0);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;

        $offset = $offset * $limit;

        $isTestData = validateObject($postData, 'is_testdata', IS_TEST_DATA);
        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {

            $where = "";
            $flag = 0;
//            if ($todayTrend != 0) {
            //                $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . ") AND p.created_date >= DATE(NOW()) - INTERVAL 1 DAY";
            //
            //                $flag = 1;
            //            }
            //
            //            if ($weekTrend != 0) {
            //                $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . ") AND p.created_date >= DATE(NOW()) - INTERVAL 7 DAY";
            //                $flag = 1;
            //            }
            //
            //            if ($monthTrend != 0) {
            //                $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . ") AND p.created_date >= DATE(NOW()) - INTERVAL 30 DAY";
            //                $flag = 1;
            //            }
            //
            //            if ($quaterTrend != 0) {
            //                $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . ") AND p.created_date >= DATE(NOW()) - INTERVAL 90 DAY";
            //                $flag = 1;
            //            }
            //
            //            if ($yearTrend != 0) {
            //                $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . " ) AND p.created_date >= DATE(NOW()) - INTERVAL 365 DAY";
            //                $flag = 1;
            //            }

//           if($is_search)
            //           {
            //               $is_relevance
            //           }

            $where .= " AND p.like_count <= (SELECT MAX(like_count) FROM " . TABLE_POST . " WHERE is_delete=" . $deleteStatus . " AND is_testdata=" . $isTestData . " ) AND p.created_date >= DATE(NOW()) - INTERVAL 365 DAY";
            $flag = 1;

            $selectQueryFirstHalf = "SELECT
            p.id AS post_id,
            p.user_id,
            p.description,
            p.like_count,
            p.comment_count,
            u.firstname,
            u.lastname,
            u.profilepic,
            u.description as user_description,
            p.created_date
            FROM " . TABLE_POST . " p
            LEFT JOIN " . TABLE_USER . " u ON u.id=p.user_id
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON p.user_id = uf.receiver_id
            WHERE p.is_delete=? AND u.is_delete=? AND p.is_testdata=? AND (p.user_id = ? OR (uf.sender_id = ? AND uf.request_status = 'ACCEPT')) AND
            ((uf.is_delete = " . $deleteStatus . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))
            " . $where;

            if ($flag == 1) {
//                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.id DESC LIMIT ? OFFSET ?";
            } else {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.id DESC LIMIT ? OFFSET ?";

            }

            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiiiii", $deleteStatus, $deleteStatus, $isTestData, $userId, $userId, $isTestData, $limit, $offset);

            $post['posts'] = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt)) {

                        $comment_query = "SELECT pc.id as comment_id,user_id, post_id, comment_message,pc.created_date, u.first_name,u.last_name,u.description as user_description FROM " . TABLE_COMMENTS . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.post_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";

                        if ($comment_stmt = $this->connection->prepare($comment_query)) {
                            $comment_stmt->bind_param("is", $val['post_id'], $isTestData);
                            $comment_stmt->execute();
                            $comment_stmt->store_result();

                            if ($comment_stmt->num_rows > 0) {
                                while ($val2 = fetch_assoc_all_values($comment_stmt)) {
                                    $val['comment'][] = $val2;
                                }
                            } else {

                            }
                        }

                        $like_query = "SELECT id FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($like_stmt = $this->connection->prepare($like_query)) {
                            $like_stmt->bind_param("iis", $val['post_id'], $userId, $isTestData);
                            $like_stmt->execute();
                            $like_stmt->store_result();

                            if ($like_stmt->num_rows > 0) {
                                $val['like_status'] = 1;
                            } else {
                                $val['like_status'] = 0;
                            }
                        }

                        $fav_query = "SELECT id FROM " . TABLE_POST_FAVORITE . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($fav_stmt = $this->connection->prepare($fav_query)) {
                            $fav_stmt->bind_param("iis", $val['post_id'], $userId, $isTestData);
                            $fav_stmt->execute();
                            $fav_stmt->store_result();

                            if ($fav_stmt->num_rows > 0) {
                                $val['isFavourite'] = 1;
                            } else {
                                $val['isFavourite'] = 0;
                            }
                        }

                        $val['profilepic'] = $val['profilepic'];
                        $this->addMediaObjectToFeed($val);
                        //$post['posts'][] = $val;
                        $post['posts'][] = $val;
                    }
                } else {
                    $post['posts'] = array();
                }

                $stmt->close();
            }
            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data[DATA] = $post;
            $data['load_more'] = count($post['posts']) == $limit;

        }
        return $data;
    }

    public function loadComments($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $postId = validateObject($postData, 'post_id', 0);

        $lastCommentId = validateObject($postData, 'last_comment_id', 0);

        $loadingType = validateObject($postData, 'loading_type', 0);
        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($postData, 'offset', 0);
        $limit = 500;

        $offset = $offset * $limit;

        $deleteStatus = DELETE_STATUS::NOT_DELETE;
        if ($userId > 0 && $postId > 0) {

            $selectQueryFirstHalf = "SELECT np.id as comment_id, np.user_id, np.post_id, np.comment_message, np.created_date,
                                           u.firstname, u.lastname, u.profilepic,u.description as user_description
                                          FROM " . TABLE_COMMENTS . " np
                                          LEFT JOIN " . TABLE_USER . " u ON u.id=np.user_id
                                          WHERE np.post_id=? AND np.is_delete=? AND np.is_testdata=?";
//            if ($lastCommentId == 0) {
            //                $selectQuerySecondHalf = " ORDER BY np.id DESC LIMIT ?";
            //                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            //                $stmt = $this->connection->prepare($selectQuery);
            //                $stmt->bind_param("iiii", $postId, $deleteStatus, $is_testdata, $limit);
            //            } else {
            //                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
            //                    $selectQuerySecondHalf = " AND np.comment_id < ? ORDER BY np.id DESC LIMIT ?";
            //                } else {
            $selectQuerySecondHalf = " ORDER BY np.id DESC LIMIT ? OFFSET ?";
//                }

            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiii", $postId, $deleteStatus, $is_testdata, $limit, $offset);
//            }

            $postComments = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                while ($val = fetch_assoc_all_values($stmt)) {
                    $postComments[] = $val;
                }
                $stmt->close();
            }

            $data['comments'] = $postComments;
            $data[STATUS] = SUCCESS;
            $data['load_more'] = count($postComments) == $limit;
            $data[MESSAGE] = "Load comment Successfully.";
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }
        return $data;
    }

    public function getFollowUserFeeds($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $loadingType = validateObject($postData, 'loading_type', 0);
        $lastFeedId = validateObject($postData, 'last_feed_id', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        if ($userId == 0) {
            $data[MESSAGE] = DEV_ERROR;
            $data[STATUS] = FAILED;
            return $data;
        } else {
            $is_delete = DELETE_STATUS::NOT_DELETE;

            //$selectQueryFirstHalf = "SELECT f.post_id as feed_id,f.post_description as feed_text,f.post_like_count as no_of_likes,f.post_comment_count as no_of_comment, m.media_name as profile_pic, f.user_id,u.first_name, u.last_name,f.created_date FROM ".TABLE_FEED." as f LEFT JOIN ".TABLE_USER." as u ON f.user_id = u.user_id LEFT JOIN ".TABLE_MEDIA." as m ON f.user_id = m.post_id LEFT JOIN ".TABLE_USER_FOLLOWERS." as uf ON f.user_id=uf.receiver_id WHERE (uf.sender_id = ? OR u.user_id = ?) AND uf.request_status = 'ACCEPT' AND m.post_type = 1 AND f.is_delete = ".$is_delete." AND m.is_delete = ".$is_delete." AND u.is_delete = ".$is_delete." AND group_id = 0  AND f.is_testdata = ? AND m.is_testdata = ? AND u.is_testdata = ? AND ((uf.is_delete = ".$is_delete." AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))";

            $selectQueryFirstHalf = "SELECT
            f.post_id as feed_id,
            f.post_description as feed_text,
            f.post_like_count as no_of_likes,
            f.post_comment_count as no_of_comment,
            f.user_id,
            f.created_date,
            m.media_name as profile_pic,
            u.first_name,
            u.last_name
            ,u.description as user_description
            FROM " . TABLE_FEED . " AS f
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON f.user_id = uf.receiver_id
            LEFT JOIN " . TABLE_USER . " AS u ON f.user_id=u.user_id
            LEFT JOIN " . TABLE_MEDIA . " AS m ON f.user_id=m.post_id
            WHERE m.post_type = 1 AND f.is_delete = " . $is_delete . "  AND f.is_testdata = ? AND ((uf.is_delete = " . $is_delete . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL)) AND u.is_delete = " . $is_delete . " AND u.is_testdata = ? AND m.is_delete = " . $is_delete . " AND m.is_testdata = ? AND (f.user_id = ? OR (uf.sender_id = ? AND uf.request_status = 'ACCEPT'))";

            //AND (f.post_type = 'NORMAL' OR f.post_type = 'BOTH')

            if ($lastFeedId == 0) {
                $selectQuerySecondHalf = "  GROUP BY f.post_id ORDER BY f.post_id DESC LIMIT ?";
                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
                $stmt = $this->connection->prepare($selectQuery);
                //$stmt->bind_param("iiiiiii", $userId, $userId, $is_testdata, $is_testdata, $is_testdata, $is_testdata, $limit);
                $stmt->bind_param("iiiiiii", $is_testdata, $is_testdata, $is_testdata, $is_testdata, $userId, $userId, $limit);
            } else {
                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
                    $selectQuerySecondHalf = " AND f.post_id < ? GROUP BY f.post_id ORDER BY f.post_id DESC LIMIT ?";
                } else {
                    $selectQuerySecondHalf = " AND f.post_id > ? GROUP BY f.post_id ORDER BY f.post_id DESC LIMIT ?";
                }

                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
                $stmt = $this->connection->prepare($selectQuery);
                //$stmt->bind_param("iiiiiiiii", $userId, $userId,  $is_testdata, $is_testdata, $is_testdata, $is_testdata, $userId, $lastFeedId, $limit);
                $stmt->bind_param("iiiiiiii", $is_testdata, $is_testdata, $is_testdata, $is_testdata, $userId, $userId, $lastFeedId, $limit);
            }

            $post = array();

            if ($stmt->execute()) {
                $stmt->store_result();

                while ($val = fetch_assoc_all_values($stmt)) {
                    $val['profile_pic'] = ($val['profile_pic'] ? $val['profile_pic'] : "");

                    $select_media = "SELECT media_id,media_type,media_name FROM " . TABLE_MEDIA . " where post_id= ? AND post_type = 0 AND is_delete=" . $is_delete . " AND is_testdata = " . $is_testdata . " ORDER BY created_date DESC LIMIT 1";
                    $stmt_media = $this->connection->prepare($select_media);
                    $stmt_media->bind_param("i", $val['feed_id']);
                    if ($stmt_media->execute()) {
                        $stmt_media->store_result();
                        $i = 0;
                        while ($val_media = fetch_assoc_all_values($stmt_media)) {
                            $val['media'][$i]['media_id'] = $val_media['media_id'];
                            $val['media'][$i]['feed_image'] = $val_media['media_name'];
                            $val['media'][$i]['type'] = $val_media['media_type'];
                            $i++;
                        }
                    }

                    $select_like = "SELECT like_id FROM " . TABLE_LIKES . " where post_id= ? AND user_id = ? AND is_delete=" . $is_delete . " AND is_testdata = " . $is_testdata . "";
                    $stmt_like = $this->connection->prepare($select_like);
                    $stmt_like->bind_param("ii", $val['feed_id'], $userId);
                    if ($stmt_like->execute()) {
                        $stmt_like->store_result();
                        if ($stmt_like->num_rows > 0) {
                            $val['like_status'] = 1;
                        } else {
                            $val['like_status'] = 0;
                        }
                    }

                    $select_saved = "SELECT save_post_id FROM " . TABLE_SAVE_POST . " where post_id= ? AND user_id = ? AND is_delete=" . $is_delete . " AND is_testdata = " . $is_testdata . "";
                    $stmt_saved = $this->connection->prepare($select_saved);
                    $stmt_saved->bind_param("ii", $val['feed_id'], $userId);
                    if ($stmt_saved->execute()) {
                        $stmt_saved->store_result();
                        if ($stmt_saved->num_rows > 0) {
                            $val['saved_status'] = 1;
                        } else {
                            $val['saved_status'] = 0;
                        }
                    }

                    $post[] = $val;
                }
                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data['home_screen'] = $post;

        }

        return $data;
    }

    public function addMediaObjectToFeed(&$val)
    {
        $isTestData = IS_TEST_DATA;
        $deleteStatus = DELETE_STATUS::NOT_DELETE;
        $selectMedia = "SELECT m.media_name,m.id as media_id,m.media_type,post_type
        FROM " . TABLE_MEDIA . " m
        WHERE m.post_id=? AND m.is_delete=? AND m.is_testdata=?";
        $stmtMedia = $this->connection->prepare($selectMedia);
        $stmtMedia->bind_param("iii", $val['post_id'], $deleteStatus, $isTestData);

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
        $val['post_media'] = $postMedia;

        $stmtMedia->close();
    }

    public function likeUnlikePosts($postData)
    {

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $userId = validateObject($postData, 'user_id', 0);

        $postId = validateObject($postData, 'post_id', 0);

        $createdDate = getDefaultDate();

        $is_delete = DELETE_STATUS::NOT_DELETE;

        $posts = array();

        if ($userId > 0 && $postId > 0) {

            $select_like_query = "SELECT * FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";
            $select_like_stmt = $this->connection->prepare($select_like_query);
            $select_like_stmt->bind_param("iii", $postId, $userId, $is_testdata);
            if ($select_like_stmt->execute()) {
                $select_like_stmt->store_result();
                if ($select_like_stmt->num_rows > 0) {
                    $tbl_like = fetch_assoc_all_values($select_like_stmt);
                    $delete_query = "DELETE FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";
                    $delete_stmt = $this->connection->prepare($delete_query);
                    $delete_stmt->bind_param("iii", $postId, $userId, $is_testdata);
                    $delete_stmt->execute();

                    $select_query = "SELECT user_id,like_count FROM " . TABLE_POST . " WHERE id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";

                    $select_stmt = $this->connection->prepare($select_query);
                    $select_stmt->bind_param("ii", $postId, $is_testdata);
                    if ($select_stmt->execute()) {
                        $select_stmt->store_result();
                        $status = 1;

                        $like_arr = fetch_assoc_all_values($select_stmt);

                        $notificationType = NOTIFICATION_TYPE_LIKE;
                        $notificationMsg = "NOTIFICATION_LIKE_ON_FEED";
                        $notificationData = '{"post_id":"' . $postId . '","like_id":"' . $tbl_like['id'] . '","message":"' . $notificationMsg . '"}';
                        $arrayNotification = array();

                        $notificationObj['sender_id'] = $userId;
                        $notificationObj['receiver_id'] = $like_arr['user_id'];
                        $notificationObj['notification_type_id'] = $postId;
                        $notificationObj['notification_type'] = $notificationType;
                        $notificationObj['is_testdata'] = $is_testdata;
                        // $notificationObj['notification_text'] = $notificationData;

                        $arrayNotification[] = $notificationObj;
                        // print_r($arrayNotification);

                        if ($userId != $like_arr['user_id']) {
                            removeEntryInNotificationTable($this->connection, $arrayNotification);
                        }

                        $posts['post_id'] = $postId;
                        $posts['like_count'] = $like_arr['like_count'];
                        $posts['like_status'] = 0;

                        $errorMsg = "Un-like post Successfully";
                    }

                } else {
                    $insert_query = "INSERT INTO " . TABLE_LIKES . " (post_id, user_id, created_date, is_testdata) VALUES(?,?,?,?)";
                    $insertStmt = $this->connection->prepare($insert_query);
                    $insertStmt->bind_param('iisi', $postId, $userId, $createdDate, $is_testdata);

                    if ($insertStmt->execute()) {
                        $insertStmt->close();
                        $likeId = mysqli_insert_id($this->connection);

                        $select_query = "SELECT user_id,like_count FROM " . TABLE_POST . " WHERE id = ? AND is_delete='" . $is_delete . "' AND is_testdata = ?";

                        $select_stmt = $this->connection->prepare($select_query);
                        $select_stmt->bind_param("ii", $postId, $is_testdata);
                        if ($select_stmt->execute()) {
                            $status = 1;

                            $select_stmt->store_result();
                            $like_arr = fetch_assoc_all_values($select_stmt);

                            $notificationType = NOTIFICATION_TYPE_LIKE;
                            $notificationMsg = "NOTIFICATION_LIKE_ON_FEED";
                            $notificationData = '{"post_id":"' . $postId . '","like_id":"' . $likeId . '","message":"' . $notificationMsg . '"}';
                            $arrayNotification = array();

                            $notificationObj['sender_id'] = (int) $userId;
                            $notificationObj['receiver_id'] = (int) $like_arr['user_id'];
                            $notificationObj['notification_type_id'] = (int) $postId;
                            $notificationObj['notification_type'] = $notificationType;
                            $notificationObj['is_testdata'] = (int) $is_testdata;
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
                                            $dataNotiArr['post_id'] = $postId;
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

                            $posts['post_id'] = $postId;
                            $posts['like_count'] = $like_arr['like_count'];
                            $posts['like_status'] = 1;

                            $errorMsg = "Like post Successfully";
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

    public function addFavoritePost($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $postId = validateObject($postData, 'post_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', 0);
        $getCurrentDate = getDefaultDate();

        if ($userId == 0 || $postId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $status = 2;
            $posts = array();
            $is_delete = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $select_query = "SELECT post_id FROM " . TABLE_POST_FAVORITE . " WHERE user_id = ? AND post_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("iii", $userId, $postId, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();

                if ($select_stmt->num_rows > 0) {
                    $delete_query = "DELETE FROM " . TABLE_POST_FAVORITE . " WHERE user_id = ? AND post_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";
                    if ($delete_stmt = $this->connection->prepare($delete_query)) {
                        $delete_stmt->bind_param("iii", $userId, $postId, $is_testdata);
                        if ($delete_stmt->execute()) {

                            $posts['post_id'] = (int) $postId;
                            $posts['saved_status'] = 0;
                            $status = 1;
                            $errorMsg = "Post un-saved successfully";
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
                    $insert_query = "INSERT INTO " . TABLE_POST_FAVORITE . " (user_id, post_id,created_date,is_testdata) VALUES(?,?,?,?)";
                    if ($insert_stmt = $this->connection->prepare($insert_query)) {
                        $insert_stmt->bind_param("iisi", $userId, $postId, $getCurrentDate, $is_testdata);
                        if ($insert_stmt->execute()) {
                            $inserted_save_post_id = $insert_stmt->insert_id;

                            $posts['post_id'] = (int) $postId;
                            $posts['saved_status'] = 1;
                            $status = 1;
                            $errorMsg = "Post saved successfully";
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

    public function reportPost($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $postId = validateObject($postData, 'post_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', 0);
        $getCurrentDate = getDefaultDate();

        if ($userId == 0 || $postId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $status = 2;
            $posts = array();
            $is_delete = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $select_query = "SELECT post_id FROM " . TABLE_POST_REPORT . " WHERE user_id = ? AND post_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("iii", $userId, $postId, $is_testdata);
                $select_stmt->execute();
                $select_stmt->store_result();

                if ($select_stmt->num_rows > 0) {
                    $delete_query = "DELETE FROM " . TABLE_POST_REPORT . " WHERE user_id = ? AND post_id = ? AND is_delete = " . $is_delete . " AND is_testdata = ?";
                    if ($delete_stmt = $this->connection->prepare($delete_query)) {
                        $delete_stmt->bind_param("iii", $userId, $postId, $is_testdata);
                        if ($delete_stmt->execute()) {

                            $posts['post_id'] = (int) $postId;
                            $posts['report_status'] = 0;
                            $status = 1;
                            $errorMsg = "Post un-reported successfully";
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
                    $insert_query = "INSERT INTO " . TABLE_POST_REPORT . " (user_id, post_id,created_date,is_testdata) VALUES(?,?,?,?)";
                    if ($insert_stmt = $this->connection->prepare($insert_query)) {
                        $insert_stmt->bind_param("iisi", $userId, $postId, $getCurrentDate, $is_testdata);
                        if ($insert_stmt->execute()) {
                            $inserted_save_post_id = $insert_stmt->insert_id;

                            $posts['post_id'] = (int) $postId;
                            $posts['report_status'] = 1;
                            $status = 1;
                            $errorMsg = "Post reported successfully";
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

    public function loadLikes($postData)
    {

        $offset = validateObject($postData, 'offset', 0);
        $userId = validateObject($postData, 'user_id', 0);
        $postId = validateObject($postData, 'post_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_OFFSET_CONTACT + 5;
        $offset = $offset * $limit;

        $deleteStatus = DELETE_STATUS::NOT_DELETE;

        if ($userId > 0 && $postId > 0) {
            $select_query_like = "SELECT l.id,l.post_id,l.user_id,u.firstname,u.lastname,u.profilepic,u.description as user_description FROM " . TABLE_LIKES . " as l LEFT JOIN " . TABLE_USER . " as u ON u.id=l.user_id
            WHERE l.post_id = ? AND l.is_delete = " . $deleteStatus . " AND u.is_delete = " . $deleteStatus . " AND l.is_testdata = ? AND u.is_testdata = ? ORDER BY l.id DESC LIMIT ? OFFSET ?";

            $select_stmt_like = $this->connection->prepare($select_query_like);
            $select_stmt_like->bind_param("iiiii", $postId, $is_testdata, $is_testdata, $limit, $offset);

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

    private function getRelevantPost($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $userId = addslashes($userId);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_FEED_LOAD_ITEMS;

        $post_trending = validateObject($postData, 'post_trending', 0);
        $post_ranked = validateObject($postData, 'post_ranked', 0);
        $post_recent = validateObject($postData, 'post_recent', 0);
        $post_follow_user = validateObject($postData, 'post_follow_user', 0);

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
            p.id AS post_id,
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
            FROM " . TABLE_POST . " p
            LEFT JOIN " . TABLE_USER . " u ON u.id=p.user_id
            LEFT JOIN " . TABLE_CATEGORY . " ca ON ca.id=p.category_id
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON p.user_id = uf.receiver_id
            WHERE p.is_delete=? AND u.is_delete=? AND p.is_testdata=?  AND
            ((uf.is_delete = " . $deleteStatus . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))";

            if ($post_recent != 0) {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.created_date DESC LIMIT ? OFFSET ?";
            } else if ($post_follow_user != 0) {
                $selectQuerySecondHalf = "  AND (p.user_id != " . $userId . " OR (uf.sender_id  = " . $userId . " AND uf.request_status = 'ACCEPT')) GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
            } else {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
            }

            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiii", $deleteStatus, $deleteStatus, $isTestData, $isTestData, $limit, $offset);

            //  echo  $deleteStatus." ". $deleteStatus." ". $isTestData." ". $userId." ". $userId." ".  $isTestData." ".  $limit." ".  $offset;
            $post['posts'] = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt)) {

                        $comment_query = "SELECT pc.id as comment_id,user_id, post_id, comment_message,pc.created_date, u.first_name,u.last_name,u.description as user_description FROM " . TABLE_COMMENTS . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.post_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";

                        if ($comment_stmt = $this->connection->prepare($comment_query)) {
                            $comment_stmt->bind_param("is", $val['post_id'], $isTestData);
                            $comment_stmt->execute();
                            $comment_stmt->store_result();

                            if ($comment_stmt->num_rows > 0) {
                                while ($val2 = fetch_assoc_all_values($comment_stmt)) {
                                    $val['comment'][] = $val2;
                                }
                            } else {

                            }
                        }

                        $like_query = "SELECT id FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($like_stmt = $this->connection->prepare($like_query)) {
                            $like_stmt->bind_param("iis", $val['post_id'], $userId, $isTestData);
                            $like_stmt->execute();
                            $like_stmt->store_result();

                            if ($like_stmt->num_rows > 0) {
                                $val['like_status'] = 1;
                            } else {
                                $val['like_status'] = 0;
                            }
                        }

                        $val['profilepic'] = $val['profilepic'];
                        $this->addMediaObjectToFeed($val);
                        //$post['posts'][] = $val;
                        $post['posts'][] = $val;
                    }
                } else {
                    $post['posts'] = array();
                }

                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data[DATA] = $post;
            $data['load_more'] = count($post['posts']) == $limit;

        }
        return $data;
    }

    private function getCategoryPost($postData)
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
            p.id AS post_id,
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
            FROM " . TABLE_POST . " p
            LEFT JOIN " . TABLE_USER . " u ON u.id=p.user_id
            LEFT JOIN " . TABLE_CATEGORY . " ca ON ca.id=p.category_id
            LEFT JOIN " . TABLE_USER_FOLLOWERS . " AS uf ON p.user_id = uf.receiver_id
            WHERE p.is_delete=? AND u.is_delete=? AND p.is_testdata=? AND p.category_id = " . $category_id . "  AND (p.user_id = ? OR (uf.sender_id = ? AND uf.request_status = 'ACCEPT')) AND
            ((uf.is_delete = " . $deleteStatus . " AND uf.is_testdata = ?) OR (uf.is_delete IS NULL AND uf.is_testdata IS NULL))";

            if ($flag == 1) {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY like_count DESC LIMIT ? OFFSET ?";
            } else {
                $selectQuerySecondHalf = "  GROUP BY p.id ORDER BY p.id DESC LIMIT ? OFFSET ?";
            }

            $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
            $stmt = $this->connection->prepare($selectQuery);
            $stmt->bind_param("iiiiiiii", $deleteStatus, $deleteStatus, $isTestData, $userId, $userId, $isTestData, $limit, $offset);

            $post['posts'] = array();
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt)) {

                        $comment_query = "SELECT pc.id as comment_id,user_id, post_id, comment_message,pc.created_date, u.first_name,u.last_name,u.description as user_description FROM " . TABLE_COMMENTS . " as pc LEFT JOIN " . TABLE_USER . " as u ON pc.user_id=u.id WHERE pc.post_id = ? AND pc.is_delete = '" . $deleteStatus . "' AND pc.is_testdata = ? ORDER BY pc.created_date DESC LIMIT 1";

                        if ($comment_stmt = $this->connection->prepare($comment_query)) {
                            $comment_stmt->bind_param("is", $val['post_id'], $isTestData);
                            $comment_stmt->execute();
                            $comment_stmt->store_result();

                            if ($comment_stmt->num_rows > 0) {
                                while ($val2 = fetch_assoc_all_values($comment_stmt)) {
                                    $val['comment'][] = $val2;
                                }
                            } else {

                            }
                        }

                        $like_query = "SELECT id FROM " . TABLE_LIKES . " WHERE post_id = ? AND user_id = ? AND  is_delete = '" . $deleteStatus . "' AND is_testdata = ? ";

                        if ($like_stmt = $this->connection->prepare($like_query)) {
                            $like_stmt->bind_param("iis", $val['post_id'], $userId, $isTestData);
                            $like_stmt->execute();
                            $like_stmt->store_result();

                            if ($like_stmt->num_rows > 0) {
                                $val['like_status'] = 1;
                            } else {
                                $val['like_status'] = 0;
                            }
                        }

                        $val['profilepic'] = $val['profilepic'];
                        $this->addMediaObjectToFeed($val);
                        //$post['posts'][] = $val;
                        $post['posts'][] = $val;
                    }
                } else {
                    $post['posts'] = array();
                }

                $stmt->close();
            }

            $data[STATUS] = SUCCESS;
            $data[MESSAGE] = "Successfully fetched feeds.";
            $data[DATA] = $post;
            $data['load_more'] = count($post['posts']) == $limit;

        }
        return $data;
    }

    public function addCommentsToFeed($postData)
    {

        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        $isDelete = DELETE_STATUS::NOT_DELETE;

        $userId = validateObject($postData, 'user_id', 0);

        $feedId = validateObject($postData, 'post_id', 0);

        $comment = validateObject($postData, 'comment_message', "");
        $comment = utf8_decode($comment);

        $createdDate = getDefaultDate();
        $isQuerySuccess = true;
        if ($userId > 0 && $feedId > 0 && strlen($comment) > 0) {

            $this->connection->begin_transaction();
            $insertQuery = "INSERT INTO " . TABLE_COMMENTS . " (user_id,post_id,comment_message,is_testdata,created_date) VALUES(?,?,?,?,?)";
            $insertStmt = $this->connection->prepare($insertQuery);
            $insertStmt->bind_param('iisis', $userId, $feedId, $comment, $is_testdata, $createdDate);

            if ($insertStmt->execute()) {
                $insertStmt->close();
                $commentId = mysqli_insert_id($this->connection);

                $selectFeed = "SELECT user_id FROM " . TABLE_POST . " WHERE id=? AND is_delete=? AND is_testdata=?";
                $stmt = $this->connection->prepare($selectFeed);
                $feedOwner = -1;
                $stmt->bind_param("iss", $feedId, $isDelete, $is_testdata);
                $stmt->execute();
                $stmt->bind_result($feedOwner);
                $stmt->fetch();
                $stmt->close();

                $notificationType = NOTIFICATION_TYPE_COMMENT;
                $notificationMsg = "NOTIFICATION_COMMENT_ON_FEED";
                $notificationData = '{"post_id":"' . $feedId . '","comment_id":"' . $commentId . '","message":"' . $notificationMsg . '"}';
                $arrayNotification = array();

                $notificationObj['sender_id'] = (int) $userId;
                $notificationObj['receiver_id'] = (int) $feedOwner;
                $notificationObj['notification_type_id'] = (int) $feedId;
                $notificationObj['notification_type'] = $notificationType;
                $notificationObj['is_testdata'] = (int) $is_testdata;
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
                                $dataNotiArr['post_id'] = $feedId;
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
                    $selectComment = "SELECT np.id,np.user_id,np.post_id,np.comment_message,np.created_date,
                                          u.firstname,u.lastname,u.profilepic,u.description as user_description
                                          FROM " . TABLE_COMMENTS . " np
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

//    function loadComments($postData)
    //    {
    //
    //        $userId = validateObject($postData, 'user_id', 0);
    //        $postId = validateObject($postData, 'post_id', 0);
    //
    //        $lastCommentId = validateObject($postData, 'last_comment_id', 0);
    //
    //        $loadingType = validateObject($postData, 'loading_type', 0);
    //        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);
    //
    //        $limit = LIMIT_COMMENT_LOAD;
    //
    //
    //        $deleteStatus = DELETE_STATUS::NOT_DELETE;
    //        if ($userId > 0 && $postId > 0) {
    //
    //
    //            $selectQueryFirstHalf = "SELECT np.id as comment_id, np.user_id, np.post_id, np.comment_message, np.created_date,
    //                                           u.firstname, u.lastname, u.profilepic,u.description as user_description
    //                                          FROM " . TABLE_COMMENTS . " np
    //                                          LEFT JOIN " . TABLE_USER . " u ON u.id=np.user_id
    //                                          WHERE np.post_id=? AND np.is_delete=? AND np.is_testdata=?";
    //            if ($lastCommentId == 0) {
    //                $selectQuerySecondHalf = " ORDER BY np.id DESC LIMIT ?";
    //                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
    //                $stmt = $this->connection->prepare($selectQuery);
    //                $stmt->bind_param("iiii", $postId, $deleteStatus, $is_testdata, $limit);
    //            } else {
    //                if ($loadingType == Loading_Type::LOAD_MORE) { // If perform load more or first time fetches feed
    //                    $selectQuerySecondHalf = " AND np.comment_id < ? ORDER BY np.id DESC LIMIT ?";
    //                } else {
    //                    $selectQuerySecondHalf = " AND np.comment_id > ? ORDER BY np.id DESC  LIMIT ?";
    //                }
    //
    //                $selectQuery = $selectQueryFirstHalf . $selectQuerySecondHalf;
    //                $stmt = $this->connection->prepare($selectQuery);
    //                $stmt->bind_param("iiiii", $postId, $deleteStatus, $is_testdata, $lastCommentId, $limit);
    //            }
    //
    //            $postComments = array();
    //            if ($stmt->execute()) {
    //                $stmt->store_result();
    //                while ($val = fetch_assoc_all_values($stmt)) {
    //                    $postComments[] = $val;
    //                }
    //                $stmt->close();
    //            }
    //
    //
    //            $data['comments'] = $postComments;
    //            $data[STATUS] = SUCCESS;
    //            $data[MESSAGE] = "Load comment Successfully.";
    //        } else {
    //            $data[STATUS] = FAILED;
    //            $data[MESSAGE] = "Provide proper data";
    //        }
    //        return $data;
    //    }

    public function likeComments($postData)
    {
        $isTest = IS_TEST_DATA;

        $userId = validateObject($postData, 'user_id', 0);

        $commentId = validateObject($postData, 'comment_id', 0);

        $status = validateObject($postData, STATUS, -1);

        $createdDate = getDefaultDate();

        if ($userId > 0 && $commentId > 0 && $status > -1) {

            $insertQuery = "INSERT INTO " . TABLE_COMMENT_LIKE . " (user_id,comment_id,status,is_testdata,created_date,modified_date) VALUES(?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE status=?";
            $insertStmt = $this->connection->prepare($insertQuery);
            $insertStmt->bind_param('iiisssi', $userId, $commentId, $status, $isTest, $createdDate, $createdDate, $status);

            if ($insertStmt->execute()) {
                $insertStmt->close();

                $statusLike = Status_Like::LIKE;

                $selectComment = "SELECT COUNT(*) FROM " . TABLE_COMMENT_LIKE . " WHERE comment_id=? AND status=?";
                $stmt = $this->connection->prepare($selectComment);
                $count = -1;
                $stmt->bind_param("ii", $commentId, $statusLike);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                $post['comment_id'] = $commentId;
                $post['likes'] = $count;
                $post['like_status'] = $status;

                $data[DATA]['comments_like'] = $post;
                $data[STATUS] = SUCCESS;
                $data[MESSAGE] = "Like status changed.";
            } else {
                $data[STATUS] = FAILED;
                $data[MESSAGE] = "Please try again.";
            }
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }

        return $data;
    }

    public function deleteComment($postData)
    {

        $userId = validateObject($postData, 'user_id', 0);
        $commentId = validateObject($postData, 'comment_id', 0);
        $feedId = validateObject($postData, 'post_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', IS_TEST_DATA);

        if ($userId > 0 && $commentId > 0) {

            $selectComment = "SELECT COUNT(*) FROM " . TABLE_COMMENTS . " nf
                            LEFT JOIN " . TABLE_POST . " n ON n.id=nf.post_id
                            WHERE nf.id=? AND (nf.user_id=? OR n.user_id=?) AND nf.is_testdata = ? AND n.is_testdata = ?";

            $stmt = $this->connection->prepare($selectComment);
            $count = -1;
            $stmt->bind_param("iiiii", $commentId, $userId, $userId, $is_testdata, $is_testdata);
            $stmt->execute();
            $stmt->bind_result($count);

            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {

                $updateCommentQuery = "UPDATE " . TABLE_COMMENTS . " SET is_delete = 1 WHERE id = ?";
                $stmtUpdateComment = $this->connection->prepare($updateCommentQuery);

                $stmtUpdateComment->bind_param('i', $commentId);
                if ($stmtUpdateComment->execute()) {
                    $stmtUpdateComment->close();

                    //Remove notification related comment

                    $selectFeed = "SELECT user_id FROM " . TABLE_POST . " WHERE id=? AND is_delete=? AND is_testdata=?";
                    $stmt = $this->connection->prepare($selectFeed);
                    $feedOwner = -1;
                    $stmt->bind_param("iii", $feedId, $isDelete, $is_testdata);
                    $stmt->execute();
                    $stmt->bind_result($feedOwner);
                    $stmt->fetch();
                    $stmt->close();

                }
                $data[STATUS] = SUCCESS;
                $data[MESSAGE] = "Successfully deleted comment";
            } else {
                $data[STATUS] = FAILED;
                $data[MESSAGE] = "Only feed owner or comment creator can delete comment.";
            }
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "";
        }
        return $data;
    }

    public function deletePost($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $postId = validateObject($postData, 'post_id', 0);

        $isDeleteStatus = DELETE_STATUS::NOT_DELETE;
        if ($userId > 0 && $postId > 0) {

            $selectFeed = "SELECT user_id FROM " . TABLE_POST . " WHERE id=? AND is_delete=?";
            $stmt = $this->connection->prepare($selectFeed);
            $feedOwner = -1;
            $stmt->bind_param("ii", $postId, $isDeleteStatus);
            $stmt->execute();
            $stmt->bind_result($feedOwner);
            $stmt->fetch();
            $stmt->close();

            if ($feedOwner == $userId) {

                $this->connection->begin_transaction();

                $isQuerySuccess = false;

                $isDelete = DELETE_STATUS::IS_DELETE;
                $updateFeedQuery = "UPDATE " . TABLE_POST . " SET is_delete = ? WHERE id=?";
                $stmtUpdateFeed = $this->connection->prepare($updateFeedQuery);

                $stmtUpdateFeed->bind_param("si", $isDelete, $postId);
                if ($stmtUpdateFeed->execute()) {
                    $stmtUpdateFeed->close();
                    $isQuerySuccess = true;
                    //Remove notification related feed

                    // $notificationData = "%" . '"feed_id":"' . $postId . '"' . "%";

                    // if($groupId == 0){
                    //     $update_post_count = "UPDATE ".TABLE_USER." SET post_count = post_count - 1 WHERE user_id = ? AND is_delete = 0  AND post_count > 0";
                    //     $count_stmt = $this->connection->prepare($update_post_count);
                    //     $count_stmt->bind_param('i',$userId);
                    //     if ($count_stmt->execute()) {
                    //         $count_stmt->close();
                    //     }
                    // }

                    // $updateNotifQuery = "UPDATE " . TABLE_NOTIFICATION . " SET is_delete=1 WHERE (notification_type = 3 OR notification_type = 4 OR notification_type = 7) AND notification_type_id = ?";
                    // $updateNotifStmt = $this->connection->prepare($updateNotifQuery);
                    // $updateNotifStmt->bind_param('i',$feedId);
                    // if ($updateNotifStmt->execute()) {
                    //     $updateNotifStmt->close();
                    //     $isQuerySuccess = true;
                    // } else {
                    //     $isQuerySuccess = false;
                    // }

                }

                if ($isQuerySuccess) {
                    $this->connection->commit();
                } else {
                    $this->connection->rollback();
                }
                $data['delete_feed'] = array('post_id' => (int) $postId);

                $data[STATUS] = $isQuerySuccess ? SUCCESS : FAILED;
                $data[MESSAGE] = $isQuerySuccess ? "Successfully deleted" : "Please try again!";
            } else {
                $data['delete_feed'] = array('post_id' => (int) $postId);
                $data[STATUS] = SUCCESS;
                $data[MESSAGE] = "Post will be deleted by only post owner.";
            }
        } else {
            $data[STATUS] = FAILED;
            $data[MESSAGE] = "Provide proper data";
        }
        return $data;
    }

    public function listSavedPost($postData)
    {
        $userId = validateObject($postData, 'user_id', 0);
        $is_testdata = validateObject($postData, 'is_testdata', 0);

        $offset = validateObject($postData, 'offset', 0);
        $limit = LIMIT_RANDOM_FEED_LOAD;

        $offset = $offset * $limit;

        if ($userId == 0) {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $status = 2;
            $posts = array();
            $is_delete = DELETE_STATUS::NOT_DELETE;
            $errorMsg = "";

            $select_query = "SELECT save_post_id, u.first_name,u.last_name,u.description as user_description,m.media_name as profile_pic,f.post_like_count as no_of_likes,f.post_comment_count as no_of_comment, f.post_id as feed_id, f.user_id,f.post_description as feed_text,sp.created_date as saved_created_date, f.created_date as feed_created_date FROM " . TABLE_SAVE_POST . " AS sp LEFT JOIN " . TABLE_FEED . " AS f ON f.post_id=sp.post_id LEFT JOIN " . TABLE_USER . " AS u ON f.user_id=u.user_id LEFT JOIN " . TABLE_MEDIA . " AS m ON u.user_id=m.post_id WHERE  m.post_type = 1 AND sp.user_id = ? AND f.is_delete =" . $is_delete . " AND f.is_testdata = ? AND u.is_delete =" . $is_delete . " AND u.is_testdata = ? AND m.is_delete = " . $is_delete . " AND m.is_testdata = ? ORDER BY sp.created_date DESC LIMIT ? OFFSET ?";

            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->bind_param("iiiiii", $userId, $is_testdata, $is_testdata, $is_testdata, $limit, $offset);
                $select_stmt->execute();
                $select_stmt->store_result();

                if ($select_stmt->num_rows > 0) {
                    while ($post = fetch_assoc_all_values($select_stmt)) {

                        $post['profile_pic'] = ($post['profile_pic'] ? $post['profile_pic'] : "");

                        $select_media = "SELECT media_id,media_type,media_name FROM " . TABLE_MEDIA . " where post_id= ? AND post_type = 0 AND is_delete=" . $is_delete . " AND is_testdata = " . $is_testdata . "";
                        $stmt_media = $this->connection->prepare($select_media);
                        $stmt_media->bind_param("i", $post['feed_id']);
                        if ($stmt_media->execute()) {
                            $stmt_media->store_result();
                            $i = 0;
                            while ($val_media = fetch_assoc_all_values($stmt_media)) {
                                $post['media'][$i]['media_id'] = $val_media['media_id'];
                                $post['media'][$i]['feed_image'] = $val_media['media_name'];
                                $post['media'][$i]['type'] = $val_media['media_type'];
                                $i++;
                            }
                        }

                        $select_like = "SELECT like_id FROM " . TABLE_LIKES . " where post_id= ? AND user_id = ? AND is_delete=" . $is_delete . " AND is_testdata = " . $is_testdata . "";
                        $stmt_like = $this->connection->prepare($select_like);
                        $stmt_like->bind_param("ii", $post['feed_id'], $userId);
                        if ($stmt_like->execute()) {
                            $stmt_like->store_result();
                            if ($stmt_like->num_rows > 0) {
                                $post['like_status'] = 1;
                            } else {
                                $post['like_status'] = 0;
                            }
                        }

                        $select_following = "SELECT sender_id FROM " . TABLE_USER_FOLLOWERS . " WHERE sender_id=? AND receiver_id=? AND (request_status = 'ACCEPT' OR request_status='PENDING') AND is_delete = " . $is_delete . " AND is_testdata = " . $is_testdata;

                        $select_stmt_following = $this->connection->prepare($select_following);

                        $select_stmt_following->bind_param("ii", $userId, $post['user_id']);

                        if ($select_stmt_following->execute()) {
                            $select_stmt_following->store_result();
                            if ($select_stmt_following->num_rows > 0) {
                                $post['following_status'] = 1;
                            } else {
                                $post['following_status'] = 0;
                            }
                        }
                        if ($post['user_id'] == $userId) {
                            $post['following_status'] = 2;
                        }
                        $post['saved_status'] = 1;
                        $posts[] = $post;
                    }
                    $status = 1;
                    $errorMsg = "List of save post";
                    $data['home_screen'] = $posts;
                } else {
                    $status = 2;
                    $errorMsg = "No save post found";
                }
            } else {
                $status = 2;
                $errorMsg = "Something wrong with select query 2";
            }

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;

        }

        return $data;
    }

    public function createFeedMediaDirectory()
    {
        $uploadDirImg = SERVER_FEED_IMAGE_PATH;
        if (!is_dir($uploadDirImg)) {
            mkdir($uploadDirImg, 0777, true);
        }

        $uploadDirVideo = SERVER_FEED_VIDEO_PATH;
        if (!is_dir($uploadDirVideo)) {
            mkdir($uploadDirVideo, 0777, true);
        }
    }

    public function getMediaPrefixUrlBasedOnType($mediaType)
    {

        switch ($mediaType) {
            case FEED_MEDIA::IMAGE:{
                    return URL_FEED_IMAGE_PATH;
                }
            case FEED_MEDIA::VIDEO:{
                    return URL_FEED_VIDEO_PATH;
                }
            default:{
                    return "";
                }
        }
    }

    public function getMediaPrefixLocalPathBasedOnType($mediaType)
    {

        switch ($mediaType) {
            case FEED_MEDIA::IMAGE:{
                    return SERVER_FEED_IMAGE_PATH;
                }
            case FEED_MEDIA::VIDEO:{
                    return SERVER_FEED_VIDEO_PATH;
                }
            default:{
                    return "";
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

    public function unLinkImageFolder($folder, $image_name)
    {
        $uploadDir = $folder . $image_name;
        if (!unlink($uploadDir)) {
            //echo ("Error deleting ");
        } else {
            //echo ("Deleted ");
        }
    }

}
