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
 * @copyright 2019 University of Bath
 * @package local\moodle_data_importer
 */
class local_data_importer_connectorpathitem {

    const DB_SETTINGS = 'local_data_importer_setting';

    /**
     * @var integer
     */
    public $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var integer
     */
    private $connectorid;
    /**
     * @var string
     */
    private $pathitem;
    /**
     * @var string
     */
    private $httpmethod;
    /**
     * @var string
     */
    private $plugincomponent;
    /**
     * @var bool
     */
    private $active;
    /**
     * @var integer
     */
    private $timecreated;
    /**
     * @var integer
     */
    private $importorder;
    /**
     * @var string
     */
    private $dbtable;

    /**
     * connectorPathItem constructor.
     * @param $dbtable
     */
    public function __construct() {
        $this->dbtable = 'local_data_importer_path';
    }

    /**
     * @return integer
     */
    public function get_id() : int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_dbtable() {
        return $this->dbtable;
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
    public function get_name() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function set_name($name) {
        $this->name = $name;
    }

    /**
     * @return integer
     */
    public function get_connector_id() : int {
        return $this->connectorid;
    }

    /**
     * @param integer $connectorid
     */
    public function set_connector_id($connectorid) {
        $this->connectorid = $connectorid;
    }

    /**
     * Return Path Item instance
     * @return string
     */
    public function get_path_item() {
        return $this->pathitem;
    }

    /**
     * Set Path Item instance
     * @param string $pathitem
     */
    public function set_path_item($pathitem) {
        $this->pathitem = $pathitem;
    }

    /**
     * @return string
     */
    public function get_http_method() {
        return $this->httpmethod;
    }

    /**
     * @param string $httpmethod
     */
    public function set_http_method($httpmethod) {
        $this->httpmethod = $httpmethod;
    }

    /**
     * @return string
     */
    public function get_plugin_component() {
        return $this->plugincomponent;
    }

    /**
     * @param string $plugincomponent
     */
    public function set_plugin_component($plugincomponent) {
        $this->plugincomponent = $plugincomponent;
    }

    /**
     * @return bool
     */
    public function get_active() : bool {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function set_active($active) {
        $this->active = $active;
    }

    /**
     * @return integer
     */
    public function get_time_created() : int {
        return $this->timecreated;
    }

    /**
     * @param integer $timecreated
     */
    public function set_time_created($timecreated) {
        $this->timecreated = $timecreated;
    }
    /**
     * @return integer
     */
    public function get_import_order() : int {
        return $this->importorder;
    }

    /**
     * @param integer
     */
    public function set_import_order($importorder) {
        $this->importorder = $importorder;
    }

    /**
     * Get all available Path Items from the database table
     * @throws Exception if there are no path items to return.
     * @return array of importers/path items
     */
    public function get_all($activeonly = false) {
        global $DB;

        $pathitems = array();
        if ($activeonly == true) {
            $conditions = array("active" => 1);
        } else {
            $conditions = null;
        }

        $pathitemrecords = $DB->get_records($this->dbtable, $conditions, 'importorder ASC');

        if (is_array($pathitemrecords) && count($pathitemrecords) > 0) {
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
                $pathiteminstance->set_import_order($recordobject->importorder);
                $pathitems[] = $pathiteminstance;
            }
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

        $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
        // TODO - what happens if id not found? - should it throw exception as not expected
        // Take the db object and turn it into this class object.
        $pathitem = new self();
        $pathitem->set_id($recordobject->id);
        $pathitem->set_name($recordobject->name);
        $pathitem->set_connector_id($recordobject->connectorid);
        $pathitem->set_active($recordobject->active);
        $pathitem->set_plugin_component($recordobject->plugincomponent);
        $pathitem->set_http_method($recordobject->httpmethod);
        $pathitem->set_path_item($recordobject->pathitem);
        $pathitem->set_import_order($recordobject->importorder);
        $pathitem->set_time_created($recordobject->timecreated);

        return $pathitem;
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
            $records = $DB->get_records($this->dbtable, ['plugincomponent' => $subplugin], 'importorder ASC');
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
                    $pathitem->set_import_order($recordobject->importorder);
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

                // Delete associated pathitem parameter mappings.
                $pathitemparameters = new local_data_importer_pathitem_parameter();
                $parameters = $pathitemparameters->get_by_pathitem_id($this->id);
                foreach ($parameters as $parameter) {
                    $parameter->delete();
                }

                // Delete associated pathitem response mappings.
                $pathitemresponses = new local_data_importer_pathitem_response();
                $responses = $pathitemresponses->get_by_pathitem_id($this->id);
                foreach ($responses as $response) {
                    $response->delete();
                }

                // Delete associated additional settings for this pathitem.
                $DB->delete_records(self::DB_SETTINGS, ['pathitemid' => $this->id]);
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
            $data->timecreated = time();
            $data->importorder = $this->get_next_importorder();
            try {
                $id = $DB->insert_record($this->dbtable, $data, $returnid);
                $this->id = $id;
                return $id;
                // Log it.
            } catch (\dml_exception $e) {
                // Log it.
                print $e->getMessage();
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Move the pathitem importorder up or down the list
     * @param string $direction 'up' or 'down'
     * @return boolean true if item has been moved otherwise return false.
     */
    public function reorder_import($direction) : bool {
        global $DB;

        $id = $this->get_id();
        $currentorder = $this->get_import_order();

        if ($direction == 'up') {
            $sqlorder = 'DESC';
            $sqlwhere = "importorder < " . $currentorder;
        } else if ($direction == 'down') {
            $sqlorder = 'ASC';
            $sqlwhere = "importorder > " . $currentorder;
        } else {
            // Direction not specified.
            return false;
        }

        $target = $DB->get_record_sql("
                SELECT id, importorder
                FROM {" . $this->dbtable . "}
                WHERE " . $sqlwhere . "
                ORDER BY importorder " . $sqlorder . "
                LIMIT 1
                ");

        if (empty($target)) {
            // No pathitem to swap with.
            return false;
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            $update = array("id" => $id, "importorder" => "-1");
            $DB->update_record($this->dbtable, $update);
            $update = array("id" => $target->id, "importorder" => $currentorder);
            $DB->update_record($this->dbtable, $update);
            $update = array("id" => $id, "importorder" => $target->importorder);
            $DB->update_record($this->dbtable, $update);

            $transaction->allow_commit();
            $this->set_import_order($target->importorder);
            return true;
        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Return the next available value for importorder i.e. current max value plus one.
     * @return integer
     */
    private function get_next_importorder(): int {
        global $DB;

        $maxorder = $DB->get_field_sql("SELECT MAX(importorder) FROM {" . $this->dbtable . "}");
        if (is_null($maxorder)) {
            $importorder = 1;
        } else {
            $importorder = $maxorder + 1;
        }
        return $importorder;
    }

    /**
     * Sets the start time for an importer when it runs.
     * @param $time
     * @return void
     */
    public function set_start_time($time) {
        global $DB;

        $update = new stdClass();
        $update->id = $this->id;
        $update->timelastrun = $time;
        $DB->update_record($this->dbtable, $update);
    }

    /**
     * Sets the duration the pathitem took to run.
     * @param $duration
     * @return void
     */
    public function set_duration_time($duration) {
        global $DB;

        $update = new stdClass();
        $update->id = $this->id;
        $update->timelasttaken = $duration;
        $DB->update_record($this->dbtable, $update);
    }
}