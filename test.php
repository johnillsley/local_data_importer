<?php

require_once("../../config.php");

$context = \context_system::instance();
$roles = get_assignable_roles($context);

print_r($roles);

$auths = core_component::get_plugin_list('auth');
$authoptions = array();
foreach($auths as $auth => $plugin) {
    if (is_enabled_auth($auth)) {
        $authoptions[$auth] = get_string('pluginname', "auth_{$auth}");
    }
}

print "<pre>";
print_r($authoptions);
print "</pre>";
global $CFG;
require_once($CFG->dirroot . '/enrol/dataimporter/lib.php');

$enrol = new enrol_dataimporter_plugin();
print $enrol->get_name();

$course = new stdClass();
$course->id = 22;
$enrolname = 'dataimporter';

print "<br/>" . enrol_is_enabled('manual');
print "<br/>" . enrol_is_enabled('dataimporter');


// Enabling enrol plugin.
$enabled = enrol_get_plugins(true);
$enabled = array_keys($enabled);
print_r($enabled);
print $CFG->enrol_plugins_enabled;
if (!in_array($enrolname, $enabled)) {
    // Add enrolment method to enabled.
    $syscontext = context_system::instance();
    $enabled[] = $enrolname;
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    core_plugin_manager::reset_caches();
    $syscontext->mark_dirty(); // resets all enrol caches
}

// Add enrol instance to a course.
$enrolmethods = enrol_get_instances($course->id, false);
$exists = false;
foreach ($enrolmethods as $enrolmethod) {
    if ($enrolmethod->enrol == $enrolname) {
        $exists = true;
        break;
    }
}
if (!$exists) {
    print "ADD INSTANCE";
    $enrol->add_instance($course);
}
$roleid = 5;
$userid = 3;
$instance = $DB->get_record('enrol', array("enrol" => $enrolname, "courseid" => $course->id));
$enrol->enrol_user($instance, $userid, $roleid, time());

$timeslots = get_config('local_data_importer');
print_r($timeslots);
// public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null)

// public function unenrol_user(stdClass $instance, $userid)






