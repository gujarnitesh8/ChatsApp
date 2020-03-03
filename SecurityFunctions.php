<?php

include_once 'ApiCrypter.php';
include_once 'HelperFunctions.php';

class SecurityFunctions {

    protected $connection;

    function __construct(mysqli $con)
    {
        $this->connection = $con;
    }

    public function call_service($service, $postData)
    {
        switch($service)
        {
            case "RefreshToken":
            {
                return $this->refreshToken($postData);
            }
                break;

            case "TestEncryption":
            {
                return $this->testEncryption($postData);
            }
                break;

            case "updateTokenForUser":
            {
                return $this->updateTokenForUser($postData);
            }
                break;

            case "expiredAllTokenofUser":
            {
                return $this->expiredAllTokenofUser($postData);
            }
                break;
        }
    }

    //============================================== Generate Random Unique Token Number =============================

    public function crypto_random_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);

        return $min + $rnd;
    }

    public function generateToken($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_random_secure(0, $max)];
        }

        return $token;
    }

    // USED METHODS
    public function refreshToken($userData)
    {

        $access_key = validateObject($userData, 'access_key', "");
        $access_key = addslashes($access_key);
        $isSecure = $this->checkForSecurityForRefreshToken($access_key,"");

        if ($isSecure == NO)
        {
            $status=FAILED;
            $message = MALICIOUS_SOURCE;
        }
        elseif($isSecure == ERROR)
        {
            $status = FAILED;
            $message = TOKEN_ERROR;
        }
        else {
            //print_r($isSecure);
            if ($isSecure != YES) {
                if ($isSecure['key'] == "Temp") {
                    $data['tempToken'] = $isSecure['value'];
                } else {
                    $data['userToken'] = $isSecure['value'];
                }
            }
            $status = SUCCESS;
            $message = "Token is generated.";
        }

        $data[STATUS] = $status;
        $data[MESSAGE] = $message;

        $data['adminConfig'] = $this->getAdminConfigWithToken($userData);

        return $data;
    }

    function curlGetLocationUsingGooglePlaceApiWithOutDB()
    {
        $api_key = "AIzaSyD-KjBdU-G9W58gAppN8qAu0N5wUtNlJzo";

        $api_latitude = "21.170240";
        $api_longitude = "‎72.831062";

        $types1="art_gallery,atm,bank,bar,beauty_salon,bicycle_store,bus_station,campground,car_rental,casino,church,clothing_store,convenience_store,doctor,embassy,florist,food,furniture_store,gas_station,grocery_or_supermarket,gym,hair_care,hardware_store,health,home_goods_store,hospital,laundry,library,liquor_store,meal_delivery,meal_takeaway,mosque,movie_rental,movie_theater,museum,night_club,park,parking,pet_store,pharmacy,police,post_office,restaurant,rv_park,school,shoe_store,shopping_mall,spa,stadium,store,subway_station,taxi_stand,train_station,transit_station,travel_agency,university,veterinary_care,zoo";
        $types="restaurant";
        $next_place_url = "https://maps.googleapis.com/maps/api/place/search/xml?location=" . $api_latitude . "," . $api_longitude . "&radius=10000&types=".$types."&hasNextPage=true&nextPage()=true&sensor=false&key=" . $api_key . "&pagetoken=";

        echo $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $api_latitude . "," . $api_longitude . "&radius=500&type=".$types."&sensor=false&key=" . $api_key;

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_HEADER, 0);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        /*curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);*/
        $res = curl_exec($handle);
        if (curl_errno($handle)) {
            echo "Error=>\n";
            print curl_error($handle);
        }
        if ($res === false)
        {
            $info = curl_getinfo($handle);
            curl_close($handle);
            echo '<pre>';
            die('error occured during curl exec. Additioanl info: ' . var_export($info));
        }

        curl_close($handle);
        $decoded1 = json_decode($res,true);
        echo "\n result=> ".$res;
        return $res;

        /*$detail_result = array();
        foreach ($res->results as $row) {
            print_r($row);
            $count_api++;
            echo $google_place_id = $row->place_id;
        }*/
    }

    public function getUserAgent()
    {
        $string=$_SERVER ['HTTP_USER_AGENT'];
        $data['User_agent']=$string;
        return $data;
    }

    public function testEncryption($userData){

        //echo '  Current PHP version: ' . phpversion();
        //echo phpinfo();
        $guid = validateValue ($userData -> guid, "");

        $query = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='globalPassword' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
        $result = mysqli_query($GLOBALS['con'], $query) or $message = mysqli_error($GLOBALS['con']);
        $masterKey = mysqli_fetch_row($result);
        $global_pwd_value=$masterKey[0];

        $security = new Security();
        $encrpt_acesskey = $security->encrypt($guid,$global_pwd_value);
        $data['encrypted_value']=$encrpt_acesskey;
        $data['decrypted_value'] = $security->decrypt($encrpt_acesskey,$global_pwd_value);

       /* $s=mysqli_query($this->connection,"select * from TABLE_USER ");

        while($row=mysqli_fetch_assoc($s)){
            $guid=$this->gen_uuid();
            $u="update TABLE_USER set guid='".$guid."' where id=".$row['uid'];
            $result = mysqli_query($this->connection, $u) or $message = mysqli_error($this->connection);
        }
        exit;*/

        return $data;
    }


    public function expiredAllTokenofUser($userData)
    {
        $user_id = validateValue($userData['userId'],'');

        if($user_id != '') {

            $modifiedDate = date('Y-m-d H:i:s', time());

            $updateQuery = "update ". TABLE_APP_TOKENS ." set  modified_date = '".$modifiedDate."' where user_id = '".$user_id."' ";
            $res = mysqli_query($this->connection,$updateQuery) or die('Error:  '. mysqli_error($this->connection));
            if ($res)
            {
                return YES;
            }
        }

        return NO;
    }


    // USED METHODS
    public function updateTokenForUser($userData)
    {
        $connection=$this->connection;
        $user_id = validateValue($userData->userId,'');
        $device_type = validateValue($userData->deviceType,'');
        $device_token = validateValue($userData->deviceToken,'');

        if($user_id != '')
        {
            $modifiedDate = date('Y-m-d H:i:s', time());
            $generateToken = $this->generateToken(8);
            $query = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='expiry_duration' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
            if($stmt_get_config = $connection->prepare($query)) {
                $stmt_get_config->execute();
                $stmt_get_config->store_result();

                if ($stmt_get_config->num_rows > 0) {
                    while ($val = fetch_assoc_all_values($stmt_get_config)) {
                        $expiryDuration = $val['config_value'];
                    }
                }
                $stmt_get_config->close();

                $currentdate = date("dmyHis", time() + $expiryDuration);
                $updateQuery = "update " . TABLE_APP_TOKENS . " set token = ?, device_token=? , device_type=? , expiry = ? , created_date = ? where user_id = ?";

                if ($update_query_stmt = $connection->prepare($updateQuery)) {
                    $update_query_stmt->bind_param('ssssss', $generateToken,$device_token,$device_type, $currentdate, $modifiedDate, $user_id);
                    if ($update_query_stmt->execute()) {
                        $update_query_stmt->store_result();

                        $uuid = validateValue($userData->GUID, '');

                        $security = new Security();

                        $generateTokenEncrypted = $security->encrypt($generateToken, $uuid);
                        $currentdateEncrypted = $security->encrypt($currentdate, $uuid);

                        $encryptedTokenName = $generateTokenEncrypted . "_" . $currentdateEncrypted;

                        if ($update_query_stmt->affected_rows > 0) {

                            $data['userToken'] = $encryptedTokenName;
                            $data['status'] = SUCCESS;
                            return $data;

                        } else {
                            $insertTokenField = "`user_id`, `token`, `device_token`, `device_type`, `expiry`,`created_date`";
                            $created_date = getDefaultDate();
                            $insertQuery = "Insert into " . TABLE_APP_TOKENS . " (" . $insertTokenField . ") values(?,?,?,?,?,?)";
                            if ($insert_stmt = $connection->prepare($insertQuery)) {

                                $insert_stmt->bind_param('isssss', $user_id, $generateToken, $device_token, $device_type, $currentdate, $created_date);
                                if ($insert_stmt->execute()) {
                                    $insert_stmt->close();

                                    $data['userToken'] = $encryptedTokenName;
                                    $data['status'] = SUCCESS;

                                    return $data;

                                } else {
                                    echo $insert_stmt->error . " ***";
                                }
                            } else {

                            }
                        }
                    } else {

                        $data['status'] = 0;
                        $data['userToken'] = NO;
                        return $data;

                    }
                } else {

                }
            }
        }
        $data['status'] = FAILED;
        $data['userToken'] = NO;
        return $data;
    }

    // USED METHODS
    public function gen_uuid()
    {
        // return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        //Remove last 4 charcter from above string to make string of 32 characters long.
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }


    // USED METHODS
    public function checkForSecurityNew($accessvalue,$secretvalue)
    {

        $connection=$this->connection;
        if($accessvalue == "" || $secretvalue == "")
        {
            return ERROR;
        }
        else{
            // get user-agent from database

            $query = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='userAgent' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
            if($stmt_get_user_agent = $connection->prepare($query)) {
                $stmt_get_user_agent->execute();
                $stmt_get_user_agent->store_result();
                if ($stmt_get_user_agent->num_rows > 0) {
                    while($value=fetch_assoc_all_values($stmt_get_user_agent))
                    {
                        $user_agent = $value['config_value'];
                        $separateKey = (explode(',', $user_agent));
                        // check user-agent is valid
                        if ((strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[0]) !== false) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[1]) !== false) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[2]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[3]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[4]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[5]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[6]) !== false ))
                        {
                            // get temporary token for user.
                            $query_config = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='tempToken' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
                            if($stmt_get_temp_token = $connection->prepare($query_config)) {
                                $stmt_get_temp_token->execute();
                                $stmt_get_temp_token->store_result();
                                if ($stmt_get_temp_token->num_rows > 0) {
                                    while($val=fetch_assoc_all_values($stmt_get_temp_token)){
                                        $tempToken = $val['config_value'];
                                        // get global password to encrypt temp token
                                        $query_global_pwd = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='globalPassword' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
                                        if($stmt_get_global_pwd = $connection->prepare($query_global_pwd)) {
                                            $stmt_get_global_pwd->execute();
                                            $stmt_get_global_pwd->store_result();
                                            if ($stmt_get_global_pwd->num_rows > 0) {
                                                while($val=fetch_assoc_all_values($stmt_get_global_pwd)){


                                                    $masterKey = $val['config_value'];
                                                    $security = new Security();

                                                    // check user request it with temporary credentials or private credential
                                                    if ($accessvalue == "nousername")
                                                    {
                                                        // check user passed temporary token or request with temporary token.
                                                        if ($secretvalue == NULL)
                                                        {
                                                            $secretvalue = $security->encrypt($tempToken, $masterKey);

                                                            $response = array();
                                                            $response['key'] = "Temp";// return temporary token
                                                            $response['value'] = $secretvalue;
                                                            return $response;
                                                        }
                                                        else
                                                        {

                                                            //  echo '  Current PHP version: ' . phpversion();
                                                            //   $secretvalue1 = $security->encrypt('allowAccessToApp', $masterKey[0]);

                                                                
                                                            $secretvalue = $security->decrypt($secretvalue, $masterKey);
                                                         //   echo 'after '.$secretvalue;
                                                            // match token is valid or not
                                                            if ($secretvalue == $tempToken)
                                                            {
                                                                return YES;
                                                            }
                                                            else
                                                            {
                                                                return NO;
                                                            }
                                                        }
                                                    }
                                                    else
                                                    {
                                                        $tempToken = $security->encrypt($tempToken[0], $masterKey[0]);
                                                        return $this->checkCredentialsForSecurityNew($accessvalue,$secretvalue,$tempToken);
                                                    }
                                                }
                                            }
                                            $stmt_get_global_pwd->close();
                                        }
                                    }
                                }
                                $stmt_get_temp_token->close();
                            }
                        }
                        else
                        {
                            return NO;
                        }
                    }
                }
                $stmt_get_user_agent->close();
            }
