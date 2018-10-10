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
    public function getDbtable() {
        return $this->dbtable;
    }

    /**
     * @param string $dbtable
     */
    public function setDbtable($dbtable) {
        $this->dbtable = $dbtable;
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
    public function getId() {
        return $this->id;
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
    public function getPathitemid() {
        return $this->pathitemid;
    }

    /**
     * @param mixed $pathitemid
     */
    public function setPathitemid($pathitemid) {
        $this->pathitemid = $pathitemid;
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

    /**
     * @return mixed
     */
    public function getTimemodified() {
        return $this->timemodified;
    }

    /**
     * @param mixed $timemodified
     */
    public function setTimemodified($timemodified) {
        $this->timemodified = $timemodified;
    }

    /**
     * @return mixed
     */
    public function getPathparam() {
        return $this->pathparam;
    }

    /**
     * @param mixed $pathparam
     */
    public function setPathparam($pathparam) {
        $this->pathparam = $pathparam;
    }

    /**
     * @return mixed
     */
    public function getComponentparam() {
        return $this->componentparam;
    }

    /**
     * @param mixed $componentparam
     */
    public function setComponentparam($componentparam) {
        $this->componentparam = $componentparam;
    }

    public function save($returnid = false) {
        global $DB;
        $data = new \stdclass();
        $data->pathitemid = $this->pathitemid;
        $data->pathparam = $this->pathparam;
        $data->componentparam = $this->componentparam;
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