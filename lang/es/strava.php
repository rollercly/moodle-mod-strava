<?php
// This file is part of Moodle - http://moodle.org/

$string['modulename'] = 'Reto Strava';
$string['modulenameplural'] = 'Retos Strava';
$string['modulename_help'] = 'Define un objetivo de actividad física (distancia, tiempo, velocidad, desnivel) que debe realizarse en una fecha concreta, se sincroniza automáticamente desde Strava y se califica de forma proporcional al grado de cumplimiento.';
$string['pluginname'] = 'Reto Strava';
$string['pluginadministration'] = 'Administración de Reto Strava';

$string['stravaname'] = 'Nombre de la actividad';
$string['stravasettings'] = 'Configuración del reto Strava';
$string['activitytype'] = 'Tipo de actividad';
$string['targetdate'] = 'Fecha objetivo';
$string['targetdate_help'] = 'Fecha exacta en la que el alumno debe realizar la actividad.';
$string['tolerancedays'] = 'Tolerancia (± días)';
$string['tolerancedays_help'] = 'Número de días antes/después de la fecha objetivo que también se consideran válidos.';
$string['selectionmode'] = 'Si hay varias actividades que coinciden';
$string['selectionmode_best'] = 'Elegir automáticamente la de mejor puntuación';
$string['selectionmode_choose'] = 'Que el alumno elija (no implementado aún en este esqueleto)';

$string['objectives'] = 'Objetivos y ponderación';
$string['objdistance'] = 'Distancia';
$string['objduration'] = 'Tiempo';
$string['objspeed'] = 'Velocidad media';
$string['objelevation'] = 'Desnivel positivo';
$string['enable'] = 'Activar';
$string['unit_km'] = 'km';
$string['unit_minutes'] = 'min';
$string['unit_kmh'] = 'km/h';
$string['unit_meters'] = 'm';
$string['weighthint'] = 'Los pesos de los objetivos activados deben sumar 100%.';
$string['errortargetrequired'] = 'Indica un valor objetivo para cada objetivo activado.';
$string['errorweightsum'] = 'Los pesos suman {$a}%, deben sumar 100%.';

$string['type_run'] = 'Running';
$string['type_trailrun'] = 'Trail running';
$string['type_ride'] = 'Ciclismo (carretera)';
$string['type_mtbride'] = 'Ciclismo de montaña (MTB)';
$string['type_mountainbikeride'] = 'Ciclismo de montaña (MTB)';
$string['type_hike'] = 'Senderismo';
$string['type_walk'] = 'Caminar';
$string['type_swim'] = 'Natación';

$string['viewwindow'] = 'Ventana válida: del {$a->from} al {$a->to} ({$a->type}).';
$string['notconnected'] = 'Todavía no has vinculado tu cuenta de Strava. Debes hacerlo antes de la fecha objetivo.';
$string['connected'] = 'Tu cuenta de Strava está vinculada.';
$string['yourresult'] = 'Tu resultado';
$string['resultpending'] = 'Los resultados aún no se han sincronizado. Se calcularán automáticamente después del {$a}.';
$string['resultnotsubmitted'] = 'No se ha encontrado ninguna actividad que coincida con este reto.';
$string['finalgrade'] = 'Nota: {$a->grade} / {$a->max} ({$a->pct}%)';
$string['metric'] = 'Métrica';
$string['objective'] = 'Objetivo';
$string['achieved'] = 'Conseguido';
$string['forcesync'] = 'Forzar sincronización ahora';
$string['syncdone'] = 'Sincronización completada.';
$string['noinstances'] = 'Todavía no hay actividades de Reto Strava en este curso.';

$string['task:syncactivities'] = 'Sincronizar actividades de Strava y calificar retos';

$string['strava:addinstance'] = 'Añadir una nueva actividad de Reto Strava';
$string['strava:view'] = 'Ver una actividad de Reto Strava';
$string['strava:submit'] = 'Participar en un Reto Strava';
$string['strava:viewreports'] = 'Ver los resultados de Reto Strava de todos los alumnos';
$string['strava:manualsync'] = 'Forzar manualmente una sincronización con Strava';

$string['privacy:metadata:strava_grade'] = 'Almacena los datos de la actividad de Strava y la nota calculada de cada alumno para cada reto.';
$string['privacy:metadata:strava_grade:userid'] = 'El ID del usuario de Moodle.';
$string['privacy:metadata:strava_grade:stravaactivityid'] = 'El ID de la actividad de Strava vinculada.';
$string['privacy:metadata:strava_grade:distance'] = 'Distancia recorrida, en metros.';
$string['privacy:metadata:strava_grade:movingtime'] = 'Tiempo en movimiento, en segundos.';
$string['privacy:metadata:strava_grade:rawgrade'] = 'La nota calculada.';
