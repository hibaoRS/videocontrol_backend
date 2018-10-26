<?php

echo ipSubZero("192.001.01.1");

//ip格式测试
function ipSubZero($ip)
{
    $result = "";
    $array = explode(".", $ip);

    for ($j = 0; $j < sizeof($array); $j++) {
        $item = $array[$j];
        if (strlen($item) == 3) {
            if ($item[0] == "0") {
                $item = substr($item, 1, 2);
                if ($item[0] == "0") {
                    $item = substr($item, 1, 1);
                }
            }
        } else if (strlen($item) == 2) {
            if ($item[0] == "0") {
                $item = substr($item, 1, 1);
            }
        }
        $result .= $item;
        if ($j != sizeof($array) - 1) {
            $result .= ".";
        }
    }
    return $result;

}
