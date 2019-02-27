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

/**
 * A class that gets all enabled pathitems in the prdefined order and initiates
 * the collection of external data from each.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $pathitems = $connectorpathitem->get_all(true);

        foreach ($pathitems as $pathitem) {
            $starttime = time();
            // Run importer.
            try {
                $datafetcher = new local_data_importer_data_fetcher($pathitem->id);
                $datafetcher->update_from_pathitem();
            } catch (Exception $e) {
                // TODO - what sort of exceptions would we get here?
            } finally {
                $endtime = time();
                $timetaken = $endtime - $starttime;

                // Log stuff.
            }
        }
    }
}