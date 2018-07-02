<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 21:10
 */


class Validator
{

    static function notEmpty($array, $keys, $msgs = array())
    {
        $errors = array();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array) || trim($array[$key]) == "") {
                $msg = "不能为空";
                if (array_key_exists($key, $msgs)) {
                    $msg = $msgs[$key] . $msg;
                }
                $errors[$key] = $msg;
            }
        }
        if (count($errors) > 0) {
            die(json_encode(Msg::validate($errors)));
        }
    }


    static function atLeastOne($array, $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array) && trim(strval($array[$key])) != "") {
                return true;
            }
        }
        return false;
    }

}