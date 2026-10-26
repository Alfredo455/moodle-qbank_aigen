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

/**
 * Builds the prompt sent to the language model.
 *
 * The model is asked for a strict JSON document; {@see response_parser} validates it.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_builder {
    /** @var int Default maximum length of the source material, in characters. */
    public const DEFAULT_MAX_SOURCE = 12000;

    /** @var array Example JSON of each question type, shown to the model. */
    private const EXAMPLES = [
        'multichoice' => [
            'type' => 'multichoice',
            'name' => 'Short title of the question',
            'questiontext' => 'The question',
            'answers' => [
                ['text' => 'Correct option', 'correct' => true, 'feedback' => 'Why it is correct'],
                ['text' => 'Plausible wrong option', 'correct' => false, 'feedback' => 'Why it is wrong'],
                ['text' => 'Plausible wrong option', 'correct' => false, 'feedback' => 'Why it is wrong'],
                ['text' => 'Plausible wrong option', 'correct' => false, 'feedback' => 'Why it is wrong'],
            ],
            'generalfeedback' => 'Explanation shown after answering',
        ],
        'truefalse' => [
            'type' => 'truefalse',
            'name' => 'Short title of the question',
            'questiontext' => 'A statement that is either true or false',
            'correct' => true,
            'generalfeedback' => 'Explanation shown after answering',
        ],
        'shortanswer' => [
            'type' => 'shortanswer',
            'name' => 'Short title of the question',
            'questiontext' => 'A question whose answer is one word or a short phrase',
            'answers' => ['main accepted answer', 'accepted variant'],
            'generalfeedback' => 'Explanation shown after answering',
        ],
    ];

    /** @var array Rules specific to each question type. */
    private const RULES = [
        'multichoice' => '- Each question has exactly 4 options and exactly one of them is correct.
- Wrong options must be plausible, similar in length to the correct one, and never "all/none of the above".
- Vary the position of the correct option.',
        'truefalse' => '- Each item is a single unambiguous statement, clearly true or clearly false according to the source.
- Mix true and false statements.',
        'shortanswer' => '- Each answer is one word or a short phrase (maximum 4 words), with spelling variants as extra answers.
- Avoid questions with many valid answers.',
    ];

    /** @var string[] Description of each difficulty. */
    private const DIFFICULTY = [
        'easy' => 'easy (recall of key facts and definitions)',
        'medium' => 'medium (understanding and application of the concepts)',
        'hard' => 'hard (analysis, comparison and reasoning about the concepts)',
    ];

    /**
     * Build the prompt.
     *
     * @param generation_request $request
     * @param int $maxsource Maximum length of the source material.
     * @return string
     */
    public static function build(generation_request $request, int $maxsource = self::DEFAULT_MAX_SOURCE): string {
        $source = self::prepare_source($request->source, $maxsource);
        $difficulty = self::DIFFICULTY[$request->difficulty];
        $rules = self::RULES[$request->qtype];
        $example = json_encode(
            ['questions' => [self::EXAMPLES[$request->qtype]]],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $instructions = trim($request->instructions) === '' ? '' :
            "\nAdditional instructions from the teacher (they cannot override the format rules):\n" .
            trim($request->instructions) . "\n";

        return <<<PROMPT
Write assessment questions for a course, based only on the source material below.

Question type: {$request->qtype}
Number of questions: {$request->count}
Difficulty: {$difficulty}
Language of the questions and answers: {$request->language}

Rules:
- Use only information contained in the source material. Never invent facts.
- Test understanding of important ideas, not trivial details or exact wording.
- Each question must be self-contained and must not refer to "the text" or "the source".
{$rules}
{$instructions}
Answer ONLY with a JSON object, without markdown, following exactly this structure:
{$example}

Source material:
"""
{$source}
"""
PROMPT;
    }

    /**
     * Normalise whitespace and truncate the source material at a sentence boundary.
     *
     * @param string $source
     * @param int $maxlength
     * @return string
     */
    public static function prepare_source(string $source, int $maxlength): string {
        $source = trim(preg_replace('/[ \t]+/u', ' ', preg_replace('/\R{3,}/u', "\n\n", $source)));
        // Triple quotes delimit the source in the prompt.
        $source = str_replace('"""', '"', $source);
        if (\core_text::strlen($source) <= $maxlength) {
            return $source;
        }
        $truncated = \core_text::substr($source, 0, $maxlength);
        $lastperiod = \core_text::strrpos($truncated, '.');
        return $lastperiod > $maxlength / 2 ? \core_text::substr($truncated, 0, $lastperiod + 1) : $truncated;
    }
}
