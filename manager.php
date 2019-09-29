<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 20:29
 */

require "utils/Validator.php";
require "utils/controller.php";
require "utils/CommonUtils.php";
require "utils/ApiUtils.php";


class manager extends controller
{

    private $managerDao;

    function __construct()
    {
        global $managerDao;
        $this->managerDao = $managerDao;
    }


    function info()
    {

        if (array_key_exists("manager", $_SESSION)) {
            die(json_encode(Msg::success($_SESSION["manager"])));
        } else {
            die(json_encode(Msg::failed("获取失败，请先登录")));
        }
    }

    function login()
    {
        Validator::notEmpty(array("name", "password"));

        if ($result = $this->managerDao->login($_REQUEST["name"], $_REQUEST["password"])) {
            $_SESSION["manager"] = $result;
            if (array_key_exists("type", $_REQUEST) && ($_REQUEST["type"] == 1 || $_REQUEST["type"] == "1")) {
                setcookie("identity", md5(md5($result["name"])) . "_" . md5(md5($result["password"]))
                    , time() + 3600 * 24 * 30 * 12 * 5);
            }

            if (CommonUtils::getRecordLiveState()->living == 1) {
                ApiUtils::start_local_live();
            }

            die(json_encode(Msg::success($result)));
        } else {
            die(json_encode(Msg::failed("管理员账号或密码有误")));
        }
    }


    function logout()
    {
        unset($_SESSION["manager"]);
        die(json_encode(Msg::success("注销成功")));
    }


    function add()
    {
        if ($_SESSION["manager"]["type"] == 0) {
            die(json_encode(Msg::failed("非法操作")));
        }
        Validator::notEmpty(array("name", "password"));
        if ($this->managerDao->exists($_REQUEST["name"])) {
            die(json_encode(Msg::failed("操作失败，该管理员已存在")));
        }
        $this->managerDao->add($_REQUEST["name"], $_REQUEST["password"]);
        die(json_encode(Msg::success("添加管理员成功")));
    }

    function list()
    {
        if ($_SESSION["manager"]["type"] == 0) {
            die(json_encode(Msg::failed("非法操作")));
        }
        die(json_encode(Msg::success($this->managerDao->list())));
    }


    function delete()
    {
        Validator::notEmpty(array("id"));

        if ($_SESSION["manager"]["type"] == 0) {
            die(json_encode(Msg::failed("非法操作")));
        }

        $admin = $this->managerDao->get($_REQUEST["id"]);
        if (!$admin) {
            die(json_encode(Msg::failed("该管理员不存在")));
        }
        if ($admin["type"] == 1) {
            die(json_encode(Msg::failed("不能删除超级管理员")));
        }
        $this->managerDao->delete($_REQUEST["id"]);
        die(json_encode(Msg::success("删除管理员成功")));
    }


    function update()
    {
        Validator::notEmpty(array("id", "name", "password"));
        if (!key_exists("oldPassword", $_REQUEST)) {
            die(json_encode(Msg::failed("旧密码不能为空")));
        }

        $id = $_REQUEST["id"];
        $name = $_REQUEST["name"];
        $password = $_REQUEST["password"];
        $oldPassword = $_REQUEST["oldPassword"];


        $admin = $this->managerDao->getByIdAndPassword($id, $oldPassword);
        if (!$admin) {
            die(json_encode(Msg::failed("旧密码有误")));
        }
        $temp = $this->managerDao->getByName($name);
        if ($temp != null && $temp["id"] != $id) {
            die(json_encode(Msg::failed("该管理员名已被使用")));
        }
        $this->managerDao->update($id, $name, $password);

        $_SESSION["manager"] = $this->managerDao->get($id);
        die(json_encode(Msg::success("修改成功")));
    }


}

$manager = new manager();
$manager->$action();
