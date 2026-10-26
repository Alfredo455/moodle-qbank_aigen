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

namespace qbank_aigen\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

use core_question\local\bank\question_version_status;

/**
 * Saves normalised questions into the question bank.
 *
 * Questions are created with the "Draft" status and the "ai-generated" tag, so
 * they cannot be added to a quiz until a teacher has reviewed them.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_creator {
    /** @var string Tag added to every generated question. */
    public const TAG = 'ai-generated';

    /**
     * Create one question.
     *
     * @param array $data Normalised question, see {@see response_parser::normalise()}.
     * @param \stdClass $category Question category record.
     * @return \stdClass The saved question.
     */
    public static function create(array $data, \stdClass $category): \stdClass {
        $form = (object) [
            'category' => $category->id . ',' . $category->contextid,
            'name' => $data['name'],
            'questiontext' => self::html($data['questiontext']),
            'generalfeedback' => self::html($data['generalfeedback']),
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'status' => question_version_status::QUESTION_STATUS_DRAFT,
        ];

        switch ($data['type']) {
            case 'multichoice':
                $form->single = 1;
                $form->shuffleanswers = 1;
                $form->answernumbering = 'abc';
                $form->showstandardinstruction = 0;
                $form->shownumcorrect = 0;
                $form->correctfeedback = self::html('');
                $form->partiallycorrectfeedback = self::html('');
                $form->incorrectfeedback = self::html('');
                $form->answer = [];
                $form->fraction = [];
                $form->feedback = [];
                foreach ($data['answers'] as $answer) {
                    $form->answer[] = ['text' => $answer['text'], 'format' => FORMAT_PLAIN];
                    $form->fraction[] = $answer['correct'] ? '1.0' : '0.0';
                    $form->feedback[] = self::html($answer['feedback']);
                }
                $form->noanswers = count($form->answer);
                break;

            case 'truefalse':
                $form->correctanswer = $data['correct'] ? '1' : '0';
                $form->feedbacktrue = self::html('');
                $form->feedbackfalse = self::html('');
                $form->penalty = 1;
                break;

            case 'shortanswer':
                $form->usecase = 0;
                $form->answer = $data['answers'];
                $form->fraction = array_fill(0, count($data['answers']), '1.0');
                $form->feedback = array_fill(0, count($data['answers']), self::html(''));
                break;

            default:
                throw new \coding_exception('Unsupported question type ' . $data['type']);
        }

        $question = (object) ['category' => $category->id, 'qtype' => $data['type']];
        $saved = \question_bank::get_qtype($data['type'])->save_question($question, $form);

        \core_tag_tag::add_item_tag(
            'core_question',
            'question',
            $saved->id,
            \context::instance_by_id($category->contextid),
            self::TAG
        );
        return $saved;
    }

    /**
     * Create several questions in one transaction.
     *
     * @param array[] $questions
     * @param \stdClass $category
     * @return \stdClass[]
     */
    public static function create_many(array $questions, \stdClass $category): array {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $saved = [];
        foreach ($questions as $question) {
            $saved[] = self::create($question, $category);
        }
        $transaction->allow_commit();
        return $saved;
    }

    /**
     * Editor-like value from plain text.
     *
     * @param string $text
     * @return array
     */
    private static function html(string $text): array {
        return ['text' => $text === '' ? '' : '<p>' . s($text) . '</p>', 'format' => FORMAT_HTML];
    }
}
