<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_strava;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \mod_strava\grader
 */
final class grader_test extends \advanced_testcase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function instance(array $overrides = []): \stdClass {
        return (object) array_merge([
            'grade'           => 100,
            'usedistance'     => 0,
            'distancetarget'  => 0,
            'distanceweight'  => 0,
            'useduration'     => 0,
            'durationtarget'  => 0,
            'durationweight'  => 0,
            'usespeed'        => 0,
            'speedtarget'     => 0.0,
            'speedweight'     => 0,
            'useelevation'    => 0,
            'elevationtarget' => 0,
            'elevationweight' => 0,
        ], $overrides);
    }

    private function activity(array $overrides = []): array {
        return array_merge([
            'id'                   => 1,
            'sport_type'           => 'Run',
            'distance'             => 0.0,
            'moving_time'          => 0,
            'average_speed'        => 0.0,
            'total_elevation_gain' => 0.0,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // calculate() — sin objetivos
    // -------------------------------------------------------------------------

    public function test_no_objectives_returns_null_grade(): void {
        $instance = $this->instance();
        $activity = $this->activity();

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        $this->assertNull($rawgrade);
        $this->assertSame([], $breakdown);
    }

    // -------------------------------------------------------------------------
    // calculate() — distancia
    // -------------------------------------------------------------------------

    public function test_distance_below_target(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 5000.0]);

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(50.0, $rawgrade, 0.001);
        $this->assertArrayHasKey('distance', $breakdown);
        $this->assertEqualsWithDelta(0.5, $breakdown['distance']['ratio'], 0.0001);
    }

    public function test_distance_at_target(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 10000.0]);

        [$rawgrade, ] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(100.0, $rawgrade, 0.001);
    }

    public function test_distance_above_target_is_capped_at_max(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 20000.0]);

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        // ratio must be capped at 1.0 — going further doesn't give extra points.
        $this->assertEqualsWithDelta(100.0, $rawgrade, 0.001);
        $this->assertEqualsWithDelta(1.0, $breakdown['distance']['ratio'], 0.0001);
    }

    public function test_distance_target_zero_returns_zero_ratio(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 0,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 5000.0]);

        // distancetarget=0 → ratio_minimum returns 0.0 → rawgrade = 0.
        [$rawgrade, ] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(0.0, $rawgrade, 0.0001);
    }

    // -------------------------------------------------------------------------
    // calculate() — duración (cuanto menor, mejor)
    // -------------------------------------------------------------------------

    public function test_duration_faster_than_target_capped_at_max(): void {
        $instance = $this->instance([
            'useduration'    => 1,
            'durationtarget' => 3600,
            'durationweight' => 100,
        ]);
        // moving_time = 1800s (más rápido que el target de 3600s).
        $activity = $this->activity(['moving_time' => 1800]);

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        // ratio = min(1.0, 3600/1800) = 1.0 → rawgrade = 100.
        $this->assertEqualsWithDelta(100.0, $rawgrade, 0.001);
        $this->assertEqualsWithDelta(1.0, $breakdown['duration']['ratio'], 0.0001);
    }

    public function test_duration_slower_than_target(): void {
        $instance = $this->instance([
            'useduration'    => 1,
            'durationtarget' => 3600,
            'durationweight' => 100,
        ]);
        // moving_time = 7200s (el doble del target) → ratio = 0.5.
        $activity = $this->activity(['moving_time' => 7200]);

        [$rawgrade, ] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(50.0, $rawgrade, 0.001);
    }

    public function test_duration_zero_moving_time_returns_near_zero(): void {
        $instance = $this->instance([
            'useduration'    => 1,
            'durationtarget' => 3600,
            'durationweight' => 100,
        ]);
        // moving_time ausente → PHP_INT_MAX → ratio ≈ 0.
        $activity = ['id' => 1, 'sport_type' => 'Run'];

        [$rawgrade, ] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(0.0, $rawgrade, 0.0001);
    }

    // -------------------------------------------------------------------------
    // calculate() — velocidad y desnivel
    // -------------------------------------------------------------------------

    public function test_speed_objective(): void {
        $instance = $this->instance([
            'usespeed'    => 1,
            'speedtarget' => 4.0,
            'speedweight' => 100,
        ]);
        $activity = $this->activity(['average_speed' => 2.0]);

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(50.0, $rawgrade, 0.001);
        $this->assertEqualsWithDelta(0.5, $breakdown['speed']['ratio'], 0.0001);
    }

    public function test_elevation_objective(): void {
        $instance = $this->instance([
            'useelevation'    => 1,
            'elevationtarget' => 500,
            'elevationweight' => 100,
        ]);
        $activity = $this->activity(['total_elevation_gain' => 750.0]);

        [$rawgrade, ] = grader::calculate($instance, $activity);

        // ratio = min(1.0, 750/500) = 1.0 → capped.
        $this->assertEqualsWithDelta(100.0, $rawgrade, 0.001);
    }

    // -------------------------------------------------------------------------
    // calculate() — múltiples objetivos
    // -------------------------------------------------------------------------

    public function test_all_objectives_equal_weights(): void {
        $instance = $this->instance([
            'usedistance'     => 1,
            'distancetarget'  => 10000,
            'distanceweight'  => 25,
            'useduration'     => 1,
            'durationtarget'  => 3600,
            'durationweight'  => 25,
            'usespeed'        => 1,
            'speedtarget'     => 4.0,
            'speedweight'     => 25,
            'useelevation'    => 1,
            'elevationtarget' => 500,
            'elevationweight' => 25,
        ]);
        // Cada objetivo alcanzado al 50%.
        $activity = $this->activity([
            'distance'             => 5000.0,
            'moving_time'          => 7200,   // doble del target → 50%
            'average_speed'        => 2.0,
            'total_elevation_gain' => 250.0,
        ]);

        [$rawgrade, $breakdown] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(50.0, $rawgrade, 0.001);
        $this->assertCount(4, $breakdown);
    }

    public function test_rawgrade_uses_instance_grade_as_max(): void {
        $instance = $this->instance([
            'grade'          => 200,
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 10000.0]);

        [$rawgrade, ] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta(200.0, $rawgrade, 0.001);
    }

    public function test_rawgrade_rounded_to_5_decimals(): void {
        $instance = $this->instance([
            'grade'          => 100,
            'usedistance'    => 1,
            'distancetarget' => 3,
            'distanceweight' => 100,
        ]);
        // distance=1, target=3 → ratio = 1/3 = 0.33333... → rawgrade = 33.33333.
        $activity = $this->activity(['distance' => 1.0]);

        [$rawgrade, ] = grader::calculate($instance, $activity);

        // round(1/3 * 100, 5) = 33.33333.
        $this->assertEqualsWithDelta(33.33333, $rawgrade, 0.000005);
    }

    // -------------------------------------------------------------------------
    // pick_best()
    // -------------------------------------------------------------------------

    public function test_pick_best_empty_returns_null(): void {
        $instance = $this->instance();
        $this->assertNull(grader::pick_best($instance, []));
    }

    public function test_pick_best_single_activity_returns_it(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => 5000.0]);

        $result = grader::pick_best($instance, [$activity]);

        $this->assertSame($activity, $result);
    }

    public function test_pick_best_returns_highest_graded(): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => 10000,
            'distanceweight' => 100,
        ]);
        $low  = $this->activity(['id' => 1, 'distance' => 3000.0]);
        $high = $this->activity(['id' => 2, 'distance' => 8000.0]);
        $mid  = $this->activity(['id' => 3, 'distance' => 5000.0]);

        $result = grader::pick_best($instance, [$low, $high, $mid]);

        $this->assertSame($high, $result);
    }

    public function test_pick_best_no_objectives_returns_first(): void {
        // Sin objetivos, rawgrade siempre null → debe devolver el primer elemento.
        $instance  = $this->instance();
        $first     = $this->activity(['id' => 1]);
        $second    = $this->activity(['id' => 2]);

        $result = grader::pick_best($instance, [$first, $second]);

        $this->assertSame($first, $result);
    }

    // -------------------------------------------------------------------------
    // Data provider — variaciones de ratio_minimum / ratio_maximum
    // -------------------------------------------------------------------------

    public static function provider_distance_ratio(): array {
        return [
            'zero actual'         => [0.0,   10000, 0.0],
            'half actual'         => [5000.0, 10000, 0.5],
            'exact match'         => [10000.0, 10000, 1.0],
            'above target'        => [15000.0, 10000, 1.0],
        ];
    }

    /**
     * @dataProvider provider_distance_ratio
     */
    public function test_distance_ratio(float $distance, int $target, float $expectedRatio): void {
        $instance = $this->instance([
            'usedistance'    => 1,
            'distancetarget' => $target,
            'distanceweight' => 100,
        ]);
        $activity = $this->activity(['distance' => $distance]);

        [, $breakdown] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta($expectedRatio, $breakdown['distance']['ratio'], 0.0001);
    }

    public static function provider_duration_ratio(): array {
        return [
            'exactly on time' => [3600, 3600, 1.0],
            'twice as fast'   => [1800, 3600, 1.0],  // capped
            'twice as slow'   => [7200, 3600, 0.5],
            'ten times slow'  => [36000, 3600, 0.1],
        ];
    }

    /**
     * @dataProvider provider_duration_ratio
     */
    public function test_duration_ratio(int $movingTime, int $target, float $expectedRatio): void {
        $instance = $this->instance([
            'useduration'    => 1,
            'durationtarget' => $target,
            'durationweight' => 100,
        ]);
        $activity = $this->activity(['moving_time' => $movingTime]);

        [, $breakdown] = grader::calculate($instance, $activity);

        $this->assertEqualsWithDelta($expectedRatio, $breakdown['duration']['ratio'], 0.0001);
    }
}
