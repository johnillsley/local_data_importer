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
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $pathitemid;
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
    private $pathitemparameter;
    /**
     * @var string
     */
    private $subpluginparameter;
    /**
     * @var string
     */
    private $dbtable;

    /**
     * local_data_importer_pathitem_parameter constructor.
     */
    public function __construct() {
        $this->dbtable = 'local_data_importer_param';
    }

    /**
     * @return string
     */
    public function get_pathitem_parameter() {
        return $this->pathitemparameter;
    }

    /**
     * @param string $pathitemparameter
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
     * @return integer
     */
    public function get_pathitemid(): int {
        return $this->pathitemid;
    }

    /**
     * @param integer $pathitemid
     */
    public function set_pathitemid(int $pathitemid) {
        $this->pathitemid = $pathitemid;
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
    public function get_subplugin_parameter() {
        return $this->subpluginparameter;
    }

    /**
     * @param string $subpluginparameter
     */
    public function set_subplugin_parameter($subpluginparameter) {
        $this->subpluginparameter = $subpluginparameter;
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
            $pathitemparaminstance->set_pathitem_parameter($recordobject->pathitemparameter);
            $pathitemparaminstance->set_subplugin_parameter($recordobject->subpluginparameter);
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
                    $pathitemparaminstance->set_id($recordobject->id);
                    $pathitemparaminstance->set_pathitemid($recordobject->pathitemid);
                    $pathitemparaminstance->set_pathitem_parameter($recordobject->pathitemparameter);
                    $pathitemparaminstance->set_subplugin_parameter($recordobject->subpluginparameter);
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
        $data->pathitemparameter = $this->pathitemparameter;
        $data->subpluginparameter = $this->subpluginparameter;
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
            $data->timecreated = $data->timemodified;
            try {
                $id = $DB->insert_record($this->dbtable, $data, $returnid);
                $this->id = $id;
                return $id;
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