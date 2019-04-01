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
 * A class that handles errors and exceptions and logs them.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_data_importer_error_handler {

    const DB_ERROR_LOG = 'local_data_importer_errors';

    static public function log(\throwable $e, $pathitemid = null) {
        global $DB;

        $log = new stdClass();
        $log->pathitemid    = $pathitemid;
        $log->message       = $e->getMessage();
        $log->code          = $e->getCode();
        $log->file          = $e->getFile();
        $log->line          = $e->getLine();
        $log->trace         = $e->getTraceAsString();
        $log->time          = time();

        $DB->insert_record(self::DB_ERROR_LOG, $log);
    }
}