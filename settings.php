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
 * Global Settings for the local_data_importer plugin
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_category('local_data_importer',
            get_string('pluginname', 'local_data_importer')
    ));
    $settings = new admin_settingpage('local_data_importer', get_string('pluginname', 'local_data_importer'));
    // $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_data_importer/date_interval_table',
            get_string('dateintervaltable', 'local_data_importer'),
            get_string('dateintervaltable_desc', 'local_data_importer'), ''));

    $settings->add(new admin_setting_configtext('local_data_importer/date_interval_code_field',
            get_string('dateintervalcodefield', 'local_data_importer'),
            get_string('dateintervalcodefield_desc', 'local_data_importer'), ''));

    $settings->add(new admin_setting_configtext('local_data_importer/date_interval_start_date_field',
            get_string('dateintervalstartdatefield', 'local_data_importer'),
            get_string('dateintervalstartdatefield_desc', 'local_data_importer'), ''));

    $settings->add(new admin_setting_configtext('local_data_importer/date_interval_end_date_field',
            get_string('dateintervalenddatefield', 'local_data_importer'),
            get_string('dateintervalenddatefield_desc', 'local_data_importer'), ''));

    $settings->add(new admin_setting_configtext('local_data_importer/date_interval_academic_year_field',
            get_string('dateintervalacademicyearfield', 'local_data_importer'),
            get_string('dateintervalacademicyearfield_desc', 'local_data_importer'), ''));

    $settings->add(new admin_setting_configtext('local_data_importer/academic_year_format',
            get_string('academicyearformat', 'local_data_importer'),
            get_string('academicyearformat_desc', 'local_data_importer'), 'YYYY/Y'));

    $settings->add(new admin_setting_configtext('local_data_importer/academic_year_first_day',
            get_string('academicyearfirstday', 'local_data_importer'),
            get_string('academicyearfirstday_desc', 'local_data_importer'), '09/01'));
}