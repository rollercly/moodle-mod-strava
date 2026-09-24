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
 * Library of interface functions and constants for mod_strava.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Indica que funcionalidades soporta el modulo.
 */
function strava_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO        => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_GRADE_HAS_GRADE  => true,
        FEATURE_GRADE_OUTCOMES   => false,
        FEATURE_BACKUP_MOODLE2   => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES    => true,
        MOD_PURPOSE_ASSESSMENT   => true,
        default                  => null,
    };
}

/**
 * Crea una nueva instancia de strava.
 */
function strava_add_instance(stdClass $data, $form = null): int {
    global $DB;

    $data->timecreated  = time();
    $data->timemodified = $data->timecreated;

    $data->id = $DB->insert_record('strava', $data);

    strava_grade_item_update($data);

    return $data->id;
}

/**
 * Actualiza una instancia existente.
 */
function strava_update_instance(stdClass $data, $form = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('strava', $data);

    strava_grade_item_update($data);

    return true;
}

/**
 * Elimina una instancia y sus datos asociados.
 */
function strava_delete_instance(int $id): bool {
    global $DB;

    $instance = $DB->get_record('strava', ['id' => $id]);
    if (!$instance) {
        return false;
    }

    $DB->delete_records('strava_grade', ['stravaid' => $id]);
    $DB->delete_records('strava', ['id' => $id]);

    strava_grade_item_delete($instance);

    return true;
}

/**
 * Da de alta o actualiza el grade_item en el libro de calificaciones.
 */
function strava_grade_item_update(stdClass $instance, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [
        'itemname'  => clean_param($instance->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax'  => $instance->grade,
        'grademin'  => 0,
    ];

    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/strava', $instance->course, 'mod', 'strava',
        $instance->id, 0, $grades, $item);
}

/**
 * Elimina el grade_item asociado a una instancia.
 */
function strava_grade_item_delete(stdClass $instance): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/strava', $instance->course, 'mod', 'strava',
        $instance->id, 0, null, ['deleted' => 1]);
}

/**
 * Empuja las notas guardadas en strava_grade hacia el libro de calificaciones.
 * Se invoca desde la scheduled task tras calcular resultados.
 *
 * @param stdClass $instance registro de la tabla strava.
 * @param int|null $userid si se indica, solo actualiza ese usuario.
 */
function strava_update_grades(stdClass $instance, ?int $userid = null): void {
    global $DB;

    $params = ['stravaid' => $instance->id];
    if ($userid) {
        $params['userid'] = $userid;
    }

    $results = $DB->get_records('strava_grade', $params);
    if (!$results) {
        return;
    }

    $grades = [];
    foreach ($results as $result) {
        if ($result->rawgrade === null) {
            continue;
        }
        $grades[$result->userid] = (object) [
            'userid'    => $result->userid,
            'rawgrade'  => $result->rawgrade,
        ];
    }

    if ($grades) {
        strava_grade_item_update($instance, $grades);
    }
}

/**
 * Listado de instancias para index.php.
 */
function strava_get_course_instances(int $courseid): array {
    global $DB;
    return $DB->get_records('strava', ['course' => $courseid], 'id ASC');
}

/**
 * Reglas de finalizacion (completion) personalizadas: completado cuando
 * existe una calificacion (graded) para el usuario, no solo al visitar.
 */
function strava_get_completion_state(stdClass $course, cm_info $cm, int $userid, bool $type): bool {
    global $DB;

    $instance = $DB->get_record('strava', ['id' => $cm->instance], '*', MUST_EXIST);
    if (empty($instance->completionsubmit)) {
        return $type;
    }

    return $DB->record_exists('strava_grade', [
        'stravaid' => $instance->id,
        'userid'   => $userid,
        'status'   => 'graded',
    ]);
}
