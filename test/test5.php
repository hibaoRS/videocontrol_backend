<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/10/24
 * Time: 21:03
 */

$c = 0;
$time=time();
for ($i = 0; $i < 100; $i++) {
    if (!file_get_contents("http://127.0.0.1:8082/htdocs/videocontrol/main.php?action=login")) {
        ++$c;
        echo $c . " " . $i . "\r\n";
    }
}

var_dump(time() - $time);