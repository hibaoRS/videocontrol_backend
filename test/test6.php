<?php

$res = "1920x1080(1080p)";

$res = explode("(", $res)[0];
$res = explode("x", $res);
var_dump($res);

$switch = '0';
var_dump($switch ? true : false);