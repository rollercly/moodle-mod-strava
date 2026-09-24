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
 * Restore steps for mod_strava.
 *
 * @package   mod_strava
 * @category  backup
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one Strava activity.
 */
class restore_strava_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('strava', '/activity/strava')];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('strava_grade', '/activity/strava/grades/grade');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity instance.
     *
     * @param array $data
     */
    protected function process_strava($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->targetdate = $this->apply_date_offset($data->targetdate);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('strava', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores one user grade record.
     *
     * @param array $data
     */
    protected function process_strava_grade($data) {
        global $DB;

        $data = (object) $data;
        $data->stravaid = $this->get_new_parentid('strava');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!empty($data->startdate)) {
            $data->startdate = $this->apply_date_offset($data->startdate);
        }

        if (!$data->userid) {
            return;
        }

        $DB->insert_record('strava_grade', $data);
    }

    /**
     * Adds the intro files after the instance has been restored.
     */
    protected function after_execute() {
        $this->add_related_files('mod_strava', 'intro', null);
    }
}
