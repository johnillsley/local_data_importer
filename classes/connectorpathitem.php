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
    /**
     * @var string
     */
    private $dbtable;

    /**
     * connectorPathItem constructor.
     * @param $dbtable
     */
    public function __construct() {
        $this->dbtable = 'connector_pathitem';
    }

    /**
     * @return mixed
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_dbtable() {
        return $this->dbtable;
    }

    /**
     * @param mixed $id
     */
    public function set_id($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function set_name($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function get_connector_id() {
        return $this->connectorid;
    }

    /**
     * @param mixed $connectorid
     */
    public function set_connector_id($connectorid) {
        $this->connectorid = $connectorid;
    }

    /**
     * Return Path Item instance
     * @return mixed
     */
    public function get_path_item() {
        return $this->pathitem;
    }

    /**
     * Set Path Item instance
     * @param mixed $pathitem
     */
    public function set_path_item($pathitem) {
        $this->pathitem = $pathitem;
    }

    /**
     * @return mixed
     */
    public function get_http_method() {
        return $this->httpmethod;
    }

    /**
     * @param mixed $httpmethod
     */
    public function set_http_method($httpmethod) {
        $this->httpmethod = $httpmethod;
    }

    /**
     * @return mixed
     */
    public function get_plugin_component() {
        return $this->plugincomponent;
    }

    /**
     * @param mixed $plugincomponent
     */
    public function set_plugin_component($plugincomponent) {
        $this->plugincomponent = $plugincomponent;
    }

    /**
     * @return mixed
     */
    public function get_active() {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function set_active($active) {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function get_time_created() {
        return $this->timecreated;
    }

    /**
     * @param mixed $timecreated
     */
    public function set_time_created($timecreated) {
        $this->timecreated = $timecreated;
    }

    /**
     * Get all available Path Items from the database table
     * @return array|null
     */
    public function get_all() {
        global $DB;
        $pathitems = null;
        try {
            $pathitemrecords = $DB->get_records($this->dbtable);
            if ($pathitemrecords && is_array($pathitemrecords)) {
                foreach ($pathitemrecords as $recordobject) {
                    $pathiteminstance = new self();
                    $pathiteminstance->set_id($recordobject->id);
                    $pathiteminstance->set_name($recordobject->name);
                    $pathiteminstance->set_plugin_component($recordobject->plugincomponent);
                    $pathiteminstance->set_active($recordobject->active);
                    $pathiteminstance->set_http_method($recordobject->httpmethod);
                    $pathiteminstance->set_path_item($recordobject->pathitem);
                    $pathiteminstance->set_connector_id($recordobject->connectorid);
                    $pathiteminstance->set_time_created($recordobject->timecreated);
                    $pathitems[] = $pathiteminstance;
                }

            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $pathitems;
    }

    /**
     * Return a path item instance by id
     * @param $id
     * @return local_data_importer_connectorpathitem
     */
    public function get_by_id($id) {
        global $DB;
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            // Take the db object and turn it into this class object.
            $pathitem = new self();
            $pathitem->set_id($recordobject->id);
            $pathitem->set_name($recordobject->name);
            $pathitem->set_connector_id($recordobject->connectorid);
            $pathitem->set_active($recordobject->active);
            $pathitem->set_plugin_component($recordobject->plugincomponent);
            $pathitem->set_http_method($recordobject->httpmethod);
            $pathitem->set_path_item($recordobject->pathitem);
            return $pathitem;
        } catch (\dml_exception $e) {
            throw $e->getmessage();
        }
    }

    /**
     * Return a path item instance by subplugin
     * @param $subplugin
     * @return array
     */
    public function get_by_subplugin($subplugin) {
        global $DB;
        $pathitems = array();
        try {
            $records = $DB->get_records($this->dbtable, ['plugincomponent' => $subplugin]);
            if (is_array($records)) {
                foreach ($records as $recordobject) {
                    $pathitem = new self();
                    $pathitem->set_id($recordobject->id);
                    $pathitem->set_name($recordobject->name);
                    $pathitem->set_connector_id($recordobject->connectorid);
                    $pathitem->set_active($recordobject->active);
                    $pathitem->set_plugin_component($recordobject->plugincomponent);
                    $pathitem->set_http_method($recordobject->httpmethod);
                    $pathitem->set_path_item($recordobject->pathitem);
                    $pathitems[] = $pathitem;
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $pathitems;

    }

    /**
     * Delete a path item instance
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

    /**
     * Save a new connector path item instance
     * @param bool $returnid
     * @return mixed
     */
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
            // Its an update.
            $data->id = $this->id;
            try {
                return $DB->update_record($this->dbtable, $data);
                // Log it.
            } catch (\dml_exception $e) {
                // Log it.
                throw new Exception($e->getMessage());
            }
        } else {
            $data->timecreated = $data->timemodified = time();
            try {
                return $DB->insert_record($this->dbtable, $data, $returnid);
                // Log it.
            } catch (\dml_exception $e) {
                // Log it.
                throw new Exception($e->getMessage());
            }
        }
    }
}