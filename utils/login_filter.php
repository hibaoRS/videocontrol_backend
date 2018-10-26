<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/5
 * Time: 10:31
 */

require __DIR__ . "/../dao/ManagerDao.php";
require __DIR__ . "/headers.php";
$managerDao = new ManagerDao();


if ($action != "login" && $_SERVER["REQUEST_METHOD"] != "OPTIONS") {
    if (!isset($_SESSION["manager"])) {
        $bool = true;
        if (array_key_exists("identity", $_COOKIE)) {
            $result = $managerDao->verify(
                substr($_COOKIE["identity"], 0, 32)
                , substr($_COOKIE["identity"], 33, 64));
            if ($result != null) {
                $_SESSION["manager"] = $result;
                $bool = false;
            }
        }
        if ($bool) {
            if ($action != "reboot" && $action != "powerOff") {
                die(json_encode(Msg::failed("请先登录")));
            }
        }
    }
}
