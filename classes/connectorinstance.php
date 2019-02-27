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
     * @var integer
     */
    public $id;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $server;
    /**
     * @var string
     */
    private $openapikey;
    /**
     * @var string
     */
    private $serverapikey;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $openapidefinitionurl;
    /**
     * @var integer
     */
    private $timecreated;
    /**
     * @var integer
     */
    private $timemodified;
    /**
     * @var string
     */
    private $dbtable;

    /**
     * connectorinstance constructor.
     */
    public function __construct() {
        $this->dbtable = 'connector_instance';
    }

    /**
     * @return integer
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function set_id($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function get_openapi_key() {
        return $this->openapikey;
    }

    /**
     * @param string $openapikey
     */
    public function set_openapi_key($openapikey) {
        $this->openapikey = $openapikey;
    }

    /**
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_dbtable() {
        return $this->dbtable;
    }

    /**
     * @param string $name
     */
    public function set_name($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function set_description($description) {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function get_server() {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function set_server($server) {
        $this->server = $server;
    }

    /**
     * @return string $openapidefinitionurl
     */
    public function get_openapidefinitionurl() {
        return $this->openapidefinitionurl;
    }

    /**
     * @param string $openapidefinitionurl
     */
    public function set_openapidefinitionurl($openapidefinitionurl) {
        $this->openapidefinitionurl = $openapidefinitionurl;
    }

    /**
     * @return integer
     */
    public function get_timecreated() {
        return $this->timecreated;
    }

    /**
     * @param integer $timecreated
     */
    public function set_timecreated($timecreated) {
        $this->timecreated = $timecreated;
    }

    /**
     * @return integer
     */
    public function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * @param integer $timemodified
     */
    public function set_timemodified($timemodified) {
        $this->timemodified = $timemodified;
    }

    /**
     * @return string
     */
    public function get_server_apikey() {
        return $this->serverapikey;
    }

    /**
     * @param string $serverapikey
     */
    public function set_server_apikey($serverapikey) {
        $this->serverapikey = $serverapikey;
    }

    /**
     * @param integer $id
     * @return object local_data_importer_connectorinstance
     * @throws \Exception
     */
    public function get_by_id($id) {

        global $DB;
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            if ($recordobject) {
                // Take the db object and turn it into this class object.
                $connectorinstance = new self();
                $connectorinstance->set_id($recordobject->id);
                $connectorinstance->set_name($recordobject->name);
                $connectorinstance->set_description($recordobject->description);
                $connectorinstance->set_server($recordobject->server);
                $connectorinstance->set_openapidefinitionurl($recordobject->openapidefinitionurl);
                $connectorinstance->set_openapi_key($recordobject->openapikey);
                $connectorinstance->set_server_apikey($recordobject->serverapikey);
                $connectorinstance->set_timecreated($recordobject->timecreated);
                $connectorinstance->set_timemodified($recordobject->timemodified);
                // TODO - why do some methods have underscore and some don't?
            } else {
                $connectorinstance = false;
                // TODO - This should throw an exception to stop code continuing above.
                // Surely if you request object with invalid id something went wrong.
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
                    $connectorinstance->set_id($recordobject->id);
                    $connectorinstance->set_name($recordobject->name);
                    $connectorinstance->set_description($recordobject->description);
                    $connectorinstance->set_server($recordobject->server);
                    $connectorinstance->set_openapidefinitionurl($recordobject->openapidefinitionurl);
                    $connectorinstance->set_openapi_key($recordobject->openapikey);
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

    /** Delete the connector instance
     * @return bool
     * @throws \dml_exception
     * @throws \Exception
     */
    public function delete() {
        global $DB;
        $deleted = false;
        $pathitem = new local_data_importer_connectorpathitem();
        try {
            if ($DB->record_exists($pathitem->get_dbtable(), ['connectorid' => $this->id])) {
                // It is already used by a connnector , cannot delete.
                throw new \Exception("Cannot delete connector as it has Pathitems using it");
            } else {
                // Ok to delete connector.
                if ($DB->record_exists($this->dbtable, ['id' => $this->id])) {
                    $deleted = $DB->delete_records($this->dbtable, ['id' => $this->id]);
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }
        return $deleted;
    }
}