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
 * Class local_data_importer_pathitem_response
 */
class local_data_importer_pathitem_response {
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
    private $pathitemresponse;
    /**
     * @var
     */
    private $pluginresponsetable;
    /**
     * @var
     */
    private $pluginresponsefield;

    /**
     * @return mixed
     */
    public function get_pathitem_response() {
        return $this->pathitemresponse;
    }

    /**
     * @param mixed $pathitemresponse
     */
    public function set_pathitem_response($pathitemresponse) {
        $this->pathitemresponse = $pathitemresponse;
    }

    /**
     * @var string
     */
    private $dbtable;

    /**
     * @return string
     */
    public function get_dbtable() {
        return $this->dbtable;
    }

    /**
     * local_data_importer_connectorresponseparams constructor.
     */
    public function __construct() {
        $this->dbtable = 'pathitem_response';
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
    public function get_pluginresponse_table() {
        return $this->pluginresponsetable;
    }

    /**
     * @param mixed $pluginresponsetable
     */
    public function set_pluginresponse_table($pluginresponsetable) {
        $this->pluginresponsetable = $pluginresponsetable;
    }

    /**
     * @return mixed
     */
    public function get_pluginresponse_field() {
        return $this->pluginresponsefield;
    }

    /**
     * @param mixed $pluginresponsefield
     */
    public function set_pluginresponse_field($pluginresponsefield) {
        $this->pluginresponsefield = $pluginresponsefield;
    }

    /**
     * @param int $id
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
            $responseparaminstance->set_pluginresponse_table($recordobject->pluginresponsefield);
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
        return $responseparaminstance;
    }

    /** Get Path Item response by Path Item ID.
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
                    $responseparaminstance->set_pathitem_response($recordobject->response);
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

    /** Save a Path item response instance
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