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
 * Generate questions with AI: review the generated questions and save the selected ones.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

use core\output\notification;
use qbank_aigen\local\drafts;
use qbank_aigen\local\question_creator;

require_login();
core_question\local\bank\helper::require_plugin_enabled('qbank_aigen');
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_edit_setup('import', '/question/bank/aigen/review.php');

$context = $contexts->lowest();
require_capability('qbank/aigen:generate', $context);

$draftid = required_param('draftid', PARAM_INT);
$draft = drafts::get($draftid, $USER->id);
$category = $DB->get_record('question_categories', ['id' => $draft->categoryid], '*', MUST_EXIST);
$categorycontext = context::instance_by_id($category->contextid);
if (!in_array($categorycontext->id, array_map(fn($c) => $c->id, $contexts->having_cap('moodle/question:add')))) {
    throw new required_capability_exception($categorycontext, 'moodle/question:add', 'nopermissions', '');
}

$thispageurl->param('draftid', $draftid);
$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('review', 'qbank_aigen'));
$PAGE->set_heading($COURSE->fullname);
$PAGE->activityheader->disable();

$generateurl = new moodle_url('/question/bank/aigen/generate.php', $thispageurl->params());
$generateurl->remove_params('draftid');

if (optional_param('discard', false, PARAM_BOOL) && confirm_sesskey()) {
    drafts::delete($draftid);
    redirect($generateurl);
}

if (optional_param('save', false, PARAM_BOOL) && confirm_sesskey()) {
    $selected = optional_param_array('selected', [], PARAM_INT);
    $questions = array_values(array_intersect_key($draft->questions, array_flip($selected)));
    if (!$questions) {
        redirect($thispageurl, get_string('noneselected', 'qbank_aigen'), null, notification::NOTIFY_WARNING);
    }
    question_creator::create_many($questions, $category);
    drafts::delete($draftid);

    $bankurl = new moodle_url('/question/edit.php', $thispageurl->params());
    $bankurl->remove_params('draftid');
    $bankurl->param('cat', $category->id . ',' . $category->contextid);
    redirect($bankurl, get_string('questionssaved', 'qbank_aigen', count($questions)), null, notification::NOTIFY_SUCCESS);
}

$items = [];
foreach ($draft->questions as $index => $question) {
    $item = [
        'index' => $index,
        'number' => $index + 1,
        'name' => $question['name'],
        'questiontext' => $question['questiontext'],
        'generalfeedback' => $question['generalfeedback'],
        'answers' => [],
    ];
    if ($question['type'] === 'multichoice') {
        foreach ($question['answers'] as $answer) {
            $item['answers'][] = ['text' => $answer['text'], 'correct' => $answer['correct'], 'feedback' => $answer['feedback']];
        }
    } else if ($question['type'] === 'truefalse') {
        $item['answers'][] = ['text' => get_string('true', 'qtype_truefalse'), 'correct' => $question['correct']];
        $item['answers'][] = ['text' => get_string('false', 'qtype_truefalse'), 'correct' => !$question['correct']];
    } else {
        foreach ($question['answers'] as $answer) {
            $item['answers'][] = ['text' => $answer, 'correct' => true];
        }
    }
    $items[] = $item;
}

$PAGE->requires->js_call_amd('qbank_aigen/review', 'init');

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('core_question', 'bank');
echo $renderer->render(new \core_question\output\qbank_action_menu($generateurl));
echo $OUTPUT->heading(get_string('review', 'qbank_aigen'));
echo $OUTPUT->render_from_template('qbank_aigen/review', [
    'actionurl' => $thispageurl->out(false),
    'sesskey' => sesskey(),
    'questions' => $items,
    'count' => count($items),
    'introtext' => get_string('reviewintro', 'qbank_aigen', (object) [
        'count' => count($items),
        'qtype' => get_string('pluginname', 'qtype_' . $draft->qtype),
        'category' => format_string($category->name),
    ]),
]);
echo $OUTPUT->footer();
