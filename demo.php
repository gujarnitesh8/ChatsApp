<?php

class demo{


    function updateTokenforUserss($userData)
    {
        $connection = $GLOBALS['con'];
        $user_id = validateValue($userData->userId, '');
        if ($user_id != '') {

            $modifiedDate = date('Y-m-d H:i:s', time());

            $generateToken = $this->generateToken(8);

            $query = "SELECT config_value FROM " . tblAdminConfig . " WHERE config_key='expiry_duration' AND is_delete=0";
            if ($stmt_get_config = $connection->prepare($query)) {
                $stmt_get_config->execute();
                $stmt_get_config->store_result();
                if ($stmt_get_config->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt_get_config)) {
                        $expiryDuration = $val['config_value'];
                    }
                }
                $stmt_get_config->close();
            }

            $currentdate = date("dmyHis", time() + $expiryDuration);

//            echo "... Date before addition..".date("dmyHis")."....Date after addition..".$currentdate."....addition value...". $expiryDuration[0];

            $updateQuery = "update " . tblAppTokens . " set token = ? , expiry = ? , created_date = ? where userid = ?";

            if ($update_query_stmt = $connection->prepare($updateQuery)) {
                $update_query_stmt->bind_param('sssi', $generateToken, $currentdate, $modifiedDate, $user_id);
                if ($update_query_stmt->execute()) {
                    $update_query_stmt->store_result();
//$username = validateValue($userData->userName,'');
// $uuid = validateValue($userData->UUID,'');
                    $uuid = validateValue($userData->GUID, '');

                    $security = new Security();

                    $generateTokenEncrypted = $security->encrypt($generateToken, $uuid);
                    $currentdateEncrypted = $security->encrypt($currentdate, $uuid);

                    $encryptedTokenName = $generateTokenEncrypted . "_" . $currentdateEncrypted;//$security->encrypt($mixedToken, $uuid."_".$username);

                    if ($update_query_stmt->affected_rows > 0) {

                        $data['UserToken'] = $encryptedTokenName;
                        $data['status'] = 1;
                        return $data;
//                    return $encryptedTokenName;
                    } else {
                        $insertTokenField = "`userid`, `token`, `expiry`,`created_date`";
                        $created_date = getDefaultDate();
                        $insertQuery = "Insert into " . tblAppTokens . " (" . $insertTokenField . ") values(?,?,?,?)";
                        if ($insert_stmt = $connection->prepare($insertQuery)) {

                            $insert_stmt->bind_param('isss', $user_id, $generateToken, $currentdate, $created_date);
                            if ($insert_stmt->execute()) {
                                $insert_stmt->close();

                                $data['UserToken'] = $encryptedTokenName;
                                $data['status'] = 1;

//  echo ' first encrypted token=> '.$encryptedTokenName;
                                return $data;
//                    return $encryptedTokenName;
                            } else {
                                echo $insert_stmt->error . " ***";
                            }
                        } else {

                        }
                    }
                } else {

                    $data['status'] = 0;
                    $data['UserToken'] = "no";
                    return $data;
//                return no;
                }
            } else {

            }
//   $update_query_stmt->close();
        }
        $data['status'] = 0;
        $data['UserToken'] = "no";
        return $data;
//return no;
    }
}
?>