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
 * Grade calculation for mod_strava.
 *
 * @package   mod_strava
 * @copyright 2026 Jose Lorenzo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_strava;

defined('MOODLE_INTERNAL') || die();

/**
 * Calcula la nota de una actividad Strava en funcion de los objetivos
 * configurados en la instancia (distancia, tiempo, velocidad, desnivel).
 */
class grader {

    /**
     * Calcula el rawgrade (0..instance->grade) y el desglose por objetivo.
     *
     * @param \stdClass $instance registro de la tabla strava.
     * @param array $activity actividad de Strava (respuesta cruda de /athlete/activities).
     * @return array [float $rawgrade, array $breakdown]
     */
    public static function calculate(\stdClass $instance, array $activity): array {
        $breakdown = [];
        $totalweight = 0;
        $weightedsum = 0;

        if (!empty($instance->usedistance) && !empty($instance->distancetarget)) {
            $ratio = self::ratio_minimum($activity['distance'] ?? 0, $instance->distancetarget);
            $breakdown['distance'] = [
                'target' => (float) $instance->distancetarget,
                'actual' => (float) ($activity['distance'] ?? 0),
                'ratio'  => $ratio,
                'weight' => (int) $instance->distanceweight,
            ];
            $weightedsum += $ratio * $instance->distanceweight;
            $totalweight += $instance->distanceweight;
        }

        if (!empty($instance->useduration) && !empty($instance->durationtarget)) {
            // Objetivo de tiempo: cuanto menor, mejor (no superar el maximo).
            $ratio = self::ratio_maximum($activity['moving_time'] ?? PHP_INT_MAX, $instance->durationtarget);
            $breakdown['duration'] = [
                'target' => (int) $instance->durationtarget,
                'actual' => (int) ($activity['moving_time'] ?? 0),
                'ratio'  => $ratio,
                'weight' => (int) $instance->durationweight,
            ];
            $weightedsum += $ratio * $instance->durationweight;
            $totalweight += $instance->durationweight;
        }

        if (!empty($instance->usespeed) && !empty($instance->speedtarget)) {
            $ratio = self::ratio_minimum($activity['average_speed'] ?? 0, $instance->speedtarget);
            $breakdown['speed'] = [
                'target' => (float) $instance->speedtarget,
                'actual' => (float) ($activity['average_speed'] ?? 0),
                'ratio'  => $ratio,
                'weight' => (int) $instance->speedweight,
            ];
            $weightedsum += $ratio * $instance->speedweight;
            $totalweight += $instance->speedweight;
        }

        if (!empty($instance->useelevation) && !empty($instance->elevationtarget)) {
            $ratio = self::ratio_minimum($activity['total_elevation_gain'] ?? 0, $instance->elevationtarget);
            $breakdown['elevation'] = [
                'target' => (float) $instance->elevationtarget,
                'actual' => (float) ($activity['total_elevation_gain'] ?? 0),
                'ratio'  => $ratio,
                'weight' => (int) $instance->elevationweight,
            ];
            $weightedsum += $ratio * $instance->elevationweight;
            $totalweight += $instance->elevationweight;
        }

        if ($totalweight <= 0) {
            // Sin objetivos configurados: no se puede calificar automaticamente.
            return [null, $breakdown];
        }

        $percentage = $weightedsum / $totalweight;   // 0..1
        $rawgrade = round($percentage * (float) $instance->grade, 5);

        return [$rawgrade, $breakdown];
    }

    /**
     * De entre varias actividades candidatas del mismo sport_type, elige
     * la que mas se acerca a cumplir (o mejor supera) los objetivos.
     *
     * @param \stdClass $instance
     * @param array $activities lista de actividades ya filtradas por tipo/fecha.
     * @return array|null actividad elegida, o null si la lista esta vacia.
     */
    public static function pick_best(\stdClass $instance, array $activities): ?array {
        if (empty($activities)) {
            return null;
        }

        $best = null;
        $bestgrade = -1;

        foreach ($activities as $activity) {
            [$rawgrade, ] = self::calculate($instance, $activity);
            if ($rawgrade !== null && $rawgrade > $bestgrade) {
                $bestgrade = $rawgrade;
                $best = $activity;
            }
        }

        // Si ningun objetivo estaba configurado (rawgrade siempre null), coge la primera.
        return $best ?? $activities[0];
    }

    /**
     * Ratio para objetivos "cuanto mas, mejor" (distancia, velocidad, desnivel).
     * Se satura en 1.0: superar el objetivo no da mas de la puntuacion maxima.
     */
    private static function ratio_minimum(float $actual, float $target): float {
        if ($target <= 0) {
            return 0.0;
        }
        return min(1.0, $actual / $target);
    }

    /**
     * Ratio para objetivos "cuanto menos, mejor" (tiempo empleado).
     */
    private static function ratio_maximum(float $actual, float $target): float {
        if ($actual <= 0) {
            return 0.0;
        }
        return min(1.0, $target / $actual);
    }
}
