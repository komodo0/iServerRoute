<?php

class State{

    private $connection_ip;
    private $connection_login;
    private $connection_password;
	private $connection_db;
	
    public function setConnection($db_ip, $db_login, $db_password, $db_name){
        $connection = new mysqli($db_ip, $db_login, $db_password);
        if ($connection->connect_errno) {
            return false;
        } else {
            if (!$db_ip){
                $this->connection_ip = "localhost";
            } else {
                $this->connection_ip = $db_ip;
            }
            $this->connection_login = $db_login;
            $this->connection_password = $db_password;
			$this->connection_db = $db_name;
            return true;
        }
    }

    public function unsetConnection(){
        unset($this->connection_ip);
        unset($this->connection_login);
        unset($this->connection_password);
		unset($this->connection_db);
    }

    public function isConnected(){
        $connection = new mysqli($this->connection_ip, $this->connection_login, $this->connection_password);
        if ($connection->connect_errno) {
            return false;
        } else {
            return true;
        }
    }

    public function getIp(){
        return $this->connection_ip;
    }
	
	public function getDbName(){
		return $this->connection_db;
	}

    public function getObjectsList(){
        try {
            $connection = new mysqli($this->connection_ip, $this->connection_login, $this->connection_password);
            $connection->set_charset("utf8");
            $objects = false;
            $request = "SELECT id, obj FROM " . $this->getDbName() . ".obj";
            $query = $connection->query($request);
            if ($query){
                if ($query->num_rows >0){
                    $objects = array();
                    while($row = $query->fetch_assoc()){
                        if ($row['id'] != -1) {
                            array_push($objects, [$row['id'], $row['obj']]);
                        }
                    }
                }
                $query->close();
            }
            return $objects;
        } catch (Exception $e) {
            return false;
        }
    }

    private $begin_date;
    private $end_date;
    private $object_id;
    private $zero_delete;
    private $data;

    public function getObjectId(){
        return $this->object_id;
    }

    public function getBeginDate(){
        if (isset($this->begin_date)){
            return $this->begin_date;
        } else {
            return "";
        }

    }

    public function getEndDate(){
        if (isset($this->end_date)){
            return $this->end_date;
        } else {
            return "";
        }
    }

    public function getZeroDelete(){
        if ($this->zero_delete == "on"){
            return "checked";
        } else {
            return "";
        }
    }


    //Преобразует формат координат к читаемому яндекс-картами
    public function latToOk($lat){
        $cel = intval($lat/100);
        $dec = ($lat - $cel*100)/60;
        return $cel + $dec;
    }

    public function setCoordinates($object_id, $begin_date, $end_date, $zero_delete){
        $this->object_id = $object_id;
        $this->begin_date = $begin_date;
        $this->end_date = $end_date;
        $this->zero_delete = $zero_delete;
    }

    public function unsetCoordinates(){
        if (isset($this->begin_date)) {unset($this->begin_date);}
        if (isset($this->end_date)) {unset($this->end_date);}
        if (isset($this->object_id)) {unset($this->object_id);}
        if (isset($this->zero_delete)) {unset($this->zero_delete);}
    }


    public function coordinatesAreSet(){
        if (isset($this->object_id) && isset($this->begin_date) && isset($this->end_date) && isset($this->zero_delete)){
            return true;
        } else {
            return false;
        }
    }

    //Выдает массив координат из множества
    function getCoordinatesArray(){
        $coordinates = array();
        $this->data = array();
        try {
            $connection = new mysqli($this->connection_ip, $this->connection_login, $this->connection_password);
            $connection->set_charset("utf8");
            $request = "SELECT id_obj, rec_date, lat, lon, rid FROM " . $this->getDbName() . ".records WHERE id_obj = ". $this->object_id ." AND rec_date BETWEEN STR_TO_DATE('". $this->begin_date ."', '%Y-%m-%d %H:%i') AND STR_TO_DATE('". $this->end_date ."', '%Y-%m-%d %H:%i') LIMIT 1000;";
            $query = $connection->query($request);
            if ($query){
                if ($query->num_rows >0){
                    $coordinates = array();
                    while($row = $query->fetch_assoc()){
                        if (($this->zero_delete=="on") && ($row["lat"] == 0)){

                        } else {
                            array_push($coordinates, array($this->latToOk($row["lat"]), $this->latToOk($row["lon"])));
                            array_push($this->data, array($this->latToOk($row["lat"]), $this->latToOk($row["lon"]), $row["rec_date"], $row["rid"]));
                        }

                    }
                }
                $query->close();
            }
            return $coordinates;
        } catch (Exception $e) {
            return false;
        }
    }

    function getData(){
        return $this->data;
    }

}

?>



