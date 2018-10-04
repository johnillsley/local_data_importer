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

namespace local\moodle_data_importer;

class connectorInstance {
    public $id;
    private $description;
    private $host;
    private $basepath;
    private $openapidefinitionurl;
    private $timecreated;
    private $timemodified;
    private $dbtable;

    /**
     * connectorInstance constructor.
     * @param $dbtable
     */
    public function __construct()
    {
        $this->dbtable = 'connector_instance';
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
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getBasepath() {
        return $this->basepath;
    }

    /**
     * @param mixed $basepath
     */
    public function setBasepath($basepath) {
        $this->basepath = $basepath;
    }

    /**
     * @return mixed
     */
    public function getOpenapidefinitionurl() {
        return $this->openapidefinitionurl;
    }

    /**
     * @param mixed $openapidefinitionurl
     */
    public function setOpenapidefinitionurl($openapidefinitionurl) {
        $this->openapidefinitionurl = $openapidefinitionurl;
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
}