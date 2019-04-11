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
 * A class that triggers the data import connection instances in the correct order.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_data_importer\task;
defined('MOODLE_INTERNAL') || die();

/**
 * A class that triggers the data import connection instances in the correct order.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class doimports extends \core\task\scheduled_task {

    /**
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_data_importer');
    }

    /**
     * Execute Scheduled Task
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/data_importer/classes/scheduler.php');
        $scheduler = new \local_data_importer_scheduler();
        $scheduler->start_data_imports();
    }
}