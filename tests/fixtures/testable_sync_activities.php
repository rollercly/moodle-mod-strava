<?php
// This file is part of Moodle - http://moodle.org/

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
