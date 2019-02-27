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
 * @copyright 2019 University of Bath
 * Class local_data_importer_pathitem_response
 */
class local_data_importer_pathitem_response {
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
    private $pathitemresponse;
    /**
     * @var string
     */
    private $pluginresponsetable;
    /**
     * @var string
     */
    private $pluginresponsefield;
    /**
     * @var string
     */
    private $dbtable;

    /**
     * local_data_importer_pathitem_response constructor.
     */
    public function __construct() {
        $this->dbtable = 'pathitem_response';
    }

    /**
     * @return string
     */
    public function get_pathitem_response() {
        return $this->pathitemresponse;
    }

    /**
     * @param string $pathitemresponse
     */
    public function set_pathitem_response($pathitemresponse) {
        $this->pathitemresponse = $pathitemresponse;
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
    public function get_pluginresponse_table() {
        return $this->pluginresponsetable;
    }

    /**
     * @param string $pluginresponsetable
     */
    public function set_pluginresponse_table($pluginresponsetable) {
        $this->pluginresponsetable = $pluginresponsetable;
    }

    /**
     * @return string
     */
    public function get_pluginresponse_field() {
        return $this->pluginresponsefield;
    }

    /**
     * @param string $pluginresponsefield
     */
    public function set_pluginresponse_field($pluginresponsefield) {
        $this->pluginresponsefield = $pluginresponsefield;
    }

    /**
     * @param integer $id
     * @return local_data_importer_pathitem_response
     */
    public function get_by_id($id): local_data_importer_pathitem_response {
        global $DB;
        $responseparaminstance = new self();
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            $responseparaminstance->set_id($recordobject->id);
            $responseparaminstance->set_pathitemid($recordobject->pathitemid);
            $responseparaminstance->set_pathitem_response($recordobject->pathitemresponse);
            $responseparaminstance->set_pluginresponse_table($recordobject->pluginresponsetable);
            $responseparaminstance->set_pluginresponse_field($recordobject->pluginresponsefield);
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $responseparaminstance;
    }

    /**
     * Get Path Item response by Path Item ID.
     * @param $id
     * @return array
     */
    public function get_by_pathitem_id($id) {
        global $DB;
        $responseparams = array();
        try {
            $responseparamrecords = $DB->get_records($this->dbtable, ['pathitemid' => $id]);
            if ($responseparamrecords && is_array($responseparamrecords)) {
                foreach ($responseparamrecords as $recordobject) {
                    $responseparaminstance = new self();
                    $responseparaminstance->set_id($recordobject->id);
                    $responseparaminstance->set_pathitemid($recordobject->pathitemid);
                    $responseparaminstance->set_pathitem_response($recordobject->pathitemresponse);
                    $responseparaminstance->set_pluginresponse_table($recordobject->pluginresponsetable);
                    $responseparaminstance->set_pluginresponse_field($recordobject->pluginresponsefield);
                    $responseparams[] = $responseparaminstance;
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $responseparams;
    }


    /**
     * Get Path Item response by Path Item ID.
     * @param $id
     * @return array
     */
    public function get_lookups_for_pathitem($pathitemid) {
        global $DB;

        $responselookups = array();
        try {
            $responseparamrecords = $DB->get_records($this->dbtable, ['pathitemid' => $pathitemid]);
            if (is_array($responseparamrecords)) {
                foreach ($responseparamrecords as $param) {
                    $table      = $param->pluginresponsetable;
                    $field      = $param->pluginresponsefield;
                    $pathitem   = $param->pathitemresponse;
                    $responselookups[$table][$field] = $pathitem;
                }
            }
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $responselookups;
    }

    /**
     * Save a Path item response instance
     * @param bool $returnid
     * @return bool|int
     */
    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->pathitemid = $this->pathitemid;
        $data->pathitemresponse = $this->pathitemresponse;
        $data->pluginresponsetable = $this->pluginresponsetable;
        $data->pluginresponsefield = $this->pluginresponsefield;
        $data->timemodified = time();
        if ($this->id) {
            // Its an update.
            $data->id = $this->id;
            try {
                return $DB->update_record($this->dbtable, $data);
                // TODO Log it.
            } catch (\exception $e) {
                // TODO Log it.
                var_dump($e->getmessage());
            }
        } else {
            $data->timecreated = $data->timemodified = time();
            try {
                return $DB->insert_record($this->dbtable, $data, $returnid);
                // TODO Log it.
            } catch (\exception $e) {
                // TODO Log it.
                var_dump($e->getmessage());
            }
        }

    }

    /**
     * Delete a Path item response
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