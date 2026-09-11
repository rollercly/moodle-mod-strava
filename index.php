<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once('lib.php');

$courseid = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);

$context = context_course::instance($course->id);

$PAGE->set_url('/mod/strava/index.php', ['id' => $courseid]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_strava'));

$instances = strava_get_course_instances($course->id);

if (!$instances) {
    echo $OUTPUT->notification(get_string('noinstances', 'mod_strava'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('stravaname', 'mod_strava'), get_string('activitytype', 'mod_strava'),
        get_string('targetdate', 'mod_strava')];

    foreach ($instances as $instance) {
        $cm = get_coursemodule_from_instance('strava', $instance->id, $course->id);
        $url = new moodle_url('/mod/strava/view.php', ['id' => $cm->id]);

        $table->data[] = [
            html_writer::link($url, format_string($instance->name)),
            get_string('type_' . strtolower($instance->activitytype), 'mod_strava'),
            userdate($instance->targetdate, get_string('strftimedate', 'langconfig')),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
