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
 * Generate questions with AI: options form.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

use core\output\notification;
use qbank_aigen\form\generate_form;
use qbank_aigen\local\drafts;
use qbank_aigen\local\generation_request;
use qbank_aigen\local\generator;

require_login();
core_question\local\bank\helper::require_plugin_enabled('qbank_aigen');
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_edit_setup('import', '/question/bank/aigen/generate.php');

$context = $contexts->lowest();
require_capability('qbank/aigen:generate', $context);

$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('pluginname', 'qbank_aigen'));
$PAGE->set_heading($COURSE->fullname);
$PAGE->activityheader->disable();

$generator = new generator();
$backend = $generator->get_backend();

// Course pages that can be used as source material.
$pages = [];
foreach (get_fast_modinfo($COURSE)->get_instances_of('page') as $pagecm) {
    if ($pagecm->uservisible) {
        $pages[$pagecm->id] = $pagecm->get_formatted_name();
    }
}

$form = new generate_form($thispageurl, [
    'contexts' => $contexts->having_one_edit_tab_cap('import'),
    'defaultcategory' => $pagevars['cat'],
    'pages' => $pages,
    'needpolicy' => $backend->requires_policy_acceptance($USER->id),
]);

$error = null;
if ($backend->is_available() && ($data = $form->get_data())) {
    if (!empty($data->acceptpolicy)) {
        \core_ai\manager::user_policy_accepted($USER->id, $context->id);
    }

    if ($data->sourcetype === 'page' && isset($pages[$data->pagecmid])) {
        $pagecm = get_fast_modinfo($COURSE)->get_cm($data->pagecmid);
        $page = $DB->get_record('page', ['id' => $pagecm->instance], 'content', MUST_EXIST);
        $source = html_to_text($page->content, 0, false);
    } else {
        $source = $data->text;
    }

    [$categoryid] = explode(',', $data->category);
    $category = $DB->get_record('question_categories', ['id' => $categoryid], '*', MUST_EXIST);
    // The category must belong to one of the contexts where the user can add questions.
    $categorycontext = context::instance_by_id($category->contextid);
    if (!in_array($categorycontext->id, array_map(fn($c) => $c->id, $contexts->having_cap('moodle/question:add')))) {
        throw new required_capability_exception($categorycontext, 'moodle/question:add', 'nopermissions', '');
    }

    if (drafts::limit_reached($USER->id)) {
        $error = get_string('ratelimit', 'qbank_aigen');
    } else {
        $languages = get_string_manager()->get_list_of_translations();
        $request = new generation_request(
            source: $source,
            qtype: $data->qtype,
            count: (int) $data->count,
            difficulty: $data->difficulty,
            language: $languages[$data->lang] ?? 'English',
            instructions: (string) $data->instructions,
        );
        try {
            $questions = $generator->generate($request, $context, $USER->id);
            $draftid = drafts::create($USER->id, $context, $category, $data->qtype, $questions);
            redirect(new moodle_url('/question/bank/aigen/review.php', $thispageurl->params() + ['draftid' => $draftid]));
        } catch (moodle_exception $e) {
            $error = $e->getMessage();
        }
    }
}

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('core_question', 'bank');
echo $renderer->render(new \core_question\output\qbank_action_menu($thispageurl));
echo $OUTPUT->heading(get_string('pluginname', 'qbank_aigen'));

if (!$backend->is_available()) {
    echo $OUTPUT->notification(get_string('notconfigured', 'qbank_aigen'), notification::NOTIFY_WARNING);
} else {
    if ($error) {
        echo $OUTPUT->notification($error, notification::NOTIFY_ERROR);
    }
    echo html_writer::tag('p', get_string('intro', 'qbank_aigen'), ['class' => 'text-muted']);
    $PAGE->requires->js_call_amd('qbank_aigen/generate', 'init');
    $form->display();
}

echo $OUTPUT->footer();
