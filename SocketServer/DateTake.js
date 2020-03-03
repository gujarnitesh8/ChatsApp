var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var format = require('string-format');
express = require('express'),
    path = require('path'),
    log4js = require('log4js'),
    enumObj = require('enum'),
    FCM = require('fcm').FCM,
    apns = require('apn'),
    mysql = require('mysql');

//===== TABLES ======

var TABLE_CHAT_MODULE = "chat_messages";
var TABLE_USERS = "users";
var TABLE_USER_INFO = "user_info";
var TABLE_CONVERSION = "chat_conversion";
var TABLE_MEDIA = "media";
var TABLE_APP_TOKENS = "app_tokens";
var TABLE_NOTIFICATION = "notification";

var pool = mysql.createPool({
    connectionLimit: 10,
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'ChatsApp',
    charset: 'utf8mb4'
});

var users = [];

var FCM_SERVER_KEY = "AAAAXPC02Gc:APA91bEQ-kXTEv2wJRoiCd9ahrY3UNfj4dhUvgbPzFZ1XRiu6tzmrT6cRce68hut50MPJRDIlYb-YY66_0kA_nlkJrHC_UqBtoA86zbb6ptA_hOKLWAYn1r6v9RrL0hIWS-a8JjC7xTz";
var SUCCESS = "success";
var FAILED = "failed";
var NOTIFICATION_MESSAGE_TYPE = "7";

http.listen(9505, function () {
    console.log('listening on *:9505');
});
app.get('/', function (req, res) {
    res.sendFile(__dirname + '/daretakehtml.html');
});

// log4js.configure({
//  appenders: {taggLifeApp: {type: 'file', filename: 'taggLifeApp.log', mode: '777'}},
//  categories: {default: {appenders: ['taggLifeApp'], level: 'error'}}
//  });
//  const logger = log4js.getLogger('taggLifeApp');

var logger = log4js.getLogger('daretakeApp');

