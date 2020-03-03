<?php

class Security
{

    public static function encrypt($input, $key)
    {
        return openssl_encrypt($input,"AES-128-ECB",$key);
    }

    public static function decrypt($sStr, $sKey)
    {
       return openssl_decrypt($sStr,"AES-128-ECB",$sKey);
    }
}

?>