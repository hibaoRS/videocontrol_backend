<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/6
 * Time: 14:05
 */

header("Content-Type:text/html; charset=gb2312");

error_reporting(E_ALL);

set_time_limit(0);

ob_implicit_flush();

$address = "127.0.0.1";
$port = 10086;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}


if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
    return;
}

do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\r\n";
        break;
    }

    $msg = "\r\nWelcome to the PHP test Server.\r\n" .
        "To quit, type 'quit'. To shut down the server type 'shutdown'.\r\n";
    socket_write($msgsock, $msg, strlen($msg));


    do {

        if (false === ($buff = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
            echo "socket_read() failed: reason:" . socket_strerror(socket_last_error($msgsock)) . "\r\n";
        }

        if (!$buff = trim($buff)) {
            continue;
        }
        if ($buff == "quit") {
            break;
        }
        if ($buff == "shutdown") {
            socket_close($msgsock);
            break 2;
        }
        $talkback = "PHP: You said '$buff'" . "\r\n";
        socket_write($msgsock, $talkback, strlen($talkback));

        echo "$buff\r\n";
    } while (true);
    socket_close($msgsock);

} while (true);

socket_close($sock);