<?php
// This file is part of Moodle - http://moodle.org/

// Permite al profesor forzar la sincronizacion de una instancia concreta
// sin esperar a la ejecucion nocturna de la scheduled task.
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/strava/classes/task/sync_activities.php');

$id = required_param('id', PARAM_INT);
require_sesskey();

[$course, $cm] = get_course_and_cm_from_cmid($id, 'strava');
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/strava:manualsync', $context);

$PAGE->set_url(new moodle_url('/mod/strava/sync.php', ['id' => $id]));
$PAGE->set_context($context);

$instance = $DB->get_record('strava', ['id' => $cm->instance], '*', MUST_EXIST);

$task = new \mod_strava\task\sync_activities();
$task->sync_instance($instance);

redirect(
    new moodle_url('/mod/strava/view.php', ['id' => $cm->id]),
    get_string('syncdone', 'mod_strava'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
