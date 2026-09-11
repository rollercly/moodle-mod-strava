<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_strava_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // --- Cabecera general -------------------------------------------------
        $mform->addElement('text', 'name', get_string('stravaname', 'mod_strava'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // --- Fecha y tipo de actividad ------------------------------------------
        $mform->addElement('header', 'stravasettings', get_string('stravasettings', 'mod_strava'));

        $mform->addElement('select', 'activitytype', get_string('activitytype', 'mod_strava'),
            $this->get_activity_types());
        $mform->setDefault('activitytype', 'Run');

        $mform->addElement('date_time_selector', 'targetdate', get_string('targetdate', 'mod_strava'));
        $mform->addHelpButton('targetdate', 'targetdate', 'mod_strava');

        $mform->addElement('select', 'tolerancedays', get_string('tolerancedays', 'mod_strava'),
            array_combine(range(0, 7), range(0, 7)));
        $mform->setDefault('tolerancedays', 0);
        $mform->addHelpButton('tolerancedays', 'tolerancedays', 'mod_strava');

        $mform->addElement('select', 'selectionmode', get_string('selectionmode', 'mod_strava'), [
            'best'   => get_string('selectionmode_best', 'mod_strava'),
            'choose' => get_string('selectionmode_choose', 'mod_strava'),
        ]);
        $mform->setDefault('selectionmode', 'best');

        // --- Objetivos ponderados ----------------------------------------------
        $mform->addElement('header', 'objectiveshdr', get_string('objectives', 'mod_strava'));
        $mform->setExpanded('objectiveshdr', true);

        $this->add_objective_group($mform, 'distance', 'km',
            get_string('objdistance', 'mod_strava'));
        $this->add_objective_group($mform, 'duration', 'minutes',
            get_string('objduration', 'mod_strava'));
        $this->add_objective_group($mform, 'speed', 'kmh',
            get_string('objspeed', 'mod_strava'));
        $this->add_objective_group($mform, 'elevation', 'meters',
            get_string('objelevation', 'mod_strava'));

        $mform->addElement('static', 'weighthint', '', get_string('weighthint', 'mod_strava'));

        // --- Calificacion estandar de Moodle ------------------------------------
        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Anade un bloque "activar / objetivo / peso" para un tipo de metrica.
     * Los valores se introducen en unidades humanas (km, minutos, km/h) y se
     * convierten a las unidades de almacenamiento (metros, segundos, m/s) en
     * data_preprocessing() / los setters correspondientes del controlador.
     */
    private function add_objective_group(\MoodleQuickForm $mform, string $field, string $unit, string $label): void {
        $group = [];
        $group[] = $mform->createElement('advcheckbox', "use{$field}", '', get_string('enable', 'mod_strava'));
        $group[] = $mform->createElement('float', "{$field}target_display", '', ['size' => 6]);
        $group[] = $mform->createElement('static', "{$field}unit", '', get_string("unit_{$unit}", 'mod_strava'));
        $group[] = $mform->createElement('text', "{$field}weight", '', ['size' => 3]);
        $group[] = $mform->createElement('static', "{$field}weightsuffix", '', '%');

        $mform->addGroup($group, "{$field}group", $label, ' ', false);
        $mform->setType("{$field}target_display", PARAM_FLOAT);
        $mform->setType("{$field}weight", PARAM_INT);
        $mform->disabledIf("{$field}target_display", "use{$field}");
        $mform->disabledIf("{$field}weight", "use{$field}");
    }

    private function get_activity_types(): array {
        // Subconjunto habitual de sport_type de Strava; ampliable segun necesidad.
        return [
            'Run'          => get_string('type_run', 'mod_strava'),
            'TrailRun'     => get_string('type_trailrun', 'mod_strava'),
            'Ride'         => get_string('type_ride', 'mod_strava'),
            'MountainBikeRide' => get_string('type_mtbride', 'mod_strava'),
            'Hike'         => get_string('type_hike', 'mod_strava'),
            'Walk'         => get_string('type_walk', 'mod_strava'),
            'Swim'         => get_string('type_swim', 'mod_strava'),
        ];
    }

    /**
     * Convierte los campos "_display" (unidades humanas) a las columnas reales
     * de la tabla y valida que la suma de pesos sea 100 cuando hay al menos un
     * objetivo activo.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $totalweight = 0;
        foreach (['distance', 'duration', 'speed', 'elevation'] as $field) {
            if (!empty($data["use{$field}"])) {
                if (empty($data["{$field}target_display"])) {
                    $errors["{$field}group"] = get_string('errortargetrequired', 'mod_strava');
                }
                $totalweight += (int) $data["{$field}weight"];
            }
        }

        if ($totalweight > 0 && $totalweight != 100) {
            $errors['weighthint'] = get_string('errorweightsum', 'mod_strava', $totalweight);
        }

        return $errors;
    }

    /**
     * Sobrescribe get_data() para convertir los campos "_display" (km,
     * minutos, km/h) introducidos por el profesor a las unidades de
     * almacenamiento (metros, segundos, m/s) antes de que lib.php inserte
     * o actualice el registro.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }

        $data->distancetarget = !empty($data->usedistance) && !empty($data->distancetarget_display)
            ? (int) round($data->distancetarget_display * 1000) : null;

        $data->durationtarget = !empty($data->useduration) && !empty($data->durationtarget_display)
            ? (int) round($data->durationtarget_display * 60) : null;

        $data->speedtarget = !empty($data->usespeed) && !empty($data->speedtarget_display)
            ? round($data->speedtarget_display / 3.6, 3) : null;

        $data->elevationtarget = !empty($data->useelevation) && !empty($data->elevationtarget_display)
            ? (int) round($data->elevationtarget_display) : null;

        return $data;
    }

    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        // metros -> km, segundos -> minutos, m/s -> km/h para mostrar en el formulario.
        if (!empty($defaultvalues['distancetarget'])) {
            $defaultvalues['distancetarget_display'] = $defaultvalues['distancetarget'] / 1000;
        }
        if (!empty($defaultvalues['durationtarget'])) {
            $defaultvalues['durationtarget_display'] = $defaultvalues['durationtarget'] / 60;
        }
        if (!empty($defaultvalues['speedtarget'])) {
            $defaultvalues['speedtarget_display'] = $defaultvalues['speedtarget'] * 3.6;
        }
        if (!empty($defaultvalues['elevationtarget'])) {
            $defaultvalues['elevationtarget_display'] = $defaultvalues['elevationtarget'];
        }
    }
}
