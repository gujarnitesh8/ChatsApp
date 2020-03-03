<?php

abstract class DELETE_STATUS
{
    const IS_DELETE = 1;
    const NOT_DELETE = 0;
    const NONE = "2";
}
abstract class ACTIVE_STATUS
{
    const IS_ACTIVE = "1";
    const NON_ACTIVE = "0";
}
define("SERVER_IMAGE_PATH","/pg/DareTakeApi/");

define("SERVER_PROFILE_IMAGES" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."profile_image/");
define("SERVER_FEED_IMAGE_PATH" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."post_image/");
define("SERVER_FEED_VIDEO_PATH" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."post_video/");
define("SERVER_CHAT_IMAGES_PATH" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."chat_image/");
define("SERVER_CHAT_VIDEOS_PATH" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."chat_video/");
define("SERVER_PUSH_NOTIFICATION" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."PushNotificationKey/");

define("SERVER_THUMB_IMAGE" , $_SERVER['DOCUMENT_ROOT'].SERVER_IMAGE_PATH."thumb_image/");

define("SERVER_PATH" , $_SERVER["DOCUMENT_ROOT"] );

define("URL_PROFILE_IMAGES" , "http://clientapp.narola.online/pg/DareTakeApi/profile_image/");
define("URL_FEED_IMAGE_PATH" , "http://clientapp.narola.online/pg/DareTakeApi/post_image/");
define("URL_FEED_VIDEO_PATH" , "http://clientapp.narola.online/pg/DareTakeApi/post_video/");
define("URL_CHAT_IMAGE_PATH" , "http://clientapp.narola.online/pg/DareTakeApi/chat_image/");
define("URL_CHAT_VIDEO_PATH" , "http://clientapp.narola.online/pg/DareTakeApi/chat_video/");

define("URL_THUMB_IMAGE" , "http://clientapp.narola.online/pg/DareTakeApi/thumb_image/");

define("URL_TEMPLATE_IMAGE" , "http://clientapp.narola.online/pg/DareTakeApi/template/");
