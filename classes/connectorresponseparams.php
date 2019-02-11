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
 * Class local_data_importer_connectorresponseparams
 */
class local_data_importer_connectorresponseparams {
    private $id;
    private $pathitemid;
    private $timecreated;
    private $timemodified;
    private $pathparam;
    private $componentparam;
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
        $this->dbtable = 'connector_response_params';
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
     * @param $pathitemid
     * @return int
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
    public function get_pathparam(): string {
        return $this->pathparam;
    }

    /**
     * @param string $pathparam
     */
    public function set_pathparam(string $pathparam) {
        $this->pathparam = $pathparam;
    }

    /**
     * @return mixed
     */
    public function get_componentparam(): string {
        return $this->componentparam;
    }

    /**
     * @param mixed $componentparam
     */
    public function set_componentparam($componentparam) {
        $this->componentparam = $componentparam;
    }

    /**
     * @param int $id
     * @return local_data_importer_connectorresponseparams
     */
    public function get_by_id($id): local_data_importer_connectorresponseparams {
        global $DB;
        try {
            $recordobject = $DB->get_record($this->dbtable, ['id' => $id]);
            $responseparaminstance = new self();
            $responseparaminstance->set_id($recordobject->id);
            $responseparaminstance->set_pathitemid($recordobject->pathitemid);
            $responseparaminstance->set_pathparam($recordobject->pathparam);
            $responseparaminstance->set_componentparam($recordobject->componentparam);
            return $responseparaminstance;
        } catch (\dml_exception $e) {
            echo $e->getmessage();
        }
    }

    /**
     * @param bool $returnid
     * @return bool|int
     */
    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->pathitemid = $this->pathitemid;
        $data->pathparam = $this->pathparam;
        $data->componentparam = $this->componentparam;
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