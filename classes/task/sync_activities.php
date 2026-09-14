<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_strava\task;

defined('MOODLE_INTERNAL') || die();

use mod_strava\grader;

/**
 * Tarea programada: para cada instancia cuya ventana de fecha objetivo ya
 * ha pasado (targetdate +/- tolerancedays), busca en Strava las actividades
 * de los alumnos matriculados, calcula la nota y actualiza el libro de
 * calificaciones.
 */
class sync_activities extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task:syncactivities', 'mod_strava');
    }

    public function execute(): void {
        global $DB;

        $now = time();

        // Instancias cuya ventana [targetdate, targetdate + tolerancedays] ya cerro.
        $sql = "SELECT * FROM {strava}
                 WHERE (targetdate + (tolerancedays * 86400) + 86400) <= :now";
        $instances = $DB->get_records_sql($sql, ['now' => $now]);

        foreach ($instances as $instance) {
            $this->sync_instance($instance);
        }
    }

    /**
     * Sincroniza una instancia concreta para todos sus alumnos pendientes.
     * Publico para poder reutilizarlo desde sync.php (sincronizacion manual
     * disparada por el profesor) ademas de desde execute().
     */
    public function sync_instance(\stdClass $instance): void {
        global $DB;

       // mtrace("mod_strava: sincronizando instancia #{$instance->id} ({$instance->name})");

        $cm = get_coursemodule_from_instance('strava', $instance->id, $instance->course);
        if (!$cm) {
            return;
        }
        $context = \context_module::instance($cm->id);

        $enrolled = get_enrolled_users($context, 'mod/strava:submit');

        $windowstart = $instance->targetdate - ($instance->tolerancedays * DAYSECS);
        $windowend   = $instance->targetdate + ($instance->tolerancedays * DAYSECS) + DAYSECS;

        foreach ($enrolled as $user) {
            // No recalcular si ya existe un resultado 'graded' para este usuario.
            if ($DB->record_exists('strava_grade', [
                'stravaid' => $instance->id,
                'userid'   => $user->id,
                'status'   => 'graded',
            ])) {
                continue;
            }

            $this->sync_user($instance, $user->id, $windowstart, $windowend);
        }

        strava_update_grades($instance);
    }

    /**
     * Busca las actividades del usuario en la ventana de fechas, calcula la
     * nota con la mejor candidata y guarda/actualiza strava_grade.
     */
    private function sync_user(\stdClass $instance, int $userid, int $windowstart, int $windowend): void {
        global $DB;

        if (!\local_stravaauth\api_client::is_connected($userid)) {
            $this->save_result($instance, $userid, null, null, 'notsubmitted');
            return;
        }

        try {
            $activities = $this->fetch_activities($userid, $windowstart, $windowend);
        } catch (\Exception $e) {
            mtrace("  usuario {$userid}: error al consultar Strava - " . $e->getMessage());
            return;
        }

        $matching = array_values(array_filter($activities, function ($activity) use ($instance) {
            return ($activity['sport_type'] ?? $activity['type'] ?? '') === $instance->activitytype;
        }));

        if (empty($matching)) {
            $this->save_result($instance, $userid, null, null, 'notsubmitted');
            return;
        }

        $chosen = grader::pick_best($instance, $matching);
        [$rawgrade, $breakdown] = grader::calculate($instance, $chosen);

        $this->save_result($instance, $userid, $chosen, $rawgrade, 'graded', $breakdown);
    }

    /**
     * Recupera actividades desde la API de Strava para el usuario dado en la ventana indicada.
     * Extraído como método protegido para permitir la sobreescritura en tests.
     */
    protected function fetch_activities(int $userid, int $windowstart, int $windowend): array {
        return \local_stravaauth\api_client::get($userid, 'athlete/activities', [
            'after'    => $windowstart,
            'before'   => $windowend,
            'per_page' => 50,
        ]);
    }

    /**
     * Inserta o actualiza el registro de resultado del alumno.
     */
    private function save_result(\stdClass $instance, int $userid, ?array $activity, ?float $rawgrade,
            string $status, array $breakdown = []): void {
        global $DB;

        $existing = $DB->get_record('strava_grade', ['stravaid' => $instance->id, 'userid' => $userid]);

        $record = new \stdClass();
        $record->stravaid = $instance->id;
        $record->userid = $userid;
        $record->status = $status;
        $record->rawgrade = $rawgrade;
        $record->breakdown = json_encode($breakdown);
        $record->timemodified = time();

        if ($activity) {
            $record->stravaactivityid = $activity['id'] ?? null;
            $record->sporttype = $activity['sport_type'] ?? $activity['type'] ?? null;
            $record->distance = $activity['distance'] ?? null;
            $record->movingtime = $activity['moving_time'] ?? null;
            $record->averagespeed = $activity['average_speed'] ?? null;
            $record->elevationgain = $activity['total_elevation_gain'] ?? null;
            $record->startdate = isset($activity['start_date'])
                ? strtotime($activity['start_date']) : null;
        }

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('strava_grade', $record);
        } else {
            $DB->insert_record('strava_grade', $record);
        }
    }
}
