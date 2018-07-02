<?php
/**
 * Created by PhpStorm.
 * User: 10324
 * Date: 2017/12/4
 * Time: 19:27
 */

require "conn.php";

class ManagerDao
{
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    function login($name, $password)
    {
        $stml = $this->db->prepare("select * from manager where name=:name and password=:password");
        $stml->bindValue(":name", $name);
        $stml->bindValue(":password", $password);
        return $stml->execute()->fetchArray(SQLITE3_ASSOC);
    }

    function getByIdAndPassword($id, $password)
    {
        $stml = $this->db->prepare("select * from manager where id=:id and password=:password");
        $stml->bindValue(":id", $id);
        $stml->bindValue(":password", $password);
        return $stml->execute()->fetchArray(SQLITE3_ASSOC);
    }


    function verify($name, $password)
    {
        $stml = $this->db->prepare("select * from manager");
        $result = $stml->execute();

        while ($resultArray = $result->fetchArray(SQLITE3_ASSOC)) {
            if (md5(md5($resultArray["name"])) == $name && md5(md5($resultArray["password"])) == $password) {
                return $resultArray;
            }
        }
        return null;
    }


    function add($name, $password)
    {
        $stml = $this->db->prepare("insert into manager(name,password) values (:name,:password)");
        $stml->bindValue(":name", $name);
        $stml->bindValue(":password", $password);
        return $stml->execute();
    }


    function delete($id)
    {
        $stml = $this->db->prepare("delete from manager where id=:id");
        $stml->bindValue(":id", $id);
        return $stml->execute();
    }

    function list()
    {
        $result = array();
        $queryResult = $this->db->query("select * from manager");
        while ($fetchArray = $queryResult->fetchArray(SQLITE3_ASSOC)) {
            array_push($result, $fetchArray);
        }
        return $result;
    }

    function exists($name)
    {
        $stml = $this->db->prepare("select * from manager where name=:name");
        $stml->bindValue(":name", $name);

        return $stml->execute()->fetchArray(SQLITE3_ASSOC)!=NULL;
    }

    function getByName($name)
    {
        $stml = $this->db->prepare("select * from manager where name=:name");
        $stml->bindValue(":name", $name);
        return $stml->execute()->fetchArray(SQLITE3_ASSOC);
    }


    function get($id){
        $stml = $this->db->prepare("select * from manager where id=:id");
        $stml->bindValue(":id", $id);

        return $stml->execute()->fetchArray(SQLITE3_ASSOC);
    }




    function update($id,$name,$password){
        $stml = $this->db->prepare("update  manager set name=:name,password=:password where id=:id");
        $stml->bindValue(":name", $password);
        $stml->bindValue(":password", $name);
        $stml->bindValue(":id", $id);
        return $stml->execute();
    }


}