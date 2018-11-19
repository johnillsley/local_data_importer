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
 * Class local_data_importer_pathitem_parameter
 */
class local_data_importer_pathitem_parameter {
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $pathitemid;
    /**
     * @var
     */
    private $timecreated;
    /**
     * @var
     */
    private $timemodified;
    /**
     * @var
     */
    private $pathitemparameter;
    /**
     * @var
     */
    private $pluginparamtable;
    /**
     * @var
     */
    private $pluginparamfield;
    /**
     * @var string
     */
    private $dbtable;


    /**
     * @return mixed
     */
    public function get_pathitem_parameter() {
        return $this->pathitemparameter;
    }

    /**
     * @param mixed $pathitemparameter
     */
    public function set_pathitem_parameter($pathitemparameter) {
        $this->pathitemparameter = $pathitemparameter;
    }

    /**
     * @return string
     */
    public function get_dbtable() {
        return $this->dbtable;
    }

    /**
     * local_data_importer_pathitem_parameter constructor.
     */
    public function __construct() {
        $this->dbtable = 'pathitem_parameter';
    }

    /**
     * @return mixed
     */
    public function get_id() {
        return $this->id;
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
    public function get_pathitemid(): int {
        return $this->pathitemid;
    }


    /**
     * @param int $pathitemid
     */
    public function set_pathitemid(int $pathitemid) {
        $this->pathitemid = $pathitemid;
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
    public function get_pluginparam_table() {
        return $this->pluginparamtable;
    }

    /**
     * @param mixed $pluginparamtable
     */
    public function set_pluginparam_table($pluginparamtable) {
        $this->pluginparamtable = $pluginparamtable;
    }

    /**
     * @return mixed
     */
    public function get_pluginparam_field() {
        return $this->pluginparamfield;
    }

    /**
     * @param mixed $pluginparamfield
     */
    public function set_pluginparam_field($pluginparamfield) {
        $this->pluginparamfield = $pluginparamfield;
    }


    /** Retrieve Path Item Parameter by Id.
     * @param int $id
     * @return local_data_importer_pathitem_parameter
     */
    public function get_by_id($id): local_data_importer_pathitem_parameter {
        global $DB;
        $pathitemparaminstance = new self();
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            $pathitemparaminstance->set_id($recordobject->id);
            $pathitemparaminstance->set_pathitemid($recordobject->pathitemid);
            $pathitemparaminstance->set_pathitem_parameter($recordobject->pathparameter);
            $pathitemparaminstance->set_pluginparam_table($recordobject->pluginparamtable);
            $pathitemparaminstance->set_pluginparam_field($recordobject->pluginparamfield);
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $pathitemparaminstance;
    }

    /** Retrieve Path Item Parameter by Path Item ID
     * @param $id
     * @return array
     */
    public function get_by_pathitem_id($id) {
        global $DB;
        $pathitemparams = array();
        try {
            $pathitemparamrecords = $DB->get_records($this->dbtable, ['pathitemid' => $id]);
            if ($pathitemparamrecords && is_array($pathitemparamrecords)) {
                foreach ($pathitemparamrecords as $recordobject) {
                    $pathitemparaminstance = new self();
                    $pathitemparaminstance->set_pathitemid($recordobject->pathitemid);
                    $pathitemparaminstance->set_id($recordobject->id);
                    $pathitemparaminstance->set_pathitem_parameter($recordobject->pathparameter);
                    $pathitemparaminstance->set_pluginparam_table($recordobject->pluginparamtable);
                    $pathitemparaminstance->set_pluginparam_field($recordobject->pluginparamfield);
                    $pathitemparams[] = $pathitemparaminstance;
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $pathitemparams;

    }

    /** Save a path item parameter instance
     * @param bool $returnid
     * @return bool|int
     */
    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->pathitemid = $this->pathitemid;
        $data->pathparameter = $this->pathitemparameter;
        $data->pluginparamtable = $this->pluginparamtable;
        $data->pluginparamfield = $this->pluginparamfield;
        $data->timemodified = time();
        if ($this->id) {
            // Its an update.
            $data->id = $this->id;
            try {
                return $DB->update_record($this->dbtable, $data);
                // Log it.
            } catch (\exception $e) {
                // Log it.
                var_dump($e->getmessage());
            }
        } else {
            $data->timecreated = $data->timemodified = time();
            try {
                return $DB->insert_record($this->dbtable, $data, $returnid);
                // Log it.
            } catch (\exception $e) {
                // Log it.
                var_dump($e->getmessage());
            }
        }

    }

    /**
     * Delete a connector response param test
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