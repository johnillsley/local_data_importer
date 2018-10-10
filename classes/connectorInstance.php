<?php
// this file is part of moodle - http://moodle.org/
//
// moodle is free software: you can redistribute it and/or modify
// it under the terms of the gnu general public license as published by
// the free software foundation, either version 3 of the license, or
// (at your option) any later version.
//
// moodle is distributed in the hope that it will be useful,
// but without any warranty; without even the implied warranty of
// merchantability or fitness for a particular purpose.  see the
// gnu general public license for more details.
//
// you should have received a copy of the gnu general public license
// along with moodle.  if not, see <http://www.gnu.org/licenses/>.

#namespace local\moodle_data_importer;

class local_moodle_data_importer_connectorinstance {
    public $id;
    private $description;
    private $host;
    private $apikey;
    private $name;
    private $basepath;
    private $openapidefinitionurl;
    private $timecreated;
    private $timemodified;
    private $dbtable;

    /**
     * connectorinstance constructor.
     * @param $dbtable
     */
    public function __construct()
    {
        $this->dbtable = 'connector_instance';
    }

    /**
     * @return mixed
     */
    public function getid() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setid($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getapikey() {
        return $this->apikey;
    }

    /**
     * @param mixed $apikey
     */
    public function setapikey($apikey) {
        $this->apikey = $apikey;
    }
    /**
     * @return mixed
     */
    public function getname() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setname($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getdescription() {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setdescription($description) {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function gethost() {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function sethost($host) {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getbasepath() {
        return $this->basepath;
    }

    /**
     * @param mixed $basepath
     */
    public function setbasepath($basepath) {
        $this->basepath = $basepath;
    }

    /**
     * @return mixed
     */
    public function getopenapidefinitionurl() {
        return $this->openapidefinitionurl;
    }

    /**
     * @param mixed $openapidefinitionurl
     */
    public function setopenapidefinitionurl($openapidefinitionurl) {
        $this->openapidefinitionurl = $openapidefinitionurl;
    }
     /**
     * @return mixed
     */
    public function gettimecreated() {
        return $this->timecreated;
    }

    /**
     * @param mixed $timecreated
     */
    public function settimecreated($timecreated) {
        $this->timecreated = $timecreated;
    }

    /**
     * @return mixed
     */
    public function gettimemodified() {
        return $this->timemodified;
    }

    /**
     * @param mixed $timemodified
     */
    public function settimemodified($timemodified) {
        $this->timemodified = $timemodified;
    }

    public function getconnectorinstancebyid($id){

        global $db;
        try{
            $recordobject = $db->get_record($this->dbtable,['id'=>$id]);
            //take the db object and turn it into this class object
            $connectorinstance = new local_moodle_data_importer_connectorinstance();
            $connectorinstance->setid($recordobject->id);
            $connectorinstance->setname($recordobject->name);
            $connectorinstance->setdescription($recordobject->description);
            $connectorinstance->sethost($recordobject->host);
            $connectorinstance->setbasepath($recordobject->basepath);
            $connectorinstance->setopenapidefinitionurl($recordobject->openapidefinitionurl);
            return $connectorinstance;
        }
        catch (\dml_exception $e){
            echo $e->getmessage();
        }

    }
    public function save($returnid = false){
        global $db;
        $data = new \stdclass();
        $data->host = $this->host;
        $data->basepath = $this->basepath;
        $data->name = $this->name;
        $data->description = $this->description;
        $data->openapidefinitionurl = $this->openapidefinitionurl;
        $data->timecreated = $data->timemodified = time();
        if($this->id){
            //its an update
            $data->id = $this->id;
            try{
                return $db->update_record($this->dbtable, $data);
                //log it.
            }
            catch (\exception $e){
                //log it.
                var_dump($e->getmessage());
            }
        }
        else{
            $data->timecreated = $data->timemodified = time();
            try{
                return $db->insert_record($this->dbtable, $data,$returnid);
                //log it.
            }
            catch (\exception $e){
                //log it.
                var_dump($e->getmessage());
            }
        }

    }
}