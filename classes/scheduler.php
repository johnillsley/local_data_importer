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

class local_data_importer_scheduler {

    public function __construct() {

    }

    /**
     * Cycles through the active connector path items and starts the import process
     * Logs all successes and failures
     */
    public function start_data_imports() {

        $connectorpathitem = new local_data_importer_connectorpathitem;
        $importers = $connectorpathitem->get_all(true);
        foreach ($importers as $importer) {
            $starttime = time();
            // Run importer.
            try {
                $getdata = new local_data_importer_data_fetcher($importer->id);
                $getdata->get_web_service_data();
            } catch (Exception $e) {
                
            } finally {
                $endtime = time();
                $timetaken = $endtime - $starttime;

                // Log stuff.
            }
        }
    }
}