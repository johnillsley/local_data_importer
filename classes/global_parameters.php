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
 * A class that provides parameters that can be utilised by all sub plugins
 * to populate web service urls
 * All methods in this class must be static and return an array even if only one value is being returned.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_data_importer_global_parameters {

    /**
     * Returns the current academic year.
     * Uses plugin admin settings for academic_year_format & academic_year_first_day
     *
     * @throws Exception if admin settings are missing so academic year cannot be derived.
     * @return array of one string containing current academic year
     */
    public static function current_academic_year() {

        $yearformat = get_config('local_data_importer', 'academic_year_format');
        $yearstart = get_config('local_data_importer', 'academic_year_first_day');
        if (empty($yearformat) || empty($yearstart)) {
            throw new Exception('Admin plugin settings academic_year_format and academic_year_first_day are not set');
        }
        // Find delimiter in format.
        for ($i = 0; $i < strlen($yearformat); $i++) {
            $char = substr($yearformat, $i, 1);
            if (strtoupper($char) != "Y") {
                $delimiter = $char;
                break;
            }
        }
        $yearformatparts = explode($delimiter, $yearformat);
        $year1length = strlen($yearformatparts[0]);
        $year2length = strlen($yearformatparts[1]);

        $today = date("m/d");
        $currentyear = date("Y");

        if ($today >= $yearstart) {
            // Current year is first part of academic year.
            $acadyear = substr($currentyear, -1 * $year1length) . $delimiter . substr(($currentyear + 1), -1 * $year2length);
        } else {
            // Last year is first part of academic year.
            $acadyear = substr(($currentyear - 1), -1 * $year1length) . $delimiter . substr($currentyear, -1 * $year2length);
        }
        return array($acadyear);
    }

    /**
     * Returns an array of date intervals that span the time supplied
     * The table that has the date intervals must have a unique id column
     *
     * @param integer $time unixtime
     * @throws Exception if admin settings are missing and therefore can't access db data.
     * @return boolean|array of objects defining date intervals
     */
    public static function date_interval_codes($time = null) {
        global $DB;

        if (is_null($time)) {
            $time = time();
        }
        $table          = get_config('local_data_importer', 'date_interval_table');
        $namefield      = get_config('local_data_importer', 'date_interval_code_field');
        $startdatefield = get_config('local_data_importer', 'date_interval_start_date_field');
        $enddatefield   = get_config('local_data_importer', 'date_interval_end_date_field');
        $acadyear       = get_config('local_data_importer', 'date_interval_academic_year_field');

        // Check we have all the required plugin admin settings.
        if (!empty($table) && !empty($namefield) && !empty($startdatefield) && !empty($enddatefield)) {
            $select = 'id,
                ' . $namefield . ' code,
                CAST(' . $startdatefield . ' AS DATE) startdate,
                CAST(' . $enddatefield . ' AS DATE) enddate';
        } else {
            throw new Exception('Admin plugin settings are missing for global parameter date_interval_codes');
        }
        if (!empty($acadyear)) {
            $select .= ', ' . $acadyear . ' acadyear';
        }

        $date = date('Y-m-d', $time);
        $params = array('date1' => $date, 'date2' => $date);

        $dateintervals = $DB->get_records_sql("
                SELECT $select
                FROM {" . $table . "}
                WHERE CAST($startdatefield AS DATE) <= :date1
                AND CAST($enddatefield AS DATE) >= :date2
                ", $params);

        $codes = array();
        foreach ($dateintervals as $dateinterval) {
            $codes[] = $dateinterval->code;
        }
        return $codes;
    }
}