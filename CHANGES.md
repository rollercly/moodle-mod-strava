# Changelog — mod_strava

All notable changes to this project will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [0.1.0] — 2026-09-10

### Initial release (alpha)

#### Added
- Activity module (`mod_strava`) that turns a Strava sporting challenge into a gradable Moodle activity.
- Activity form (`mod_form.php`) allowing teachers to configure:
  - Sport type (Run, Trail Run, Ride, Mountain Bike, Hike, Walk, Swim).
  - Target date and tolerance window (±0–7 days).
  - Up to four weighted objectives: distance (km), duration (min), average speed (km/h), and elevation gain (m).
  - Selection mode: best score or student choice when multiple activities qualify.
- Student view (`view.php`) showing connection status, valid window, and grade breakdown.
- Weighted grade calculation: each metric is scored as `min(1.0, actual/target)` (or inverse for duration); final grade is the weighted sum scaled to the configured maximum grade.
- Nightly scheduled task (`mod_strava\task\sync_activities`, default 03:30) that:
  - Queries the Strava API (`GET /athlete/activities`) for each enrolled student.
  - Filters by sport type and date window.
  - Picks the best-scoring activity and publishes the grade to the Moodle gradebook.
- Manual sync trigger for teachers with the `mod/strava:manualsync` capability.
- Database tables:
  - `mdl_strava` — activity instance configuration.
  - `mdl_strava_grade` — per-student results with status (`pending`, `graded`, `notsubmitted`).
- Capabilities: `addinstance`, `view`, `submit`, `viewreports`, `manualsync`.
- Privacy API implementation declaring `strava_grade` data and the external Strava data source.
- Dependency on `local_stravaauth` (≥ 2026091000) for OAuth2 token management and the Strava API client.

---

*For questions or issues, open a ticket in the project repository.*
