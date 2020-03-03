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

        let senderId = data.senderId;
        let receiverId = data.receiverId;
        let last_message = data.last_message;
        let group_name = data.group_name ? data.group_name : "";
        let is_group = data.is_group ? data.is_group : 0;
        let is_testdata = data.is_testdata ? data.is_testdata : 0;
        var created_date = getCurrentUTCDateTime();

        var chatRoomQuery = "select * from chat_room where (sender_id='" + senderId + "' AND receiver_id='" + receiverId + "') OR (receiver_id='" + senderId + "' and sender_id='" + receiverId + "')";
        executeQuery(chatRoomQuery, '', function (err, chatRoomResult, fields) {

            //length will be 0 if chat is not intialize yet
            if (chatRoomResult.length == 0) {

                //insert record into chatRoom table
                var insertChatRoomQuery = "insert into chat_room (last_message,group_name,is_group,sender_id,receiver_id,is_testdata) values('" + last_message + "','" + group_name + "','" + is_group + "'," + senderId + "," + receiverId + ",'" + is_testdata + "')";
                executeQuery(insertChatRoomQuery, '', function (err, insertChatRoomResult, fields) {
                    let is_sent = isInArray(receiverId, users);
                    //insert into chat message table
                    var insertChatMessageQuery = "insert into chat_messages (sender_id,receiver_id,crid,message,is_group,is_sent,is_testdata) values(" + senderId + "," + receiverId + "," + insertChatRoomResult['insertId'] + ",'" + last_message + "','" + is_group + "','" + is_sent + "','" + is_testdata + "')";
                    executeQuery(insertChatMessageQuery, '', function (err, insertChatMessageResult, fields) {
                        var userAvailable = isInArray(receiverId, users);
                        let finalObj = {
                            conversion_id: stringToInt(insertChatRoomResult['insertId']),
                            message_id: stringToInt(insertChatMessageResult['insertId']),
                            sender_id: stringToInt(receiverId),
                            receiver_id: stringToInt(senderId),
                            message_type: 'text',
                            message: last_message,
                            created_date: created_date,
                            is_testdata: stringToInt(is_testdata)
                        }
                        if (userAvailable === true) {
                            io.in(receiverId).emit("ReceiveMessage", finalObj);
                            if (typeof callback === "function") {
                                callback({ status: SUCCESS, data: finalObj })
                            }
                        } else {
                            if (typeof callback === "function") {
                                callback({ status: SUCCESS, data: finalObj })
                            }
                        }
                    })

                }, err => {
                    if (typeof callback === "function") {
                        callback({ status: FAILED })
                    }
                })

            } else {
                //update chatroom and insert new record into chat message table with chat room id
                var updateChatRoomQuery = "update chat_room set sender_id=" + senderId + ",receiver_id=" + receiverId + ",last_message='" + last_message + "' where id=" + chatRoomResult[0].id + "";
                executeQuery(updateChatRoomQuery, '', function (err, updateChatRoomResult, fields) {
                    let is_sent = isInArray(receiverId, users);
                    //insert into chat message table
                    var insertChatMessageQuery = "insert into chat_messages (sender_id,receiver_id,crid,message,is_group,is_sent,is_testdata) values(" + senderId + "," + receiverId + "," + chatRoomResult[0].id + ",'" + last_message + "','" + is_group + "','" + is_sent + "','" + is_testdata + "')";
                    executeQuery(insertChatMessageQuery, '', function (err, insertChatMessageResult, fields) {
                        var userAvailable = isInArray(receiverId, users);
                        let finalObj = {
                            conversion_id: stringToInt(chatRoomResult[0].id),
                            message_id: stringToInt(insertChatMessageResult['insertId']),
                            sender_id: stringToInt(receiverId),
                            receiver_id: stringToInt(senderId),
                            message_type: 'text',
                            message: last_message,
                            created_date: created_date,
                            is_testdata: stringToInt(is_testdata)
                        }
                        if (userAvailable === true) {
                            io.in(receiverId).emit("ReceiveMessage", finalObj);
                            if (typeof callback === "function") {
                                callback({ status: SUCCESS, data: finalObj })
                            }
                        } else {
                            if (typeof callback === "function") {
                                callback({ status: SUCCESS, data: finalObj })
                            }
                        }
                    })

                }, err => {
                    if (typeof callback === "function") {
                        callback({ status: FAILED, err: err })
                    }
                })
            }
        })
    });

    client.on('ReadMessage', function (data, callback) {

        let updateQuery = "update chat_messages set is_read=1 where crid=" + data.crid + " AND receiver_id=" + data.userId + " AND is_delete=" + data.is_delete + "";
        executeQuery(updateQuery, '', function (err, readMessagesResult, fields) {
            if (typeof callback === "function") {
                callback({ status: SUCCESS, data: readMessagesResult });
            }
        }, err => {
            if (typeof callback === "function") {
                callback({ status: FAILED, err: err });
            }
        })
    });

    client.on('disconnect', function (data, callback) {

        console.log("****Disconnect soket****", client.user_id);

        var deleteUser = isInArray(client.user_id, users);
        if (deleteUser == true) {
            if (typeof client.user_id === "undefined") {

            } else {
                //delete user from group.
                console.log("user " + data.user_id);
                console.log(users, 'avalible user before delete');
                // delete users[client.user_id];
                deleteFromArray(users, data.user_id);
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