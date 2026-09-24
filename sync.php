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
 * Lets a teacher force the synchronisation of a Strava challenge instance.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
