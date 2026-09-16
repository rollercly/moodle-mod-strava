<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_strava';
$plugin->version   = 2026091401;      // YYYYMMDDXX
$plugin->requires  = 2022112800;      // Moodle 4.1+
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
$plugin->dependencies = [
    'local_stravaauth' => 2026091000,
];
