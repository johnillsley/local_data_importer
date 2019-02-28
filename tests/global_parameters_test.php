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
 * Unit tests for the local/data_importer/classes/global_parameters.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class local_data_importer_global_parameters_test extends advanced_testcase {

    public function test_current_academic_year() {

        $this->resetAfterTest();

        $currentyear = date('Y');

        $testfirstday = date('m/d');
        set_config('academic_year_format', 'YYYY/Y', 'local_data_importer');
        set_config('academic_year_first_day', $testfirstday, 'local_data_importer');
        $currentacadyear = local_data_importer_global_parameters::current_academic_year();
        $this->assertEquals($currentacadyear, $currentyear . '/' . substr(($currentyear + 1), -1));

        $testfirstday = date('m/d', time() + 60 * 60 * 24); // Set first day of academic year to tomorrow.
        set_config('academic_year_format', 'YYYY/YY', 'local_data_importer');
        set_config('academic_year_first_day', $testfirstday, 'local_data_importer');
        $currentacadyear = local_data_importer_global_parameters::current_academic_year();
        $this->assertEquals($currentacadyear, ($currentyear - 1) . '/' . substr($currentyear, -2));
    }

    public function test_date_interval_codes() {
        global $DB;
        $this->resetAfterTest();

        // Set up test data.
        $DB->insert_records("local_data_importer_dates", array(
                        ['period_code' => 'AY',
                                'acyear' => '2018/9',
                                'start_date' => '2018-09-10 00:00:00',
                                'end_date' => '2019-09-30 00:00:00'],
                        ['period_code' => 'S2',
                                'acyear' => '2018/9',
                                'start_date' => '2019-01-14 00:00:00',
                                'end_date' => '2019-09-30 00:00:00'],
                        ['period_code' => 'M08',
                                'acyear' => '2018/9',
                                'start_date' => '2019-02-08 00:00:00',
                                'end_date' => '2019-03-21 00:00:00']
                )
        );

        // Configure settings to use sits_period table used by sits plugin.
        set_config('date_interval_table', 'local_data_importer_dates', 'local_data_importer');
        set_config('date_interval_code_field', 'period_code', 'local_data_importer');
        set_config('date_interval_start_date_field', 'start_date', 'local_data_importer');
        set_config('date_interval_end_date_field', 'end_date', 'local_data_importer');

        $dateintervals = local_data_importer_global_parameters::date_interval_codes(1549591200); // Time 2019-02-08T02:00:00+00:00.
        $this->assertEquals(count($dateintervals), 3);
        $this->assertFalse(isset(array_pop($dateintervals)->acadyear));

        $dateintervals = local_data_importer_global_parameters::date_interval_codes(1569808800); // Time 2019-09-30T02:00:00+00:00.
        $this->assertEquals(count($dateintervals), 2);

        $dateintervals = local_data_importer_global_parameters::date_interval_codes(1536544800); // Time 2018-09-10T02:00:00+00:00.
        $this->assertEquals(count($dateintervals), 1);

        set_config('date_interval_academic_year_field', 'acyear', 'local_data_importer');
        $dateintervals = local_data_importer_global_parameters::date_interval_codes(1549591200); // Time 2019-02-08T02:00:00+00:00.
        $this->assertEquals(count($dateintervals), 3);
        $this->assertTrue(isset(array_pop($dateintervals)->acadyear));
    }
}