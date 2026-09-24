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
 * English language strings for mod_strava.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Strava Challenge';
$string['modulenameplural'] = 'Strava Challenges';
$string['modulename_help'] = 'Set a physical activity goal (distance, time, speed, elevation) to be completed on a specific date, synced automatically from Strava, and graded proportionally to how well the goal was met.';
$string['pluginname'] = 'Strava Challenge';
$string['pluginadministration'] = 'Strava Challenge administration';

$string['stravaname'] = 'Activity name';
$string['stravasettings'] = 'Strava challenge settings';
$string['activitytype'] = 'Activity type';
$string['targetdate'] = 'Target date';
$string['targetdate_help'] = 'The exact date on which the student must complete the activity.';
$string['tolerancedays'] = 'Tolerance (± days)';
$string['tolerancedays_help'] = 'Number of days before/after the target date that also count as valid.';
$string['selectionmode'] = 'If several activities match';
$string['selectionmode_best'] = 'Automatically pick the best-scoring activity';
$string['selectionmode_choose'] = 'Let the student choose (not yet implemented in this skeleton)';

$string['objectives'] = 'Objectives and weighting';
$string['objdistance'] = 'Distance';
$string['objduration'] = 'Time';
$string['objspeed'] = 'Average speed';
$string['objelevation'] = 'Elevation gain';
$string['enable'] = 'Enable';
$string['unit_km'] = 'km';
$string['unit_minutes'] = 'min';
$string['unit_kmh'] = 'km/h';
$string['unit_meters'] = 'm';
$string['weighthint'] = 'The weights of the enabled objectives must add up to 100%.';
$string['errortargetrequired'] = 'Enter a target value for every enabled objective.';
$string['errorweightsum'] = 'The weights add up to {$a}%, they must add up to 100%.';

$string['type_run'] = 'Running';
$string['type_trailrun'] = 'Trail running';
$string['type_ride'] = 'Cycling (road)';
$string['type_mtbride'] = 'Mountain biking';
$string['type_mountainbikeride'] = 'Mountain biking';
$string['type_hike'] = 'Hiking';
$string['type_walk'] = 'Walking';
$string['type_swim'] = 'Swimming';

$string['viewwindow'] = 'Valid window: from {$a->from} to {$a->to} ({$a->type}).';
$string['notconnected'] = 'You have not linked your Strava account yet. You must do so before the target date.';
$string['connected'] = 'Your Strava account is linked.';
$string['yourresult'] = 'Your result';
$string['resultpending'] = 'Results have not been synced yet. They will be calculated automatically after {$a}.';
$string['resultnotsubmitted'] = 'No matching activity was found for this challenge.';
$string['finalgrade'] = 'Grade: {$a->grade} / {$a->max} ({$a->pct}%)';
$string['metric'] = 'Metric';
$string['objective'] = 'Objective';
$string['achieved'] = 'Achieved';
$string['forcesync'] = 'Force sync now';
$string['syncdone'] = 'Sync completed.';
$string['noinstances'] = 'There are no Strava Challenge activities in this course yet.';

$string['task:syncactivities'] = 'Sync Strava activities and grade challenges';

$string['strava:addinstance'] = 'Add a new Strava Challenge activity';
$string['strava:view'] = 'View a Strava Challenge activity';
$string['strava:submit'] = 'Participate in a Strava Challenge';
$string['strava:viewreports'] = 'View Strava Challenge results for all students';
$string['strava:manualsync'] = 'Manually trigger a Strava sync';

$string['privacy:metadata:strava_grade'] = 'Stores the Strava activity data and calculated grade for each student and challenge.';
$string['privacy:metadata:strava_grade:userid'] = 'The Moodle user ID.';
$string['privacy:metadata:strava_grade:stravaactivityid'] = 'The linked Strava activity ID.';
$string['privacy:metadata:strava_grade:distance'] = 'Distance covered, in metres.';
$string['privacy:metadata:strava_grade:movingtime'] = 'Moving time, in seconds.';
$string['privacy:metadata:strava_grade:rawgrade'] = 'The calculated grade.';
