<?php

//require_once('class.phpmailer.php');
include_once 'SendEmail.php';

class OTPFunctions
{
    protected $connection;

    public function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch ($service) {
            case "ValidateOTP":
                {
                    return $this->validateOTP($postData);
                }
                break;
            case "ResendOTP":
                {
                    return $this->resendOTP($postData);
                }
        }
    }

    public function validateOTP($userData)
    {

        $connection = $this->connection;
        $status = 2;

        $mobile_number = validateObject($userData, 'mobile_number', "");

        $otp_message = validateObject($userData, 'otp_message', "");

        $user_id = validateObject($userData, 'user_id', "");

        $country_code = validateObject($userData, 'country_code', "");
        $getCurrentDate = getDefaultDate();

        if ($otp_message == "" || $mobile_number == "" || $country_code == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $is_delete = DELETE_STATUS::NOT_DELETE;
            if ($country_code[0] != "+") {
                $country_code = "+" . $country_code;
            }
            $select_query = "select * from generated_otp as go LEFT JOIN users as u ON go.userId=u.id where u.mobile_number='" . $mobile_number . "' AND go.userId=$user_id and go.otp_message='" . $otp_message . "'";
            if ($select_stmt = $this->connection->prepare($select_query)) {
                $select_stmt->execute();
                $select_stmt->store_result();
                if ($select_stmt->num_rows > 0) {
                    while ($otpData = fetch_assoc_all_values($select_stmt)) {
                        if ($otpData['is_otp_verified'] == 0) {

                            $update_query = "update generated_otp set is_otp_verified=1 where userId=$user_id and otp_message='" . $otp_message . "'";
                            if ($updateStmt = $this->connection->prepare($update_query)) {
                                if ($updateStmt->execute()) {
                                    $status = 1;
                                    if ($select_stmt->num_rows > 0) {
                                        $errorMsg = "Otp verification success!!";
                                        $updateStmt->close();
                                    }
                                } else {
                                    echo $updateStmt->error;
                                }
                            } else {
                                echo $updateStmt->error;
                            }
                        } else {
                            $status = 2;
                            $errorMsg = "You are already verified!!";
                        }
                    }

                } else {
                    $status = 2;
                    $errorMsg = "Invalid OTP!!";
                }
            } else {
                $status = 2;
                $errorMsg = "Something went wrong!!";

            }

            // $errorMsg = "Otp verification success!!";

            $data['status'] = ($status > 1) ? FAILED : SUCCESS;
            $data['message'] = $errorMsg;
            if (!empty($posts)) {
                $data['User'] = $posts;
            }
        }
        return $data;
    }

    public function resendOtp($userData)
    {
        $status = 2;
        $connection = $this->connection;

        $user_id = validateObject($userData, 'user_id', "");
        $is_testdata = validateObject($userData, 'is_testdata', IS_TEST_DATA);

        if ($user_id == "") {
            $data['status'] = FAILED;
            $data['message'] = DEV_ERROR;
        } else {
            $otp = $this->sendOtp($user_id);
            if ($otp != null) {
                $data['status'] = SUCCESS;
                $data['message'] = "OTP Sent successfully!!";
                $data['data'] = $otp;
            } else {
                $data['status'] = FAILED;
                $data['message'] = "Something went wrong with OTP sending!!";
            }
        }

        return $data;
    }
    public function sendOtp($user_id)
    {

        $user_id = $user_id;
        $otp = generateRandomCode(9);
        $select_query = "select * from generated_otp where userId='" . $user_id . "'";
        if ($select_stmt = $this->connection->prepare($select_query)) {
            $select_stmt->execute();
            $select_stmt->store_result();
            if ($select_stmt->num_rows > 0) {
                $update_query = "update generated_otp set otp_message='" . $otp . "', is_otp_verified=0 where userId=$user_id";
                if ($updateStmt = $this->connection->prepare($update_query)) {
                    if ($updateStmt->execute()) {
                        $updateStmt->close();
                        return $otp;
                    } else {
                        // echo $updateStmt->error;
                    }
                } else {
                    // echo $updateStmt->error;
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
}
