<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class mod_strava_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $record = (array) $record;
        $record += [
            'activitytype'  => 'Run',
            'targetdate'    => mktime(0, 0, 0, 6, 15, 2026),
            'tolerancedays' => 3,
            'selectionmode' => 'best',
            'grade'         => 100,
            'usedistance'   => 0,
            'useduration'   => 0,
            'usespeed'      => 0,
            'useelevation'  => 0,
        ];
        return parent::create_instance($record, $options);
    }
}
