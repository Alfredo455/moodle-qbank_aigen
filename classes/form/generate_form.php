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

namespace qbank_aigen\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use qbank_aigen\local\generation_request;

/**
 * Generation options.
 *
 * Custom data: contexts (question_edit_contexts list), defaultcategory, pages (cmid => name),
 * needpolicy (bool).
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_form extends \moodleform {
    /** @var int Minimum length of the source material. */
    public const MIN_SOURCE_LENGTH = 200;

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $pages = $this->_customdata['pages'] ?? [];

        $mform->addElement('header', 'sourceheader', get_string('source', 'qbank_aigen'));

        $sources = ['text' => get_string('sourcetext', 'qbank_aigen')];
        if ($pages) {
            $sources['page'] = get_string('sourcepage', 'qbank_aigen');
        }
        $mform->addElement('select', 'sourcetype', get_string('sourcetype', 'qbank_aigen'), $sources);

        $mform->addElement(
            'textarea',
            'text',
            get_string('sourcetext', 'qbank_aigen'),
            ['rows' => 12, 'cols' => 80, 'placeholder' => get_string('sourcetext_placeholder', 'qbank_aigen')]
        );
        $mform->setType('text', PARAM_RAW);
        $mform->hideIf('text', 'sourcetype', 'neq', 'text');

        if ($pages) {
            $mform->addElement('select', 'pagecmid', get_string('sourcepage', 'qbank_aigen'), $pages);
            $mform->hideIf('pagecmid', 'sourcetype', 'neq', 'page');
        }

        $mform->addElement('header', 'optionsheader', get_string('options', 'qbank_aigen'));
        $mform->setExpanded('optionsheader');

        $qtypes = [];
        foreach (generation_request::QTYPES as $qtype) {
            $qtypes[$qtype] = get_string('pluginname', 'qtype_' . $qtype);
        }
        $mform->addElement('select', 'qtype', get_string('questiontype', 'question'), $qtypes);

        $counts = array_combine(range(1, generation_request::MAX_QUESTIONS), range(1, generation_request::MAX_QUESTIONS));
        $mform->addElement('select', 'count', get_string('count', 'qbank_aigen'), $counts);
        $mform->setDefault('count', 5);

        $difficulties = [];
        foreach (generation_request::DIFFICULTIES as $difficulty) {
            $difficulties[$difficulty] = get_string('difficulty_' . $difficulty, 'qbank_aigen');
        }
        $mform->addElement('select', 'difficulty', get_string('difficulty', 'qbank_aigen'), $difficulties);
        $mform->setDefault('difficulty', 'medium');

        $mform->addElement('select', 'lang', get_string('language'), get_string_manager()->get_list_of_translations());
        $mform->setDefault('lang', current_language());

        $mform->addElement('textarea', 'instructions', get_string('instructions', 'qbank_aigen'), ['rows' => 2, 'cols' => 80]);
        $mform->setType('instructions', PARAM_TEXT);
        $mform->addHelpButton('instructions', 'instructions', 'qbank_aigen');

        $mform->addElement(
            'questioncategory',
            'category',
            get_string('category', 'question'),
            ['contexts' => $this->_customdata['contexts']]
        );
        $mform->setDefault('category', $this->_customdata['defaultcategory']);

        if (!empty($this->_customdata['needpolicy'])) {
            $mform->addElement('header', 'policyheader', get_string('aipolicy', 'qbank_aigen'));
            $mform->addElement('static', 'policytext', '', get_string('userpolicy', 'ai'));
            $mform->addElement('checkbox', 'acceptpolicy', get_string('acceptpolicy', 'qbank_aigen'));
            $mform->addRule('acceptpolicy', null, 'required', null, 'client');
        }

        $this->add_action_buttons(false, get_string('generate', 'qbank_aigen'));
    }

    /**
     * The source must be long enough to produce meaningful questions.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['sourcetype'] === 'text' && \core_text::strlen(trim($data['text'] ?? '')) < self::MIN_SOURCE_LENGTH) {
            $errors['text'] = get_string('sourcetooshort', 'qbank_aigen', self::MIN_SOURCE_LENGTH);
        }
        if (!empty($this->_customdata['needpolicy']) && empty($data['acceptpolicy'])) {
            $errors['acceptpolicy'] = get_string('required');
        }
        return $errors;
    }
}