else{
                return NO;
}

        }
    }

    // USED METHODS
    public function checkCredentialsForSecurityNew($accessvalue,$secretvalue,$tempToken)
    {
        $connection=$this->connection;
        $query = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='globalPassword' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
        if($stmt_get_global_pwd = $connection->prepare($query)) {
            $stmt_get_global_pwd->execute();
            $stmt_get_global_pwd->store_result();
            if ($stmt_get_global_pwd->num_rows > 0) {
                while ($value = fetch_assoc_all_values($stmt_get_global_pwd)) {
                     $masterKey = $value['config_value'];
                    //step1= decrpt accesskey with global password
                    $security = new Security();
                    $decrypted_access_key = $security->decrypt($accessvalue, $masterKey);
                    //$decrypted_access_key1 = $security->decrypt($accessvalue,"(TabrabbitApp)17(TabrabbitApp)17");
//                    echo "\ndecrpt => ".$decrypted_access_key;

                    //step2= Check access key in user table
                     $queryToCheckAccessKeyExist = "SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.guid, u.facebookid, u.googleid, u.password, u.post_count, u.follower_count, u.following_count, u.rates,u.description as user_description, u.profilepic, u.is_private
                    FROM " . TABLE_USER . " as u WHERE u.guid ='" . $decrypted_access_key . "' AND u.is_delete='".DELETE_STATUS::NOT_DELETE."'";
                    if($stmt_check_access_key = $connection->prepare($queryToCheckAccessKeyExist)) {
                        $stmt_check_access_key->execute();
                        $stmt_check_access_key->store_result();
                        if ($stmt_check_access_key->num_rows > 0)
                        {
                            while($user_value=fetch_assoc_all_values($stmt_check_access_key)){

                                $queryToCheckRecordExist = "SELECT * FROM " . TABLE_APP_TOKENS . " WHERE user_id =" . $user_value['id']. " AND is_delete='".DELETE_STATUS::NOT_DELETE."'";

                                if($stmt_check_record_in_token = $connection->prepare($queryToCheckRecordExist)) {
                                    $stmt_check_record_in_token->execute();
                                    $stmt_check_record_in_token->store_result();
                                    if ($stmt_check_record_in_token->num_rows > 0)
                                    {
                                        while($row_token=fetch_assoc_all_values($stmt_check_record_in_token))
                                        {
                                            $tokenName = $row_token['token'];
                                            $currentdate = $row_token['expiry'];
                                            if($secretvalue == $tempToken)
                                            {
                                                // we can return user's private access token here
                                                // $tokenName = $tokenName."_".$currentdate;
                                                $currentdateEncrypt = $security->encrypt($currentdate,$decrypted_access_key);
                                                $tokenNameEncrypt = $security->encrypt($tokenName, $decrypted_access_key);
                                                // echo ' current date encrpt=> '.$currentdateEncrypt;
                                                // echo ' token name encrpt=> '.$tokenNameEncrypt;
                                                $tokenName = $tokenNameEncrypt."_".$currentdateEncrypt;
                                                $response = array();
                                                $response['key'] = "User"; // return user's private token
                                                $response['value'] = $tokenName;

                                                // echo ' secret=access scenario my token=> '.$tokenName;
                                                return $response;
                                            }
                                            else if($secretvalue == NULL)
                                            {
                                                $currentdateEncrypt = $security->encrypt($currentdate, $decrypted_access_key);
                                                $tokenNameEncrypt = $security->encrypt($tokenName, $decrypted_access_key);
                                                $tokenName = $tokenNameEncrypt."_".$currentdateEncrypt;
                                                $response = array();
                                                $response['key'] = "User";// return user's private token
                                                $response['value'] = $tokenName;
                                                return $response;
                                            }
                                            else {

                                               $secretvalue = explode("_",$secretvalue);

                                                $decrypted_secret_key = $security->decrypt($secretvalue[0], $decrypted_access_key);
                                                $decrypted_secret_key1 = $security->decrypt($secretvalue[1], $decrypted_access_key);
                                                if ($decrypted_secret_key == $tokenName)
                                                {
                                                    return YES;
                                                }
                                                else
                                                {
                                                    return NO;
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        return NO;
                                    }
                                    $stmt_check_record_in_token->close();
                                }
                            }
                        }
                        else
                        {
                            return NO;
                        }
                        $stmt_check_access_key->close();
                    }
                }
                $stmt_get_global_pwd->close();
            }
        }



    }

    // USED METHODS
    public function checkForSecurityForRefreshToken($accessvalue,$secretvalue)
    {
        $connection=$this->connection;
        if($accessvalue == "")
        {
            $data['status'] = FAILED;
            $data['message'] = TOKEN_ERROR;
        }
        else
        {
            // get user-agent from database
            //print_r($_SERVER ['HTTP_USER_AGENT']);
            $query = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='userAgent' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
            if($stmt_get_user_agent = $connection->prepare($query)) {
                $stmt_get_user_agent->execute();
                $stmt_get_user_agent->store_result();
                if ($stmt_get_user_agent->num_rows > 0) {
                    while($value=fetch_assoc_all_values($stmt_get_user_agent))
                    {
                        $user_agent = $value['config_value'];
                        $separateKey = (explode(',', $user_agent));

                        // check user-agent is valid
                        if ((strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[0]) !== false) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[1]) !== false) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[2]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[3]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[4]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[5]) !== false ) || (strpos($_SERVER ['HTTP_USER_AGENT'], $separateKey[6]) !== false ))
                        {

                            // get temporary token for user.
                            $query_config = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='tempToken' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";

                            if($stmt_get_temp_token = $connection->prepare($query_config)) {
                                $stmt_get_temp_token->execute();
                                $stmt_get_temp_token->store_result();
                                if ($stmt_get_temp_token->num_rows > 0) {
                                    while($val=fetch_assoc_all_values($stmt_get_temp_token)){
                                        $tempToken = $val['config_value'];

                                        // get global password to encrypt temp token
                                        $query_global_pwd = "SELECT config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key='globalPassword' AND is_delete='".DELETE_STATUS::NOT_DELETE."'";

                                        if($stmt_get_global_pwd = $connection->prepare($query_global_pwd)) {
                                            $stmt_get_global_pwd->execute();
                                            $stmt_get_global_pwd->store_result();
                                            if ($stmt_get_global_pwd->num_rows > 0) {
                                                while($val=fetch_assoc_all_values($stmt_get_global_pwd)){
                                                    $masterKey = $val['config_value'];
                                                    $security = new Security();

                               // check user request it with temporary credentials or private credential
                                                    if ($accessvalue == "nousername")
                                                    {
                                                        // check user passed temporary token or request with temporary token.
                                                        if ($secretvalue == NULL)
                                                        {
                                                            // return encrypted token

                                                            $secretvalue = $security->encrypt($tempToken, $masterKey);

                                                            $response = array();
                                                            $response['key'] = "Temp";// return temporary token
                                                            $response['value'] = $secretvalue;
                                                            return $response;
                                                        }
                                                        else
                                                        {
                                                            //  echo '  Current PHP version: ' . phpversion();(TheWorkWireApp)
                                                            //   $secretvalue1 = $security->encrypt('allowAccessToApp', $masterKey[0]);
                                                            //echo 'after '.$secretvalue1;

                                                            $secretvalue = $security->decrypt($secretvalue, $masterKey);

                                                            // match token is valid or not
                                                            if ($secretvalue == $tempToken)
                                                            {
                                                                return YES;
                                                            }
                                                            else
                                                            {
                                                                return NO;
                                                            }
                                                        }
                                                    }
                                                    else
                                                    {
//                echo '\n'.' before temp token=> ';
//                print_r($tempToken);
                                                        $tempToken = $security->encrypt($tempToken[0], $masterKey[0]);
                                                        // check security access with user's private credentials
                                                        // echo '\n'.'temp token=> '.$tempToken;

                                                        return $this->checkCredentialsForSecurityNew($accessvalue,$secretvalue,$tempToken);
                                                    }
                                                }
                                            }
                                            $stmt_get_global_pwd->close();
                                        }

                                    }

                                }
                                $stmt_get_temp_token->close();
                            }

                        }
                        else
                        {
                            //  No valid user agents
//            echo "No user-agent";
                            return NO;
                        }
                    }
                }
                $stmt_get_user_agent->close();
            }



        }

    }

    // USED METHODS
    public function getAdminConfigWithToken($postData)
    {
        $data = array();
        $connection=$this->connection;
        $secret_key = validateObject($postData, 'secret_key', "");
        $secret_key = addslashes($secret_key);

        $access_key = validateObject($postData, 'access_key', "");
        $access_key = addslashes($access_key);
        if ($access_key == "") {

        } else {
            $isSecure = $this->checkForSecurityNew($access_key, $secret_key);
            if ($isSecure != NO) {

                 $query = "SELECT config_key,config_value FROM " . TABLE_ADMIN_CONFIG . " WHERE config_key IN('globalPassword','userAgent','tempToken')  AND is_delete='".DELETE_STATUS::NOT_DELETE."'";
                if ($stmt_get_admin_config = $connection->prepare($query)) {
                    $stmt_get_admin_config->execute();
                    $stmt_get_admin_config->store_result();

                    if ($stmt_get_admin_config->num_rows > 0) {
                        while ($row = fetch_assoc_all_values($stmt_get_admin_config)) {
                            $post=array();
                            $s=$row['config_key'];
                            $post[$row['config_key']]=$row['config_value'];
                            $data[] = $post;
                        }

                        $message = "Successfully listed.";
                        $stmt_get_admin_config->close();
                    } else {

                        $message = DEFAULT_NO_RECORD;
                    }

                    $status = SUCCESS;
                }
            } else {
                $data = "";
            }

        }

        return $data;
    }


}
?>