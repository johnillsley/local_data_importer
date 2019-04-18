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
$plugin->version = 2019020700;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2017111300;        // Requires this Moodle version
$plugin->component = 'importers_enrolment'; // Full name of the plugin (used for diagnostics).
$plugin->dependencies = array(
        'local_data_importer' => ANY_VERSION,
        'importers_course' => ANY_VERSION,
        'importers_user' => ANY_VERSION
);