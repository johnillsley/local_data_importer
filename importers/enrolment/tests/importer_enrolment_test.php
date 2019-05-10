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
 * Unit tests for the local_data_importer_importers_enrolment plugin.
 * There is a dependency on the importers_course sub-plugin.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer/importers/enrolment
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_data_importer_importers_enrolment_testcase extends advanced_testcase {

    const DB_CONNECTOR = 'local_data_importer_connect';
    const DB_PATHITEM = 'local_data_importer_path';
    const DB_RESPONSE = 'local_data_importer_resp';
    const DB_SETTINGS = 'local_data_importer_setting';
    const DB_INTERVALCODES = 'local_data_importer_dates';
    const DB_LOCAL_LOG = 'importers_enrolment';
    const DB_COURSE_LOG = 'importers_course';

    /**
     * @var integer
     */
    private $pathitemid;

    /**
     * @var array of user objects.
     */
    private $users;

    /**
     * @var object course.
     */
    private $course;

    /**
     * @var array of enrolment importer data.
     */
    private $enrolments;

    protected function setup() {
        global $DB;

        $connectorid = $DB->insert_record(self::DB_CONNECTOR,
                array(
                        "name"                  => "Connector",
                        "description"           => "Test",
                        "openapidefinitionurl"  => "https://api.swaggerhub.com/",
                        "openapikey"            => "ABC",
                        "server"                => "samis-dev-stutalk.bath.ac.uk",
                        "serverapikey"          => "XYZ",
                        "timemodified"          => 12345,
                        "timecreated"           => 12345
                )
        );

        $this->pathitemid = $DB->insert_record(self::DB_PATHITEM,
                array(
                        "name"              => "Test pathitem",
                        "connectorid"       => $connectorid,
                        "pathitem"          => "ABC",
                        "httpmethod"        => "get",
                        "plugincomponent"   => "importers_enrolment",
                        "active"            => 1,
                        "importorder"       => 1,
                        "timecreated"       => 12345
                )
        );

        $DB->insert_record(self::DB_PATHITEM, array(
                        'name'              => 'test course importer 1',
                        'connectorid'       => $connectorid,
                        'pathitem'          => '/something/{acyear}',
                        'httpmethod'        => 'GET',
                        'plugincomponent'   => 'importers_course',
                        'active'            => 1,
                        'importorder'       => 1,
                        'timecreated'       => 1234,
                        'timemodified'      => 1234
                )
        );

        // Enabling enrol plugin.
        $enrolname = 'dataimporter';
        $enabled = enrol_get_plugins(true);
        $enabled = array_keys($enabled);
        if (!in_array($enrolname, $enabled)) {
            // Add enrolment method to enabled.
            $syscontext = context_system::instance();
            $enabled[] = $enrolname;
            set_config('enrol_plugins_enabled', implode(',', $enabled));
            core_plugin_manager::reset_caches();
            $syscontext->mark_dirty(); // Resets all enrol caches.
        }
        $this->resetAfterTest();
    }

    public function test_get_database_properties() {
        require_once(__DIR__.'/fixtures/database_properties.php');

        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $class = new ReflectionClass('importers_enrolment_importer');
        $method = $class->getMethod('get_database_properties');
        $method->setAccessible(true);
        $method->invokeArgs($enrolmentimporter, array(1));

        $databaseproperties = $class->getProperty('databaseproperties');
        $databaseproperties->setAccessible(true);

        $this->assertEquals($expecteddbproperties, $databaseproperties->getValue($enrolmentimporter));
    }

    public function test_get_plugin_name() {

        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $pluginname = $enrolmentimporter->get_plugin_name();

        $this->assertEquals(get_string('pluginname', $enrolmentimporter->languagepack), $pluginname);
    }

    public function test_get_additional_form_elements() {
        global $DB;

        $DB->insert_records(self::DB_PATHITEM, array(
                array(
                        'name'              => 'test course importer 2',
                        'connectorid'       => 1,
                        'pathitem'          => '/something/{acyear}',
                        'httpmethod'        => 'GET',
                        'plugincomponent'   => 'importers_course',
                        'active'            => 1,
                        'importorder'       => 4,
                        'timecreated'       => 1234,
                        'timemodified'      => 1234
                ),
                array(
                        'name'              => 'test course importer 3',
                        'connectorid'       => 1,
                        'pathitem'          => '/something2/{acyear}',
                        'httpmethod'        => 'GET',
                        'plugincomponent'   => 'importers_course',
                        'active'            => 0,
                        'importorder'       => 5,
                        'timecreated'       => 1234,
                        'timemodified'      => 1234
                ),
        ));
        // Note, for testing one of the pathitems is not active.

        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $formdata = $enrolmentimporter->get_additional_form_elements();

        $roles = array();
        $rolerecords = $DB->get_records('role');
        foreach ($rolerecords as $rolerecord) {
            $roles[$rolerecord->id] = $rolerecord->shortname;
        }
        $roles2 = $roles; // Extra option needed for $roles2.
        array_unshift($roles2, get_string('unenrolmentdonothing', 'importers_enrolment'));

        $courseimporters = array(
                ($this->pathitemid + 1) => 'test course importer 1',
                ($this->pathitemid + 2) => 'test course importer 2',
                ($this->pathitemid + 3) => 'test course importer 3',
        ); // The first pathitemid is for the enrolment importer.

        $expected = array(
                'enrolment_roleid' => array(
                        'field_label'   => get_string('role', 'importers_enrolment'),
                        'field_type'    => 'select',
                        'options'       => $roles
                ),
                'course_pathitem_id' => array(
                        'field_label'   => get_string('courseimporter', 'importers_enrolment'),
                        'field_type'    => 'select',
                        'options'       => $courseimporters
                ),
                'group_name_format' => Array(
                        'field_label'   => get_string('groupnameformat', 'importers_enrolment'),
                        'field_type'    => 'text'
                ),
                'unenrolment_roleid' => Array(
                        'field_label'   => get_string('unenrolmentrole', 'importers_enrolment'),
                        'field_type'    => 'select',
                        'options'       => $roles2
                )
        );
        $this->assertEquals($expected, $formdata);
    }

    public function test_create_enrolments() {
        global $DB;

        $this->prepare_for_enrolments();
        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $sortedenrols = $enrolmentimporter->sort_items($this->enrolments);
        $enrolmentimporter->do_imports($sortedenrols);

        // Check enrolments.
        $enrols = $DB->get_records('user_enrolments');
        $this->assertEquals(3, count($enrols));

        // Check groups.
        $groups = $DB->get_records('groups');
        $this->assertEquals(2, count($groups));

        // Check groups membership.
        $groupmembers = $DB->get_records('groups_members');
        $this->assertEquals(3, count($groupmembers));

        // Check roles.
        $roles = $DB->get_records('role_assignments');
        $this->assertEquals(3, count($roles));
        foreach ($roles as $role) {
            $this->assertEquals(3, $role->roleid); // Student role.
            $this->assertEquals('enrol_dataimporter', $role->component);
        }

        // Check local log.
        $logs = $DB->get_records($enrolmentimporter->logtable);
        $this->assertEquals(3, count($logs));
        foreach ($logs as $log) {
            $this->assertEquals($this->pathitemid, $log->pathitemid); // Student role.
            $this->assertEquals('idtest', $log->course_idnumber);
            $this->assertEquals(0, $log->deleted);
        }
    }

    public function test_update_enrolments() {

        // Update enrolments probably won't be used in sub-plugin unless start/end dates are being derived from external data.
    }

    public function test_delete_enrolments() {
        global $DB;

        $this->prepare_for_enrolments();
        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $sortedenrols = $enrolmentimporter->sort_items($this->enrolments);
        $enrolmentimporter->do_imports($sortedenrols);

        // Remove two students from incoming enrolments then run again so that they are removed from Moodle.
        array_pop($this->enrolments);
        array_pop($this->enrolments);
        $sortedenrols = $enrolmentimporter->sort_items($this->enrolments);;
        $enrolmentimporter->do_imports($sortedenrols);

        // Check enrolments.
        $enrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($enrols));
        $enrol = array_pop($enrols);
        $enrolinstance = $DB->get_record('enrol', array('id' => $enrol->enrolid));
        $this->assertEquals('dataimporter', $enrolinstance->enrol);

        // Check roles.
        $roles = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roles));
        $role = array_pop($roles);
        $this->assertEquals(3, $role->roleid);

        // Check groups.
        $groups = $DB->get_records('groups');
        $this->assertEquals(1, count($groups));

        // Check groups members.
        $groupmembers = $DB->get_records('groups_members');
        $this->assertEquals(1, count($groupmembers));

        // Check local log.
        $logs = $DB->get_records($enrolmentimporter->logtable);
        $this->assertEquals(3, count($logs));

        // Delete the last enrolment using the create new role setting.
        $enrolmentimporter->save_setting("unenrolment_roleid", 5);
        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid); // Pick up new setting above.

        array_pop($this->enrolments);
        $sortedenrols = $enrolmentimporter->sort_items($this->enrolments);
        $enrolmentimporter->do_imports($sortedenrols);

        // Check enrolments.
        $enrols = $DB->get_records('user_enrolments');
        $this->assertEquals(1, count($enrols));
        $enrol = array_pop($enrols);
        $enrolinstance = $DB->get_record('enrol', array('id' => $enrol->enrolid));
        $this->assertEquals('manual', $enrolinstance->enrol);

        // Check roles.
        $roles = $DB->get_records('role_assignments');
        $this->assertEquals(1, count($roles));
        $role = array_pop($roles);
        $this->assertEquals(5, $role->roleid);
    }

    public function test_get_parameters() {
        global $DB;

        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        $coursepathitemid = $this->pathitemid + 1; // Use the second pathitemid.
        $enrolmentimporter->save_setting('course_pathitem_id', $coursepathitemid); // Connection to course importer.
        $enrolmentimporter->save_setting('course_pathitem_id', $coursepathitemid); // Group name format setting.

        // Create a couple of current interval codes.
        $yesterday = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") - 1, date("Y")));
        $tomorrow = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d") + 1, date("Y")));
        $DB->insert_records(self::DB_INTERVALCODES, array(
                array(
                        "period_code" => 'S1',
                        "acyear"      => '2018-9',
                        "start_date"  => $yesterday,
                        "end_date"    => $tomorrow,
                ),
                array(
                        "period_code" => 'M2',
                        "acyear"      => '2018-9',
                        "start_date"  => $yesterday,
                        "end_date"    => $tomorrow,
                )
        ));
        // Create some existing courses in the courses log with corresponding interval codes.
        $id1 = $DB->insert_record(self::DB_COURSE_LOG, array(
                "pathitemid"             => $coursepathitemid,
                "course_fullname"        => 'Test course 1',
                "course_shortname"       => 'TC1',
                "course_idnumber"        => 'C001',
                "course_categories_name" => 'Cat1',
                "other_academic_year"    => '2018-9',
                "other_timeslot"         => 'S1',
                "other_occurence"        => 'A',
                "timecreated"            => 1234,
                "timemodified"           => 1234
        ));
        $id2 = $DB->insert_record(self::DB_COURSE_LOG, array(
                "pathitemid"             => $coursepathitemid,
                "course_fullname"        => 'Test course 2',
                "course_shortname"       => 'TC2',
                "course_idnumber"        => 'C002',
                "course_categories_name" => 'Cat2',
                "other_academic_year"    => '2018-9',
                "other_timeslot"         => 'S1',
                "other_occurence"        => 'A',
                "timecreated"            => 1234,
                "timemodified"           => 1234
        ));
        $id3 = $DB->insert_record(self::DB_COURSE_LOG, array(
                "pathitemid"             => $coursepathitemid,
                "course_fullname"        => 'Test course 2',
                "course_shortname"       => 'TC2',
                "course_idnumber"        => 'C002',
                "course_categories_name" => 'Cat2',
                "other_academic_year"    => '2018-9',
                "other_timeslot"         => 'M2',
                "other_occurence"        => 'AF',
                "timecreated"            => 1234,
                "timemodified"           => 1234
        ));
        $id4 = $DB->insert_record(self::DB_COURSE_LOG, array(
                "pathitemid"             => $coursepathitemid,
                "course_fullname"        => 'Test course 3',
                "course_shortname"       => 'TC3',
                "course_idnumber"        => 'C003',
                "course_categories_name" => 'Cat2',
                "other_academic_year"    => '2018-9',
                "other_timeslot"         => 'S2',
                "other_occurence"        => 'A',
                "timecreated"            => 1234,
                "timemodified"           => 1234
        ));
        $expected = array(
                $id1 => (object)array(
                        "id" => $id1,
                        "course_idnumber"        => "C001",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "S1",
                        "other_occurence"        => "A",
                        "course_categories_name" => 'Cat1'
                ),
                $id2 => (object)array(
                        "id" => $id2,
                        "course_idnumber"        => "C002",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "S1",
                        "other_occurence"        => "A",
                        "course_categories_name" => 'Cat2'
                ),
                $id3 => (object)array(
                        "id" => $id3,
                        "course_idnumber"        => "C002",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "M2",
                        "other_occurence"        => "AF",
                        "course_categories_name" => 'Cat2'
                ),
        );
        $parameters = $enrolmentimporter->get_parameters();
        $this->assertEquals($expected, $parameters);

        // Test for parameter filter set to a department/course category.
        $enrolmentimporter->set_parameter_filter(array("course_categories_name" => "Cat2"));
        $expected = array(
                $id2 => (object)array(
                        "id" => $id2,
                        "course_idnumber"        => "C002",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "S1",
                        "other_occurence"        => "A",
                        "course_categories_name" => 'Cat2'
                ),
                $id3 => (object)array(
                        "id" => $id3,
                        "course_idnumber"        => "C002",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "M2",
                        "other_occurence"        => "AF",
                        "course_categories_name" => 'Cat2'
                )
        );
        $parameters = $enrolmentimporter->get_parameters();
        $this->assertEquals($expected, $parameters);

        // Test for parameter filter set to a a single unit/course.
        $enrolmentimporter->set_parameter_filter(array("course_idnumber" => "C001"));
        $expected = array(
                $id1 => (object)array(
                        "id" => $id1,
                        "course_idnumber"        => "C001",
                        "other_academic_year"    => "2018-9",
                        "other_timeslot"         => "S1",
                        "other_occurence"        => "A",
                        "course_categories_name" => 'Cat1'
                )
        );
        $parameters = $enrolmentimporter->get_parameters();
        $this->assertEquals($expected, $parameters);
    }

    private function prepare_for_enrolments() {
        global $DB;

        $this->users[] = $this->getDataGenerator()->create_user(array('idnumber' => 'user1'));
        $this->users[] = $this->getDataGenerator()->create_user(array('idnumber' => 'user2'));
        $this->users[] = $this->getDataGenerator()->create_user(array('idnumber' => 'user3'));

        $this->course = $this->getDataGenerator()->create_course(array('idnumber' => 'idtest'));

        // Add data_importer enrolment method.
        $enrol = new enrol_dataimporter_plugin();
        $enrol->add_instance($this->course);

        // Add response mappings for the unique fields. Needed for test.
        $DB->insert_records(self::DB_RESPONSE, array(
                array(
                        'pathitemid'          => $this->pathitemid,
                        'pathitemreponse'     => 'externalusername',
                        'pluginresponsetable' => 'user',
                        'pluginresponsefield' => 'username',
                        'timecreated'         => 1234,
                        'timemodified'        => 1234
                ),
                array(
                        'pathitemid'          => $this->pathitemid,
                        'pathitemreponse'     => 'externalcoursecode',
                        'pluginresponsetable' => 'course',
                        'pluginresponsefield' => 'idnumber',
                        'timecreated'         => 1234,
                        'timemodified'        => 1234
                ),
        ));
        $this->enrolments = array(
                array(
                        'user' => array(
                                'username' => $this->users[0]->username,
                                'idnumber' => $this->users[0]->idnumber
                        ),
                        'course' => array(
                                'idnumber' => $this->course->idnumber
                        ),
                        'other' => array(
                                'academic_year' => '2018/9',
                                'timeslot' => 'S1'
                        ),
                ),
                array(
                        'user' => array(
                                'username' => $this->users[1]->username,
                                'idnumber' => $this->users[1]->idnumber
                        ),
                        'course' => array(
                                'idnumber' => $this->course->idnumber
                        ),
                        'other' => array(
                                'academic_year' => '2018/9',
                                'timeslot' => 'S1'
                        ),
                ),
                array(
                        'user' => array(
                                'username' => $this->users[2]->username,
                                'idnumber' => $this->users[2]->idnumber
                        ),
                        'course' => array(
                                'idnumber' => $this->course->idnumber
                        ),
                        'other' => array(
                                'academic_year' => '2018/9',
                                'timeslot' => 'S2'
                        ),
                ),
        );
        $enrolmentimporter = new importers_enrolment_importer($this->pathitemid);
        // Create setting to define the user role for these enrolments.
        $enrolmentimporter->save_setting("enrolment_roleid", 3);
        // Create setting to define the group name format.
        $enrolmentimporter->save_setting("group_name_format", 'Test group {other_academic_year} - {other_timeslot}');
    }
}