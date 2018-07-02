<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 20:43
 */

class Msg
{
    public $code;
    public $data;

    public function __construct($code, $data)
    {
        $this->code = $code;
        $this->data = $data;
    }

    static function failed($data = null)
    {
        return new Msg(0, $data);
    }

    static function success($data = null)
    {
        return new Msg(1, $data);
    }

    static function validate($data = null)
    {
        return new Msg(-1, $data);
    }


}