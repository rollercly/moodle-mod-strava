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
 * Restore task for mod_strava.
 *
 * @package   mod_strava
 * @category  backup
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/strava/backup/moodle2/restore_strava_stepslib.php');

/**
 * Provides all the settings and steps to perform one complete restore of a Strava instance.
 */
class restore_strava_activity_task extends restore_activity_task {

    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the restore step that reads strava.xml.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_strava_activity_structure_step('strava_structure', 'strava.xml'));
    }

    /**
     * Defines the contents that must be processed by the link decoder.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('strava', ['intro'], 'strava')];
    }

    /**
     * Defines the decoding rules for links belonging to the activity.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('STRAVAVIEWBYID', '/mod/strava/view.php?id=$1', 'course_module'),
            new restore_decode_rule('STRAVAINDEX', '/mod/strava/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Defines the restore log rules for the activity.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Defines the restore log rules for course-level logs.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
