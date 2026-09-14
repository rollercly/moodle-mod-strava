<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'strava');
$instance = $DB->get_record('strava', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/strava:view', $context);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/strava/view.php', ['id' => $id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));

if ($instance->intro) {
    echo $OUTPUT->box(format_module_intro('strava', $instance, $cm->id), 'generalbox mod_introbox');
}

// --- Ventana de la prueba -------------------------------------------------
$windowstart = $instance->targetdate - ($instance->tolerancedays * DAYSECS);
$windowend   = $instance->targetdate + ($instance->tolerancedays * DAYSECS) + DAYSECS;

echo html_writer::tag('p', get_string('viewwindow', 'mod_strava', (object) [
    'from' => userdate($windowstart, get_string('strftimedate', 'langconfig')),
    'to'   => userdate($windowend, get_string('strftimedate', 'langconfig')),
    'type' => get_string('type_' . strtolower($instance->activitytype), 'mod_strava',
        null, true) ?: $instance->activitytype,
]));

// --- Estado de vinculacion con Strava -------------------------------------
if (!\local_stravaauth\api_client::is_connected($USER->id)) {
    $connecturl = new moodle_url('/local/stravaauth/connect.php', ['returnurl' => $PAGE->url->out_as_local_url(false)]);
    echo $OUTPUT->notification(get_string('notconnected', 'mod_strava'), 'warning');
    echo html_writer::link($connecturl, get_string('connecttostrava', 'local_stravaauth'),
        ['class' => 'btn btn-primary']);
} else {
    echo $OUTPUT->notification(get_string('connected', 'mod_strava'), 'success');
}

// --- Resultado del alumno (si ya se ha calculado) -------------------------
if (has_capability('mod/strava:submit', $context, null, false)) {
    $result = $DB->get_record('strava_grade', ['stravaid' => $instance->id, 'userid' => $USER->id]);

    echo $OUTPUT->heading(get_string('yourresult', 'mod_strava'), 3);

    if (!$result || $result->status === 'pending') {
        echo $OUTPUT->notification(get_string('resultpending', 'mod_strava',
            userdate($windowend)), 'info');
    } else if ($result->status === 'notsubmitted') {
        echo $OUTPUT->notification(get_string('resultnotsubmitted', 'mod_strava'), 'warning');
    } else {
        $percentage = $instance->grade > 0 ? round(($result->rawgrade / $instance->grade) * 100, 1) : 0;

        $table = new html_table();
        $table->head = [get_string('metric', 'mod_strava'), get_string('objective', 'mod_strava'),
            get_string('achieved', 'mod_strava')];

        $breakdown = json_decode($result->breakdown ?? '{}', true) ?: [];
        foreach ($breakdown as $metric => $row) {
            $table->data[] = [
                get_string('obj' . $metric, 'mod_strava'),
                $row['target'],
                $row['actual'] . ' (' . round($row['ratio'] * 100) . '%)',
            ];
        }

        echo html_writer::tag('p', get_string('finalgrade', 'mod_strava',
            (object) ['grade' => $result->rawgrade, 'max' => $instance->grade, 'pct' => $percentage]));
        echo html_writer::table($table);
    }
}

// --- Panel de profesor: forzar sincronizacion manual -----------------------
if (has_capability('mod/strava:manualsync', $context)) {
    $syncurl = new moodle_url('/mod/strava/sync.php', ['id' => $cm->id, 'sesskey' => sesskey()]);
    echo $OUTPUT->single_button($syncurl, get_string('forcesync', 'mod_strava'));
}

echo $OUTPUT->footer();
