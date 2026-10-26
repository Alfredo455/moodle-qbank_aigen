<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish strings for the AI question generator.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptpolicy'] = 'Acepto la política de uso de IA';
$string['aierror'] = 'El servicio de IA devolvió un error: {$a}';
$string['aigen:generate'] = 'Generar preguntas con IA';
$string['aipolicy'] = 'Política de uso de IA';
$string['apiendpoint'] = 'Endpoint de la API';
$string['apiendpoint_desc'] = 'URL de chat completions de una API compatible con OpenAI. Ejemplos: https://api.openai.com/v1/chat/completions o, para un servidor Ollama propio, http://ollama.internal:11434/v1/chat/completions.';
$string['apikey'] = 'Clave de la API';
$string['apikey_desc'] = 'Déjala vacía para modelos propios que no requieren autenticación.';
$string['backend'] = 'Motor de IA';
$string['backend_coreai'] = 'Subsistema de IA de Moodle (recomendado)';
$string['backend_desc'] = 'El subsistema de IA de Moodle usa los proveedores configurados en Administración del sitio > General > IA, con registro de uso y aceptación de la política. La API compatible con OpenAI permite usar cualquier proveedor o un modelo propio.';
$string['backend_openai'] = 'API compatible con OpenAI';
$string['count'] = 'Número de preguntas';
$string['difficulty'] = 'Dificultad';
$string['difficulty_easy'] = 'Fácil: recordar datos clave';
$string['difficulty_hard'] = 'Difícil: análisis y razonamiento';
$string['difficulty_medium'] = 'Media: comprensión y aplicación';
$string['discard'] = 'Descartar y empezar de nuevo';
$string['generate'] = 'Generar preguntas';
$string['generating'] = 'Generando preguntas, puede tardar hasta un minuto...';
$string['instructions'] = 'Instrucciones adicionales';
$string['instructions_help'] = 'Indicaciones opcionales para la IA, por ejemplo "Céntrate en el capítulo 2" o "Usa ejemplos de Latinoamérica".';
$string['intro'] = 'Pega el material del curso o elige una página del curso. La IA redacta las preguntas, tú las revisas y las que conserves se guardan como borrador con la etiqueta "ai-generated".';
$string['invalidresponse'] = 'No se pudo interpretar la respuesta de la IA. Inténtalo de nuevo.';
$string['keep'] = 'Conservar';
$string['maxperhour'] = 'Generaciones por usuario y hora';
$string['maxperhour_desc'] = 'Limita el coste del servicio de IA.';
$string['maxsourcechars'] = 'Longitud máxima del material';
$string['maxsourcechars_desc'] = 'El material más largo se recorta al final de una frase, en caracteres.';
$string['model'] = 'Modelo';
$string['model_desc'] = 'Nombre del modelo, por ejemplo gpt-4o-mini o llama3.1.';
$string['navtitle'] = 'Generador IA';
$string['noneselected'] = 'Selecciona al menos una pregunta.';
$string['notconfigured'] = 'No hay ningún servicio de IA disponible. Pide al administrador que active un proveedor de IA con la acción "Generar texto" o que configure una API compatible con OpenAI en los ajustes del plugin.';
$string['novalidquestions'] = 'La IA no devolvió ninguna pregunta válida. Prueba con un material más largo o más claro.';
$string['options'] = 'Opciones';
$string['pluginname'] = 'Generador de preguntas con IA';
$string['privacy:metadata:aiprovider'] = 'El material elegido por el profesor se envía al modelo de lenguaje configurado. No incluye datos personales.';
$string['privacy:metadata:aiprovider:prompt'] = 'Las instrucciones y el material del curso usados para redactar las preguntas.';
$string['privacy:metadata:core_ai'] = 'Cuando se usa el subsistema de IA de Moodle, este registra cada petición.';
$string['privacy:metadata:draft'] = 'Preguntas generadas pendientes de revisión. Se eliminan al cabo de un día.';
$string['privacy:metadata:draft:questions'] = 'Las preguntas generadas.';
$string['privacy:metadata:draft:timecreated'] = 'Cuándo se generaron las preguntas.';
$string['privacy:metadata:draft:userid'] = 'El profesor que generó las preguntas.';
$string['questionssaved'] = '{$a} pregunta(s) guardadas como borrador. Revísalas antes de usarlas en un cuestionario.';
$string['ratelimit'] = 'Has alcanzado el máximo de generaciones por hora. Inténtalo más tarde.';
$string['review'] = 'Revisa las preguntas generadas';
$string['reviewintro'] = 'Se generaron {$a->count} pregunta(s) de tipo {$a->qtype}. Las seleccionadas se guardarán como borrador en "{$a->category}".';
$string['saveselected'] = 'Guardar las preguntas seleccionadas';
$string['selectedcount'] = '{$a} seleccionadas';
$string['source'] = 'Material de origen';
$string['sourcepage'] = 'Página del curso';
$string['sourcetext'] = 'Texto';
$string['sourcetext_placeholder'] = 'Pega aquí el contenido sobre el que deben tratar las preguntas: apuntes, un capítulo, una transcripción...';
$string['sourcetooshort'] = 'El texto debe tener al menos {$a} caracteres.';
$string['sourcetype'] = 'Usar';
$string['taskcleanup'] = 'Eliminar las preguntas generadas con IA no revisadas';
