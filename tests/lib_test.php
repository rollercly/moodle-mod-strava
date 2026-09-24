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
 * Unit tests for the mod_strava library functions.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_strava;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/strava/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * @covers ::strava_supports
 * @covers ::strava_add_instance
 * @covers ::strava_update_instance
 * @covers ::strava_delete_instance
 * @covers ::strava_get_course_instances
 * @covers ::strava_update_grades
 * @covers ::strava_get_completion_state
 */
final class lib_test extends \advanced_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function create_instance(array $overrides = []): object {
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_strava');
        $record = array_merge(['course' => $course->id], $overrides);
        $mod    = $gen->create_instance($record);
        return (object) ['course' => $course, 'mod' => $mod];
    }

    private function insert_grade(\stdClass $mod, int $userid, string $status, ?float $rawgrade = null): void {
        global $DB;
        $DB->insert_record('strava_grade', (object) [
            'stravaid'    => $mod->id,
            'userid'      => $userid,
            'status'      => $status,
            'rawgrade'    => $rawgrade,
            'breakdown'   => '{}',
            'timemodified' => time(),
        ]);
    }

    // -------------------------------------------------------------------------
    // strava_supports()
    // -------------------------------------------------------------------------

    public function test_supports_returns_true_for_grade(): void {
        $this->assertTrue(strava_supports(FEATURE_GRADE_HAS_GRADE));
    }

    public function test_supports_returns_true_for_intro(): void {
        $this->assertTrue(strava_supports(FEATURE_MOD_INTRO));
    }

    public function test_supports_returns_false_for_outcomes(): void {
        $this->assertFalse(strava_supports(FEATURE_GRADE_OUTCOMES));
    }

    public function test_supports_returns_true_for_backup(): void {
        $this->assertTrue(strava_supports(FEATURE_BACKUP_MOODLE2));
    }

    public function test_supports_returns_null_for_unknown(): void {
        $this->assertNull(strava_supports('mod_strava_nonexistent_feature'));
    }

    // -------------------------------------------------------------------------
    // strava_add_instance() / strava_update_instance() / strava_delete_instance()
    // -------------------------------------------------------------------------

    public function test_add_instance_inserts_record(): void {
        global $DB;
        $this->resetAfterTest();
        $data = $this->create_instance();

        $this->assertTrue($DB->record_exists('strava', ['id' => $data->mod->id]));
    }

    public function test_add_instance_sets_timestamps(): void {
        global $DB;
        $this->resetAfterTest();
        $before = time();
        $data = $this->create_instance();
        $after = time();

        $record = $DB->get_record('strava', ['id' => $data->mod->id]);
        $this->assertGreaterThanOrEqual($before, (int) $record->timecreated);
        $this->assertLessThanOrEqual($after, (int) $record->timecreated);
        $this->assertSame($record->timecreated, $record->timemodified);
    }

    public function test_update_instance_changes_name(): void {
        global $DB;
        $this->resetAfterTest();
        $data = $this->create_instance();

        $update = $DB->get_record('strava', ['id' => $data->mod->id]);
        $update->instance = $update->id;
        $update->name = 'Updated Name';
        strava_update_instance($update);

        $this->assertSame('Updated Name', $DB->get_field('strava', 'name', ['id' => $data->mod->id]));
    }

    public function test_delete_instance_removes_strava_record(): void {
        global $DB;
        $this->resetAfterTest();
        $data = $this->create_instance();

        strava_delete_instance($data->mod->id);

        $this->assertFalse($DB->record_exists('strava', ['id' => $data->mod->id]));
    }

    public function test_delete_instance_removes_grades(): void {
        global $DB;
        $this->resetAfterTest();
        $data    = $this->create_instance();
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $student->id, 'graded', 75.0);
        strava_delete_instance($data->mod->id);

        $this->assertFalse($DB->record_exists('strava_grade', ['stravaid' => $data->mod->id]));
    }

    public function test_delete_instance_returns_false_if_not_found(): void {
        $this->resetAfterTest();
        $this->assertFalse(strava_delete_instance(999999));
    }

    // -------------------------------------------------------------------------
    // strava_get_course_instances()
    // -------------------------------------------------------------------------

    public function test_get_course_instances_returns_correct_course(): void {
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $gen     = $this->getDataGenerator()->get_plugin_generator('mod_strava');

        $mod1 = $gen->create_instance(['course' => $course1->id]);
        $mod2 = $gen->create_instance(['course' => $course1->id]);
        $gen->create_instance(['course' => $course2->id]);

        $instances = strava_get_course_instances($course1->id);

        $this->assertCount(2, $instances);
        $this->assertArrayHasKey($mod1->id, $instances);
        $this->assertArrayHasKey($mod2->id, $instances);
    }

    public function test_get_course_instances_ordered_by_id(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $gen    = $this->getDataGenerator()->get_plugin_generator('mod_strava');
        $gen->create_instance(['course' => $course->id]);
        $gen->create_instance(['course' => $course->id]);

        $instances = strava_get_course_instances($course->id);
        $ids = array_keys($instances);

        $this->assertSame($ids, array_values($ids));
        $this->assertLessThan($ids[1], $ids[0]);
    }

    // -------------------------------------------------------------------------
    // strava_update_grades()
    // -------------------------------------------------------------------------

    public function test_update_grades_skips_null_rawgrade(): void {
        global $DB;
        $this->resetAfterTest();
        $data    = $this->create_instance();
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        // rawgrade nulo → no debe lanzar excepción.
        $this->insert_grade($data->mod, $student->id, 'pending', null);

        $instance = $DB->get_record('strava', ['id' => $data->mod->id]);
        // No debe lanzar excepción.
        strava_update_grades($instance);
        $this->assertTrue(true);
    }

    public function test_update_grades_processes_graded_records(): void {
        global $DB;
        $this->resetAfterTest();
        $data    = $this->create_instance();
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $student->id, 'graded', 80.0);

        $instance = $DB->get_record('strava', ['id' => $data->mod->id]);
        strava_update_grades($instance);
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // strava_get_completion_state()
    // -------------------------------------------------------------------------

    public function test_get_completion_state_graded_returns_true(): void {
        global $DB;
        $this->resetAfterTest();
        $data    = $this->create_instance(['completionsubmit' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $student->id, 'graded', 80.0);

        $cm       = get_coursemodule_from_instance('strava', $data->mod->id);
        $cm       = \cm_info::create($cm);

        $result = strava_get_completion_state($data->course, $cm, $student->id, false);
        $this->assertTrue($result);
    }

    public function test_get_completion_state_pending_returns_false(): void {
        global $DB;
        $this->resetAfterTest();
        $data    = $this->create_instance(['completionsubmit' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $student->id, 'pending', null);

        $cm = get_coursemodule_from_instance('strava', $data->mod->id);
        $cm = \cm_info::create($cm);

        $result = strava_get_completion_state($data->course, $cm, $student->id, false);
        $this->assertFalse($result);
    }

    public function test_get_completion_state_returns_type_when_no_completionsubmit(): void {
        $this->resetAfterTest();
        $data    = $this->create_instance();
        $student = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $cm = get_coursemodule_from_instance('strava', $data->mod->id);
        $cm = \cm_info::create($cm);

        // completionsubmit no está seteado → debe devolver el valor de $type.
        $this->assertTrue(strava_get_completion_state($data->course, $cm, $student->id, true));
        $this->assertFalse(strava_get_completion_state($data->course, $cm, $student->id, false));
    }
}
