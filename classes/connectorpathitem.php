<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Class connectorPathItem
 * @package local\moodle_data_importer
 */
class local_data_importer_connectorpathitem {
    /**
     * @var
     */
    public $id;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $connectorid;
    /**
     * @var
     */
    private $pathitem;
    /**
     * @var
     */
    private $httpmethod;
    /**
     * @var
     */
    private $plugincomponent;
    /**
     * @var
     */
    private $active;
    /**
     * @var
     */
    private $timecreated;
    private $dbtable;

    /**
     * connectorPathItem constructor.
     * @param $dbtable
     */
    public function __construct()
    {
        $this->dbtable = 'connector_pathitem';
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }
    public function getdbtable(){
        return $this->dbtable;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getConnectorid() {
        return $this->connectorid;
    }

    /**
     * @param mixed $connectorid
     */
    public function setConnectorid($connectorid) {
        $this->connectorid = $connectorid;
    }

    /**
     * @return mixed
     */
    public function getPathitem() {
        return $this->pathitem;
    }

    /**
     * @param mixed $pathitem
     */
    public function setPathitem($pathitem) {
        $this->pathitem = $pathitem;
    }

    /**
     * @return mixed
     */
    public function getHttpmethod() {
        return $this->httpmethod;
    }

    /**
     * @param mixed $httpmethod
     */
    public function setHttpmethod($httpmethod) {
        $this->httpmethod = $httpmethod;
    }

    /**
     * @return mixed
     */
    public function getPlugincomponent() {
        return $this->plugincomponent;
    }

    /**
     * @param mixed $plugincomponent
     */
    public function setPlugincomponent($plugincomponent) {
        $this->plugincomponent = $plugincomponent;
    }

    /**
     * @return mixed
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active) {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getTimecreated() {
        return $this->timecreated;
    }

    /**
     * @param mixed $timecreated
     */
    public function setTimecreated($timecreated) {
        $this->timecreated = $timecreated;
    }
    public function getbyid($id){
        global $DB;
        try{
            $recordobject = $DB->get_record($this->dbtable,['id'=>$id]);
            //take the db object and turn it into this class object
            $pathitem = new self();
            $pathitem->setid($recordobject->id);
            $pathitem->setname($recordobject->name);
            $pathitem->setConnectorid($recordobject->connectorid);
            $pathitem->setActive($recordobject->active);
            $pathitem->setPlugincomponent($recordobject->plugincomponent);
            $pathitem->setHttpmethod($recordobject->httpmethod);
            $pathitem->setPathitem($recordobject->pathitem);
            return $pathitem;
        }
        catch (\dml_exception $e){
            echo $e->getmessage();
        }
    }
    public function delete(){
        global $DB;
        $deleted = false;
        try {
            if ($DB->record_exists($this->dbtable, ['id' => $this->id])) {
                $deleted = $DB->delete_records($this->dbtable, ['id' => $this->id]);
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }
        return $deleted;
    }
    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->name = $this->name;
        $data->connectorid = $this->connectorid;
        $data->pathitem = $this->pathitem;
        $data->httpmethod = $this->httpmethod;
        $data->plugincomponent = $this->plugincomponent;
        $data->active = $this->active;
        $data->timemodified = time();
        if ($this->id) {
            //its an update
            $data->id = $this->id;
            try {
                return $DB->update_record($this->dbtable, $data);
                //log it.
            } catch (\exception $e) {
                //log it.
                var_dump($e->getmessage());
            }
        } else {
            $data->timecreated = $data->timemodified = time();
            try {
                return $DB->insert_record($this->dbtable, $data, $returnid);
                //log it.
            } catch (\exception $e) {
                //log it.
                var_dump($e->getmessage());
            }
        }

    }
}