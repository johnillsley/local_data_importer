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
defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_connectorinstance
 */
class local_data_importer_connectorinstance {
    /**
     * @var
     */
    public $id;
    /**
     * @var
     */
    private $description;
    /**
     * @var
     */
    private $server;
    /**
     * @var
     */
    private $openapikey;
    /**
     * @var
     */
    private $serverapikey;
    /**
     * @var
     */
    private $name;

    /**
     * @var
     */
    private $openapidefinitionurl;
    /**
     * @var
     */
    private $timecreated;
    /**
     * @var
     */
    private $timemodified;
    /**
     * @var string
     */
    private $dbtable;

    /**
     * connectorinstance constructor.
     * @param $dbtable
     */
    public function __construct() {
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
    public function get_openapi_key() {
        return $this->openapikey;
    }

    /**
     * @param mixed $openapikey
     */
    public function setopenapikey($openapikey) {
        $this->openapikey = $openapikey;
    }

    /**
     * @return mixed
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getdbtable() {
        return $this->dbtable;
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
    public function getserver() {
        return $this->server;
    }

    /**
     * @param mixed $host
     */
    public function setserver($server) {
        $this->server = $server;
    }

    /**
     * @return mixed
     */
    public function get_openapidefinitionurl() {
        return $this->openapidefinitionurl;
    }

    /**
     * @param mixed $openapidefinitionurl
     */
    public function set_openapidefinitionurl($openapidefinitionurl) {
        $this->openapidefinitionurl = $openapidefinitionurl;
    }

    /**
     * @return mixed
     */
    public function get_timecreated() {
        return $this->timecreated;
    }

    /**
     * @param mixed $timecreated
     */
    public function set_timecreated($timecreated) {
        $this->timecreated = $timecreated;
    }

    /**
     * @return mixed
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * @param mixed $timemodified
     */
    public function set_timemodified($timemodified) {
        $this->timemodified = $timemodified;
    }

    /**
     * @return mixed
     */
    public function get_server_apikey() {
        return $this->serverapikey;
    }

    /**
     * @param mixed $serverapikey
     */
    public function set_server_apikey($serverapikey) {
        $this->serverapikey = $serverapikey;
    }

    /**
     * @param $id
     * @return local_data_importer_connectorinstance
     */
    public function get_by_id($id) {

        global $DB;
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            if ($recordobject) {
                // Take the db object and turn it into this class object.
                $connectorinstance = new self();
                $connectorinstance->setid($recordobject->id);
                $connectorinstance->setname($recordobject->name);
                $connectorinstance->setdescription($recordobject->description);
                $connectorinstance->setserver($recordobject->server);
                $connectorinstance->set_openapidefinitionurl($recordobject->openapidefinitionurl);
                $connectorinstance->setopenapikey($recordobject->openapikey);
                $connectorinstance->set_server_apikey($recordobject->serverapikey);
            } else {
                $connectorinstance = false;
            }

            return $connectorinstance;
        } catch (\dml_exception $e) {
            echo "catch error";
            throw new \Exception($e);
        }

    }

    /** Return all connectors from the database
     * @return array|null
     */
    public function get_all() {
        global $DB;
        $connectors = null;
        try {
            $connectorrecords = $DB->get_records($this->dbtable);
            if ($connectorrecords && is_array($connectorrecords)) {
                foreach ($connectorrecords as $recordobject) {
                    $connectorinstance = new self();
                    $connectorinstance->setid($recordobject->id);
                    $connectorinstance->setname($recordobject->name);
                    $connectorinstance->setdescription($recordobject->description);
                    $connectorinstance->setserver($recordobject->server);
                    $connectorinstance->set_openapidefinitionurl($recordobject->openapidefinitionurl);
                    $connectorinstance->setopenapikey($recordobject->openapikey);
                    $connectorinstance->set_server_apikey($recordobject->serverapikey);
                    $connectorinstance->set_timemodified($recordobject->timemodified);
                    $connectors[] = $connectorinstance;
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $connectors;
    }

    /**
     * @param bool $returnid
     * @return bool|int
     */
    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->server = $this->server;
        $data->name = $this->name;
        $data->description = $this->description;
        $data->openapidefinitionurl = $this->openapidefinitionurl;
        $data->openapikey = $this->openapikey;
        $data->serverapikey = $this->serverapikey;
        $data->timecreated = $data->timemodified = time();
        if ($this->id) {
            // Its an update.
            $data->id = $this->id;
            try {
                return $DB->update_record($this->dbtable, $data);
                // Log it.
            } catch (\exception $e) {
                // Log it.
                return $e;
                var_dump($e->getmessage());
            }
        } else {
            $data->timecreated = $data->timemodified = time();
            try {
                return $DB->insert_record($this->dbtable, $data, $returnid);
                // Log it.
            } catch (\exception $e) {
                // Log it.
                return $e;
                var_dump($e->getmessage());
            }
        }

    }

    /**
     * @return bool
     */
    public function delete() {
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
}