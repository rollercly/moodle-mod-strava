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
 * Backup steps for mod_strava.
 *
 * @package   mod_strava
 * @category  backup
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete structure for backup of a Strava instance.
 */
class backup_strava_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $strava = new backup_nested_element('strava', ['id'], [
            'name', 'intro', 'introformat', 'activitytype', 'targetdate', 'tolerancedays',
            'selectionmode', 'usedistance', 'distancetarget', 'distanceweight',
            'useduration', 'durationtarget', 'durationweight',
            'usespeed', 'speedtarget', 'speedweight',
            'useelevation', 'elevationtarget', 'elevationweight',
            'grade', 'completionsubmit', 'timecreated', 'timemodified',
        ]);

        $grades = new backup_nested_element('grades');
        $stravagrade = new backup_nested_element('grade', ['id'], [
            'userid', 'stravaactivityid', 'sporttype', 'distance', 'movingtime',
            'averagespeed', 'elevationgain', 'startdate', 'rawgrade', 'breakdown',
            'status', 'timemodified',
        ]);

        $strava->add_child($grades);
        $grades->add_child($stravagrade);

        $strava->set_source_table('strava', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $stravagrade->set_source_table('strava_grade', ['stravaid' => backup::VAR_PARENTID]);
        }

        $stravagrade->annotate_ids('user', 'userid');

        $strava->annotate_files('mod_strava', 'intro', null);

        return $this->prepare_activity_structure($strava);
    }
}
