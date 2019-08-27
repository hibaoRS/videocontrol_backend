<?php
//TODO 修改接口地址
$api = "http://192.168.1.223:8081/";

class NetworkUtils
{

    static function get($url, $data = null)
    {
        $url = $GLOBALS["api"] . $url;
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $context = stream_context_create(array(
            "http" => array(
                "content" => $jsonData,
            )
        ));
        $result = @file_get_contents($url, false, $context);
        return $result;
    }

}

