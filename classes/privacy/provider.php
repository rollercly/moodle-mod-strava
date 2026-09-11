<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_strava\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('strava_grade', [
            'userid'           => 'privacy:metadata:strava_grade:userid',
            'stravaactivityid' => 'privacy:metadata:strava_grade:stravaactivityid',
            'distance'         => 'privacy:metadata:strava_grade:distance',
            'movingtime'       => 'privacy:metadata:strava_grade:movingtime',
            'rawgrade'         => 'privacy:metadata:strava_grade:rawgrade',
        ], 'privacy:metadata:strava_grade');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'strava'
                  JOIN {strava_grade} sg ON sg.stravaid = cm.instance
                 WHERE sg.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('strava', $context->instanceid);
        if (!$cm) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {strava_grade} WHERE stravaid = :stravaid',
            ['stravaid' => $cm->instance]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('strava', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $record = $DB->get_record('strava_grade', ['stravaid' => $cm->instance, 'userid' => $user->id]);
            if ($record) {
                writer::with_context($context)->export_data([], (object) [
                    'sporttype'   => $record->sporttype,
                    'distance'    => $record->distance,
                    'movingtime'  => $record->movingtime,
                    'rawgrade'    => $record->rawgrade,
                    'status'      => $record->status,
                    'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                ]);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('strava', $context->instanceid);
        if ($cm) {
            $DB->delete_records('strava_grade', ['stravaid' => $cm->instance]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('strava', $context->instanceid);
            if ($cm) {
                $DB->delete_records('strava_grade', ['stravaid' => $cm->instance, 'userid' => $contextlist->get_user()->id]);
            }
        }
    }

    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('strava', $context->instanceid);
        if (!$cm) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('strava_grade', ['stravaid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
