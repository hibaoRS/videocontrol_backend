<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2018/5/20
 * Time: 12:33
 */

//加密测试
require "./test4.php";
require "../utils/Des.php";

echo bin2hex(base64_decode(openssl_encrypt("1", 'des-ecb', "12345678")));
echo "<br/>";
echo bin2hex(encrypt("1", "12345678"));
// 使用方式
$Des = new Des();

