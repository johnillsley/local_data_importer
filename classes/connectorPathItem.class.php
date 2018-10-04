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


/**
 * Class connectorPathItem
 * @package local\moodle_data_importer
 */
class connectorPathItem {
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
    private $dbtable;

    /**
     * connectorPathItem constructor.
     * @param $dbtable
     */
    public function __construct()
    {
        $this->dbtable = 'connector_pathitem';
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
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getConnectorid() {
        return $this->connectorid;
    }

    /**
     * @param mixed $connectorid
     */
    public function setConnectorid($connectorid) {
        $this->connectorid = $connectorid;
    }

    /**
     * @return mixed
     */
    public function getPathitem() {
        return $this->pathitem;
    }

    /**
     * @param mixed $pathitem
     */
    public function setPathitem($pathitem) {
        $this->pathitem = $pathitem;
    }

    /**
     * @return mixed
     */
    public function getHttpmethod() {
        return $this->httpmethod;
    }

    /**
     * @param mixed $httpmethod
     */
    public function setHttpmethod($httpmethod) {
        $this->httpmethod = $httpmethod;
    }

    /**
     * @return mixed
     */
    public function getPlugincomponent() {
        return $this->plugincomponent;
    }

    /**
     * @param mixed $plugincomponent
     */
    public function setPlugincomponent($plugincomponent) {
        $this->plugincomponent = $plugincomponent;
    }

    /**
     * @return mixed
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active) {
        $this->active = $active;
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


}