io.on('connection', function (client) {
    console.log('connected ==>' + client.id);

    /**
   * here is function of join socket connection
   */
    client.on('JoinSocket', function (data, callback) {
        logger.level = 'debug';
        logger.debug("Join socket");

        if (typeof data.id === "undefined") {
            console.log("Please pass user id");

        } else {
            client.join(data.id);
            client.user_id = data.id;
            if (users.length <= 0) {
                console.log("**** First User ****--->" + data.id);
                users.push(data.id);
            } else {
                var userAvailable = isInArray(data.id, users);
                if (userAvailable == false) {
                    console.log("**** New  User ****--->" + data.id);
                    users.push(data.id);
                }
            }
            if (typeof callback === "function") {
                callback({ status: SUCCESS });
            }
        }
        console.log(users, 'avalible user 0');
    });

    /**
     * here is function of send message
    */

    client.on('SendNewMessage', function (data, callback) {
        var finalObj = {};
        var created_date = getCurrentUTCDateTime();

        var is_delete = 0;

        var insertNewChat = "INSERT INTO " + TABLE_CONVERSION + " (sender_id, receiver_id, last_message, created_date, modified_date, is_testdata) " +
            "VALUES (?,?,?,?,?,?)";

        var chatInfo = "SELECT id as conversion_id FROM " + TABLE_CONVERSION +
            " WHERE ((sender_id = " + data.sender_id + " AND receiver_id = " + data.receiver_id + ") OR (sender_id = "
            + data.receiver_id + " AND receiver_id = " + data.sender_id + ")) AND is_delete = 0 AND is_testdata=" + data.is_testdata + "";

        var insertExistChat = "INSERT INTO " + TABLE_CHAT_MODULE + " (conversion_id, sender_id, receiver_id, message_type, chat_message, created_date, is_testdata) " +
            "VALUES (?,?,?,?,?,?,?)";

        var updateLastMessage = "UPDATE " + TABLE_CONVERSION + " SET last_message = ?, modified_date = '" + created_date + "' WHERE  ((sender_id = " + data.sender_id + " AND receiver_id = " + data.receiver_id + ") OR (sender_id = "
            + data.receiver_id + " AND receiver_id = " + data.sender_id + "))";

        var insertMedia = "INSERT INTO " + TABLE_MEDIA + " (post_id, media_type, media_name, created_date) VALUES(?,?,?,?)";

        executeQuery(chatInfo, '', function (err, chatResult, fields) {
            if (err) {
                logger.error(new Error().stack + err);
                throw err;
            } else {
                if (typeof chatResult !== 'undefined' && chatResult.length > 0) {
                    var messageType = data.message;
                    executeQuery(insertExistChat, [chatResult[0].conversion_id, data.sender_id, data.receiver_id, data.message_type, data.message, created_date, data.is_testdata], function (err, result, fields) {
                        if (err) {
                            logger.error(new Error().stack + err);
                            throw err;
                        }


                        data.message_id = result.insertId;
                        data.created_date = created_date;

                        if (data.message_type == 'IMAGE' || data.message_type == 'VIDEO') {
                            executeQuery(insertMedia, [data.message_id, data.message_type, data.media_name, created_date], function (err, result2, fields) {
                                if (err) {
                                    logger.error(new Error().stack + err);
                                    throw err;
                                }
                            });
                        }

                        if (data.message_type == 'IMAGE') {
                            messageType = 'IMAGE';
                        } else if (data.message_type == 'VIDEO') {
                            messageType = 'VIDEO';
                        }

                        executeQuery(updateLastMessage, [messageType], function (err, result1, fields) {
                            if (err) {
                                logger.error(new Error().stack + err);
                                throw err;
                            }
                        });


                        var mediaObj = {};
                        if (data.message_type == 'IMAGE' || data.message_type == 'VIDEO') {
                            mediaObj = {
                                media_id: '',
                                chat_image: data.media_name,
                                type: data.message_type
                            };
                        }

                        finalObj = {
                            conversion_id: stringToInt(chatResult[0].conversion_id),
                            message_id: stringToInt(data.message_id),
                            sender_id: stringToInt(data.sender_id),
                            receiver_id: stringToInt(data.receiver_id),
                            message_type: data.message_type,
                            message: data.message,
                            created_date: created_date,
                            is_testdata: stringToInt(data.is_testdata),
                            media: mediaObj
                        }

                        if (typeof callback === "function") {
                            callback(finalObj);
                        }
                        console.log(finalObj, 'first');
                        var userAvailable = isInArray(data.receiver_id, users);
                        if (userAvailable === true) {
                            console.log(finalObj, 'second');
                            io.in(data.receiver_id).emit("ReceiveMessage", finalObj);
                        } else {
                            //if(directPush(finalObj)){
                            //    console.log('message send');
                            //}else{
                            //    console.log('message failed');
                            //}
                            console.log('message send');
                        }

                    });


                } else {
                    var messageType = data.message;

                    if (data.message_type == 'IMAGE') {
                        messageType = 'IMAGE';
                    } else if (data.message_type == 'VIDEO') {
                        messageType = 'VIDEO';
                    }
                    executeQuery(insertNewChat, [data.sender_id, data.receiver_id, messageType, created_date, created_date, data.is_testdata], function (err, result, fields) {
                        if (err) {
                            logger.error(new Error().stack + err);
                            throw err;
                        }
                        var tempId = result.insertId;
                        data.created_date = created_date;

                        executeQuery(insertExistChat, [tempId, data.sender_id, data.receiver_id, data.message_type, data.message, created_date, data.is_testdata], function (err, result1, fields) {
                            if (err) {
                                logger.error(new Error().stack + err);
                                throw err;
                            }
                            data.message_id = result1.insertId;

                            if (data.message_type == 'IMAGE' || data.message_type == 'VIDEO') {
                                executeQuery(insertMedia, [data.message_id, data.message_type, data.media_name, created_date], function (err, result2, fields) {
                                    if (err) {
                                        logger.error(new Error().stack + err);
                                        throw err;
                                    }
                                });
                            }
                        });

                        var mediaObj = {};
                        if (data.message_type == 'IMAGE' || data.message_type == 'VIDEO') {
                            mediaObj = {
                                media_id: '',
                                chat_image: data.media_name,
                                type: data.message_type
                            };
                        }

                        finalObj = {
                            conversion_id: stringToInt(tempId),
                            message_id: stringToInt(data.message_id),
                            sender_id: stringToInt(data.sender_id),
                            receiver_id: stringToInt(data.receiver_id),
                            message_type: data.message_type,
                            message: data.message,
                            created_date: created_date,
                            is_testdata: stringToInt(data.is_testdata),
                            media: mediaObj
                        }

                        if (typeof callback === "function") {
                            callback(finalObj);
                        }
                        console.log(finalObj, 'first');
                        var userAvailable = isInArray(data.receiver_id, users);
                        if (userAvailable === true) {
                            console.log(finalObj, 'second');
                            io.in(data.receiver_id).emit("ReceiveMessage", finalObj);
                        } else {
                            //if(directPush(finalObj)){
                            //    console.log('message send');
                            //}else{
                            //    console.log('message failed');
                            //}
                            console.log('message send');
                        }

                    });
                }
                console.log(chatResult);
                //console.log(chatResult[0].conversion_id);
            }
        });
    });

    client.on('ReadMessage', function (data, callback) {

        var is_delete = 0;
        var updateReadMessage = "UPDATE " + TABLE_CHAT_MODULE + " SET is_read=1 WHERE conversion_id = ? AND receiver_id = ? AND is_delete=" + is_delete + "";

        executeQuery(updateReadMessage, [data.conversion_id, data.receiver_id], function (err, result, fields) {
            if (err) {
                logger.error(new Error().stack + err);
                throw err;
            }
        });
    });

    function directPush(finalObj, payload) {
        console.log(finalObj.sender_id, 'sender_id');
        console.log(finalObj.receiver_id, 'receiver_id');
        var isSendBox = 0;

        var firstName = '';
        var lastName = '';
        var fullName = '';

        var otherUserImage = '';

        var notify_badge = 0;
        var conversion_badge = 0;

        var insertNotification = "INSERT INTO " + TABLE_NOTIFICATION + " (notification_type_id, sender_id, receiver_id, notification_type, created_date) " +
            "VALUES (?,?,?,7,?)";


        var deviceInfo = "SELECT device_token,device_type FROM " + TABLE_APP_TOKENS + " u " +
            "WHERE user_id = '" + finalObj.receiver_id + "' AND is_delete = '0' AND device_token IS NOT NULL";

        var userInfo = "SELECT u.firstname, u.lastname, u.profilepic, ui.device_token, ui.device_type FROM " + TABLE_USERS +
            " AS u LEFT JOIN " + TABLE_APP_TOKENS + " AS ui ON u.id=ui.user_id WHERE u.id = '" + finalObj.sender_id + "' AND u.is_delete = '0'";

        var conversionBadge = "SELECT COUNT(DISTINCT(conversion_id)) as conversion_badge FROM " + TABLE_CHAT_MODULE + " WHERE receiver_id = " + finalObj.receiver_id + " AND is_read = 0 AND is_delete = 0";

        var notificationBadge = "SELECT COUNT(*) as notify_badge FROM " + TABLE_NOTIFICATION + " WHERE receiver_id = " + finalObj.receiver_id + " AND is_delete = '0'";

        executeQuery(insertNotification, [finalObj.conversion_id, finalObj.sender_id, finalObj.receiver_id, finalObj.created_date], function (err, result, fields) {
            if (err) {
                logger.error(new Error().stack + err);
                throw err;
            }
        });

        executeQuery(notificationBadge, '', function (err, userResult, fields) {
            if (err) {
                logger.error(new Error().stack + err);
                throw err;
            } else {
                notify_badge = userResult[0].notify_badge;
                console.log(userResult, '11111');
            }
        });

        executeQuery(conversionBadge, '', function (err, userResult, fields) {
            if (err) {
                logger.error(new Error().stack + err);
                throw err;
            } else {
                conversion_badge = userResult[0].conversion_badge;
                console.log(userResult, '22222');
            }
        });

        executeQuery(deviceInfo, '', function (err1, userResult1, fields) {
            if (err1) {
                logger.error(new Error().stack + err1);
                throw err1;
            } else {

                executeQuery(userInfo, '', function (err2, userResult2, fields) {
                    if (err2) {
                        logger.error(new Error().stack + err2);
                        throw err2;
                    } else {
                        console.log(userResult2);
                        otherUserImage = userResult2[0].profile_pic;

                        firstName = userResult2[0].firstname;
                        lastName = userResult2[0].lastname;

                        fullName = firstName + ' ' + lastName;

                        if (userResult1[0].device_type == 'ios') {
                            var options = {
                                token: {
                                    key: "../PushNotificationKey/AuthKey_8MBQDVN8PW.p8",
                                    keyId: "8MBQDVN8PW",
                                    teamId: "2HGNKSHWWK"
                                },
                                production: true //false
                            };

                            var apnProvider = new apns.Provider(options);

                            if (apnProvider == null) {
                            }
                            var dict = {};
                            var data = {};

                            payload = {
                                "notification_type": NOTIFICATION_MESSAGE_TYPE,
                                "message_id": finalObj.message_id,
                                "serder_id": finalObj.sender_id,
                                "receiver_id": finalObj.receiver_id,
                                "user_id": finalObj.receiver_id,
                                "other_user_id": finalObj.sender_id,
                                "other_user_first_name": firstName,
                                "other_user_last_name": lastName,
                                "other_user_profile_pic": otherUserImage,
                                "conversion_id": finalObj.conversion_id,
                                "message_type": finalObj.message_type,
                                "message": finalObj.message,
                                "created_date": finalObj.created_date,
                                "is_testdata": finalObj.is_testdata,
                                "media": finalObj.media
                            };
                            dict['payload'] = payload;

                            var note = new apns.Notification();
                            note.expiry = Math.floor(Date.now() / 1000) + 3600; // Expires 1 hour from now.
                            //note.title = "Peak";
                            //note.sound = "ping.aiff";
                            note.badge = parseInt(conversion_badge); //parseInt(notify_badge) + 
                            note.alert = fullName + ' sent you a message.';//'\ud83d\ude0a';//"\uD83D\uDCE7 \u2709 You have a new message. ";
                            note.payload = dict;//[{'messageFrom': PayloadData },{'type':"ChatMSG"}];
                            note.topic = "com.PeakDemo";

                            console.log(note, '000000');
                            apnProvider.send(note, userResult1[0].device_token).then(function (notificationResult) {
                                // Check the result for any failed devices
                                apnProvider.shutdown();
                            });
                            return true;
                        } else if (userResult1[0].device_type == 'android') {

                            payload = {
                                "notification_type": NOTIFICATION_MESSAGE_TYPE,
                                "message_id": finalObj.message_id,
                                "sender_id": finalObj.sender_id,
                                "receiver_id": finalObj.receiver_id,
                                "other_user_first_name": firstName,
                                "other_user_last_name": lastName,
                                "other_user_profile_pic": otherUserImage,
                                "conversion_id": finalObj.conversion_id,
                                "message_type": finalObj.message_type,
                                "message": finalObj.message,
                                "notification_message": fullName + ' sent you a message.',
                                "created_date": finalObj.created_date,
                                "is_testdata": finalObj.is_testdata,
                                "media": finalObj.media
                            };

                            var device_token = userResult1[0].device_token;
                            console.log(device_token);

                            var fcm = new FCM(FCM_SERVER_KEY);
                            var message = {
                                to: device_token,
                                priority: "high",
                                data: JSON.stringify(payload)

                            };
                            console.log(message);
                            fcm.send(message, function (err, response) {
                                if (err) {
                                    console.log(err);
                                    return false;
                                } else {
                                    console.log("Successfully sent with response: ", response);
                                    return true;
                                }
                            });

                        } else {
                            return false;
                        }

                    }
                });
            }
        });
    }

    client.on('disconnect', function (data, callback) {

        console.log("****Disconnect soket****");

        var deleteUser = isInArray(client.user_id, users);
        console.log(deleteUser);
        console.log(users, 'avalible user 1');
        if (deleteUser == true) {
            if (typeof client.user_id === "undefined") {

            } else {
                //delete user from group.
                console.log("user " + client.user_id);
                console.log(users, 'avalible user before delete');
                // delete users[client.user_id];
                deleteFromArray(users, client.user_id);
                console.log("users " + users);
                console.log(users, 'avalible user after delete');
            }
        }
        else {
        }
        console.log(users, 'avalible user 2');
    });

    function getCurrentUTCDateTime() {
        return new Date().toISOString().replace(/T/, ' ').// replace T with a space
            replace(/\..+/, '');
    }

    function isInArray(user, userArray) {
        //return userArray.indexOf(user) > -1;

        var length = userArray.length;
        for (var i = 0; i < length; i++) {
            if (userArray[i] == user)
                return true;
        }
        return false;
    }

    function deleteFromArray(my_array, element) {
        const index = my_array.indexOf(element);
        my_array.splice(index, 1);
    }

    function executeQuery(sql, parma, sql_rescponce_callack) {
        pool.getConnection(function (err, connection) {
            if (err) {
                logger.error(sql + '  : getConnection THROW :' + err);
                return;
            }
            var query = connection.query(sql, parma, sql_rescponce_callack);
            if (typeof query === "undefined") {

            } else {
                query.on('error', function (err) {
                    logger.error(sql + ' : query FROM :' + err);
                    throw err;
                });
                query.on('end', function () {
                    connection.release();
                });
            }
        });
    }

    function stringToInt($string) {
        return parseInt($string);
    }
});