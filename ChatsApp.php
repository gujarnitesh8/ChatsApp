<?php

include_once 'config.php';
include_once 'HelperFunctions.php';
include_once 'Paths.php';
include_once 'TableVars.php';
include_once 'ConstantValues.php';
include_once 'CommonFunctions.php';
include_once 'SecurityFunctions.php';

error_reporting(E_ALL);

$post_body = file_get_contents('php://input');
$post_body = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($post_body));
$reqData[] = json_decode($post_body);

$postData = $reqData[0];

if (!empty($_POST['secret_key']) && !empty($_POST['access_key'])) {
    $postData = (object) array('access_key' => $_POST['access_key'], 'secret_key' => $_POST['secret_key'], 'device_type' => $_POST['device_type']);
}

$debug = 0;
//$logger->Log($debug, 'POST DATA :', $postData);
$status = "";

//$logger->Log($debug, 'Service :', $_REQUEST['Service']);

switch ($_REQUEST['Service']) {
    /*********************  User Functions ******************************/
    // case "Register":
    case "Register":{
            // $isSecure = (new SecurityFunctions($GLOBALS['con']))->checkForSecurityNew($postData->access_key, $postData->secret_key);
            $isSecure = YES;
            if ($isSecure == NO) {
                $data['status'] = MALICIOUS_SOURCE_STATUS;
                $data['message'] = MALICIOUS_SOURCE;
            } elseif ($isSecure == ERROR) {
                $data['status'] = FAILED;
                $data['message'] = TOKEN_ERROR;
            } else {
                include_once 'UserFunctions.php';
                $user = new UserFunctions($GLOBALS['con']);
                $data = $user->call_service($_REQUEST['Service'], $postData);
                if ($isSecure != YES) {
                    if ($isSecure['key'] == "Temp") {
                        $data['tempToken'] = $isSecure['value'];
                    } else {
                        $data['userToken'] = $isSecure['value'];
                    }
                }
            }
        }
        break;

    case "GetChatList":
    case "GetMessagesList":
    case "GetActiveUsers":
    case "GetGroups":{

            // $isSecure = (new SecurityFunctions($GLOBALS['con']))->checkForSecurityNew($postData->access_key, $postData->secret_key);
            $isSecure = YES;
            if ($isSecure == NO) {
                $data['status'] = MALICIOUS_SOURCE_STATUS;
                $data['message'] = MALICIOUS_SOURCE;
            } elseif ($isSecure == ERROR) {
                $data['status'] = FAILED;
                $data['message'] = TOKEN_ERROR;
            } else {
                include_once 'ChatFunctions.php';
                $user = new ChatFunctions($GLOBALS['con']);
                $data = $user->call_service($_REQUEST['Service'], $postData);
                if ($isSecure != YES) {
                    if ($isSecure['key'] == "Temp") {
                        $data['tempToken'] = $isSecure['value'];
                    } else {
                        $data['userToken'] = $isSecure['value'];
                    }
                }
            }
        }
        break;

    // case "UploadFileTest":{

    //         include_once 'UploadFileTest.php';
    //         $user = new UploadFileTest($GLOBALS['con']);
    //         $data = $user->call_service($_REQUEST['Service'], $postData);

    //     }
    //     break;

    // case "Logout":{
    //         $isSecure = YES;
    //         include_once 'UserFunctions.php';
    //         $user = new UserFunctions($GLOBALS['con']);
    //         $data = $user->call_service($_REQUEST['Service'], $postData);
    //         if ($isSecure != YES) {
    //             if ($isSecure['key'] == "Temp") {
    //                 $data['tempToken'] = $isSecure['value'];
    //             } else {
    //                 $data['userToken'] = $isSecure['value'];
    //             }
    //         }
    //     }
    //     break;

    case "ResendOTP":
    case "ValidateOTP":{
            include_once 'OTPFunctions.php';
            $user = new OTPFunctions($GLOBALS['con']);
            $data = $user->call_service($_REQUEST['Service'], $postData);
        }
        break;
    default:{
            $message = "No service found";
            $status = FAILED;
            $data['status'] = $status;
            $data['message'] = $message;
        }
        break;
}

header('Content-type: application/json');

echo json_encode($data);
mysqli_close($GLOBALS['con']);
