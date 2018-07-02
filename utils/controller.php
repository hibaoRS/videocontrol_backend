<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/1/11
 * Time: 18:12
 */
require "login_filter.php";

class controller
{

    public function __call($name, $arguments)
    {
        die(json_encode(Msg::validate("参数有误")));
    }

}