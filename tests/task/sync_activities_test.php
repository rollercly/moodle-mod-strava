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
 * Unit tests for the mod_strava sync_activities task.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_strava\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/strava/lib.php');
require_once($CFG->dirroot . '/mod/strava/tests/fixtures/testable_sync_activities.php');

/**
 * @covers \mod_strava\task\sync_activities
 */
final class sync_activities_test extends \advanced_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function make_strava_instance(array $overrides = []): \stdClass {
        $course  = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_strava');
        $record = array_merge([
            'course'        => $course->id,
            'activitytype'  => 'Run',
            'tolerancedays' => 0,
            'selectionmode' => 'best',
            'grade'         => 100,
            'usedistance'   => 0,
            'useduration'   => 0,
            'usespeed'      => 0,
            'useelevation'  => 0,
        ], $overrides);

        $instance = $gen->create_instance($record);
        return (object) [
            'instance' => $instance,
            'course'   => $course,
            'student'  => $student,
        ];
    }

    /** Crea un token en la tabla de local_stravaauth para simular usuario conectado. */
    private function connect_user(int $userid): void {
        global $DB;
        $DB->insert_record('local_stravaauth_token', (object) [
            'userid'       => $userid,
            'athleteid'    => 12345,
            'accesstoken'  => 'fake_token',
            'refreshtoken' => 'fake_refresh',
            'expiresat'    => time() + 3600,
            'scope'        => 'activity:read_all',
            'timemodified' => time(),
            'timecreated'  => time(),
        ]);
    }

    /** Crea una actividad Strava falsa con los campos mínimos necesarios. */
    private function fake_run(array $overrides = []): array {
        return array_merge([
            'id'                   => 999,
            'sport_type'           => 'Run',
            'type'                 => 'Run',
            'distance'             => 10000.0,
            'moving_time'          => 3600,
            'average_speed'        => 2.78,
            'total_elevation_gain' => 100.0,
            'start_date'           => '2026-06-15T08:00:00Z',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_get_name_returns_nonempty_string(): void {
        $task = new sync_activities();
        $this->assertNotEmpty($task->get_name());
    }

    public function test_execute_ignores_instances_with_open_window(): void {
        global $DB;
        $this->resetAfterTest();

        // targetdate en el futuro: la ventana no ha cerrado aún.
        $data = $this->make_strava_instance([
            'targetdate'    => time() + DAYSECS * 10,
            'tolerancedays' => 0,
        ]);

        $task = new sync_activities();
        $task->execute();

        $count = $DB->count_records('strava_grade', ['stravaid' => $data->instance->id]);
        $this->assertSame(0, $count);
    }

    public function test_execute_processes_closed_window(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'targetdate'    => $past,
            'tolerancedays' => 0,
        ]);

        // Sin token → notsubmitted.
        $task = new \testable_sync_activities();
        $task->execute();

        $count = $DB->count_records('strava_grade', ['stravaid' => $data->instance->id]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_skip_already_graded_user(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'targetdate'    => $past,
            'tolerancedays' => 0,
        ]);

        // Insertar un resultado 'graded' previo.
        $DB->insert_record('strava_grade', (object) [
            'stravaid'    => $data->instance->id,
            'userid'      => $data->student->id,
            'status'      => 'graded',
            'rawgrade'    => 80.0,
            'breakdown'   => '{}',
            'timemodified' => time() - 100,
        ]);

        $task = new \testable_sync_activities();
        $task->fake_activities = [$this->fake_run()];
        $task->execute();

        // El registro debe conservar su rawgrade original sin modificarse.
        $record = $DB->get_record('strava_grade', ['stravaid' => $data->instance->id, 'userid' => $data->student->id]);
        $this->assertSame('graded', $record->status);
        $this->assertEqualsWithDelta(80.0, (float) $record->rawgrade, 0.001);
    }

    public function test_not_connected_saves_notsubmitted(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'targetdate'    => $past,
            'tolerancedays' => 0,
        ]);

        // No insertar token → is_connected() devolverá false.
        $task = new \testable_sync_activities();
        $task->execute();

        $record = $DB->get_record('strava_grade', ['stravaid' => $data->instance->id, 'userid' => $data->student->id]);
        $this->assertNotFalse($record);
        $this->assertSame('notsubmitted', $record->status);
    }

    public function test_no_matching_activities_saves_notsubmitted(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'activitytype'  => 'Run',
            'targetdate'    => $past,
            'tolerancedays' => 0,
        ]);

        $this->connect_user($data->student->id);

        // Actividad de tipo distinto (Ride ≠ Run).
        $task = new \testable_sync_activities();
        $task->fake_activities = [$this->fake_run(['sport_type' => 'Ride', 'type' => 'Ride'])];
        $task->execute();

        $record = $DB->get_record('strava_grade', ['stravaid' => $data->instance->id, 'userid' => $data->student->id]);
        $this->assertNotFalse($record);
        $this->assertSame('notsubmitted', $record->status);
    }

    public function test_graded_result_saved_correctly(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'activitytype'   => 'Run',
            'targetdate'     => $past,
            'tolerancedays'  => 0,
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);

        $this->connect_user($data->student->id);

        $task = new \testable_sync_activities();
        $task->fake_activities = [$this->fake_run(['distance' => 10000.0])];
        $task->execute();

        $record = $DB->get_record('strava_grade', ['stravaid' => $data->instance->id, 'userid' => $data->student->id]);
        $this->assertNotFalse($record);
        $this->assertSame('graded', $record->status);
        $this->assertEqualsWithDelta(100.0, (float) $record->rawgrade, 0.001);
    }

    public function test_save_result_upserts_not_duplicates(): void {
        global $DB;
        $this->resetAfterTest();

        $past = time() - DAYSECS * 5;
        $data = $this->make_strava_instance([
            'targetdate'    => $past,
            'tolerancedays' => 0,
        ]);

        // Primera ejecución → notsubmitted.
        $task = new \testable_sync_activities();
        $task->execute();

        // Segunda ejecución → debe actualizar, no duplicar.
        $task2 = new \testable_sync_activities();
        $task2->execute();

        $count = $DB->count_records('strava_grade', ['stravaid' => $data->instance->id, 'userid' => $data->student->id]);
        $this->assertSame(1, $count);
    }
}
