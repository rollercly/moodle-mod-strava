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
 * Privacy provider tests for mod_strava.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_strava\privacy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/strava/lib.php');

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * @covers \mod_strava\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function create_strava_with_student(): object {
        $course  = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $gen     = $this->getDataGenerator()->get_plugin_generator('mod_strava');
        $mod     = $gen->create_instance(['course' => $course->id]);
        $cm      = get_coursemodule_from_instance('strava', $mod->id);
        $context = \context_module::instance($cm->id);

        return (object) [
            'course'  => $course,
            'student' => $student,
            'mod'     => $mod,
            'cm'      => $cm,
            'context' => $context,
        ];
    }

    private function insert_grade(\stdClass $mod, int $userid, string $status = 'graded', float $rawgrade = 80.0): void {
        global $DB;
        $DB->insert_record('strava_grade', (object) [
            'stravaid'         => $mod->id,
            'userid'           => $userid,
            'stravaactivityid' => 99999,
            'sporttype'        => 'Run',
            'distance'         => 10000.0,
            'movingtime'       => 3600,
            'averagespeed'     => 2.78,
            'elevationgain'    => 100.0,
            'startdate'        => time() - DAYSECS,
            'rawgrade'         => $rawgrade,
            'breakdown'        => '{}',
            'status'           => $status,
            'timemodified'     => time(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_get_metadata_registers_strava_grade_table(): void {
        $this->resetAfterTest();
        $collection = new \core_privacy\local\metadata\collection('mod_strava');
        $result = provider::get_metadata($collection);

        $items = $result->get_collection();
        $tables = array_filter($items, fn($item) => $item instanceof \core_privacy\local\metadata\types\database_table);
        $tablenames = array_map(fn($item) => $item->get_name(), $tables);

        $this->assertContains('strava_grade', array_values($tablenames));
    }

    public function test_get_contexts_for_userid_with_grade(): void {
        $this->resetAfterTest();
        $data = $this->create_strava_with_student();
        $this->insert_grade($data->mod, $data->student->id);

        $contextlist = provider::get_contexts_for_userid($data->student->id);
        $contextids  = $contextlist->get_contextids();

        $this->assertContains($data->context->id, array_map('intval', $contextids));
    }

    public function test_get_contexts_for_userid_without_grade(): void {
        $this->resetAfterTest();
        $data    = $this->create_strava_with_student();
        $other   = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($other->id);
        $contextids  = $contextlist->get_contextids();

        $this->assertNotContains($data->context->id, $contextids);
    }

    public function test_get_users_in_context(): void {
        $this->resetAfterTest();
        $data = $this->create_strava_with_student();
        $this->insert_grade($data->mod, $data->student->id);

        $userlist = new userlist($data->context, 'mod_strava');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertContains((int) $data->student->id, $userids);
    }

    public function test_export_user_data(): void {
        $this->resetAfterTest();
        $data = $this->create_strava_with_student();
        $this->insert_grade($data->mod, $data->student->id, 'graded', 75.0);

        $contextlist = new approved_contextlist($data->student, 'mod_strava', [$data->context->id]);
        provider::export_user_data($contextlist);

        $exported = writer::with_context($data->context)->get_data([]);
        $this->assertNotNull($exported);
        $this->assertSame('Run', $exported->sporttype);
        $this->assertEqualsWithDelta(75.0, (float) $exported->rawgrade, 0.001);
        $this->assertSame('graded', $exported->status);
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        $data     = $this->create_strava_with_student();
        $student2 = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $data->student->id);
        $this->insert_grade($data->mod, $student2->id);

        provider::delete_data_for_all_users_in_context($data->context);

        $count = $DB->count_records('strava_grade', ['stravaid' => $data->mod->id]);
        $this->assertSame(0, $count);
    }

    public function test_delete_data_for_user_only_removes_that_user(): void {
        global $DB;
        $this->resetAfterTest();
        $data     = $this->create_strava_with_student();
        $student2 = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $data->student->id);
        $this->insert_grade($data->mod, $student2->id);

        $contextlist = new approved_contextlist($data->student, 'mod_strava', [$data->context->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertFalse($DB->record_exists('strava_grade', ['stravaid' => $data->mod->id, 'userid' => $data->student->id]));
        $this->assertTrue($DB->record_exists('strava_grade', ['stravaid' => $data->mod->id, 'userid' => $student2->id]));
    }

    public function test_delete_data_for_users_batch_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $data     = $this->create_strava_with_student();
        $student2 = $this->getDataGenerator()->create_and_enrol($data->course, 'student');
        $student3 = $this->getDataGenerator()->create_and_enrol($data->course, 'student');

        $this->insert_grade($data->mod, $data->student->id);
        $this->insert_grade($data->mod, $student2->id);
        $this->insert_grade($data->mod, $student3->id);

        $userlist = new approved_userlist($data->context, 'mod_strava', [$data->student->id, $student2->id]);
        provider::delete_data_for_users($userlist);

        $this->assertFalse($DB->record_exists('strava_grade', ['userid' => $data->student->id]));
        $this->assertFalse($DB->record_exists('strava_grade', ['userid' => $student2->id]));
        $this->assertTrue($DB->record_exists('strava_grade', ['userid' => $student3->id]));
    }
}
