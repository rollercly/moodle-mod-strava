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
 * List of Strava challenges in a course.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
