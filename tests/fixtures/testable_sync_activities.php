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
 * Testable sync_activities subclass used by the unit tests.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Subclase testable que inyecta actividades falsas en lugar de llamar a la API HTTP.
 */
class testable_sync_activities extends \mod_strava\task\sync_activities {

    /** @var array Actividades que se devolverán en lugar de llamar a Strava. */
    public array $fake_activities = [];

    protected function fetch_activities(int $userid, int $windowstart, int $windowend): array {
        return $this->fake_activities;
    }
}
