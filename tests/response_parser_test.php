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

namespace qbank_aigen;

use qbank_aigen\local\response_parser;

/**
 * Tests of the parsing and validation of the model output.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_aigen\local\response_parser
 */
final class response_parser_test extends \basic_testcase {
    /**
     * A valid multiple choice question.
     *
     * @param string $text
     * @return array
     */
    private function multichoice(string $text = 'Which gas do plants release?'): array {
        return [
            'type' => 'multichoice',
            'name' => 'Photosynthesis',
            'questiontext' => $text,
            'answers' => [
                ['text' => 'Oxygen', 'correct' => true, 'feedback' => 'Yes.'],
                ['text' => 'Nitrogen', 'correct' => false],
                ['text' => 'Helium', 'correct' => false],
                ['text' => 'Argon', 'correct' => 'false'],
            ],
            'generalfeedback' => 'Plants release <b>oxygen</b>.',
        ];
    }

    /**
     * JSON wrapped in markdown fences or surrounded by text is extracted.
     */
    public function test_decode_variants(): void {
        $json = json_encode(['questions' => [$this->multichoice()]]);
        $this->assertCount(1, response_parser::parse($json, 'multichoice', 5));
        $this->assertCount(1, response_parser::parse("```json\n{$json}\n```", 'multichoice', 5));
        $this->assertCount(1, response_parser::parse("Sure! Here you go:\n{$json}\nGood luck.", 'multichoice', 5));
        // A bare list is accepted too.
        $this->assertCount(1, response_parser::parse(json_encode([$this->multichoice()]), 'multichoice', 5));
    }

    /**
     * Text is cleaned, answers are normalised.
     */
    public function test_normalise_multichoice(): void {
        [$question] = response_parser::parse(json_encode(['questions' => [$this->multichoice()]]), 'multichoice', 5);
        $this->assertSame('Plants release oxygen.', $question['generalfeedback']);
        $this->assertCount(4, $question['answers']);
        $this->assertTrue($question['answers'][0]['correct']);
        $this->assertFalse($question['answers'][3]['correct']);
        $this->assertSame('', $question['answers'][1]['feedback']);
    }

    /**
     * Invalid questions are dropped, the valid ones are kept up to the maximum.
     */
    public function test_invalid_questions_are_dropped(): void {
        $twocorrect = $this->multichoice('Two correct');
        $twocorrect['answers'][1]['correct'] = true;
        $tooshort = $this->multichoice('Two options');
        $tooshort['answers'] = array_slice($tooshort['answers'], 0, 2);
        $duplicated = $this->multichoice('Duplicated options');
        $duplicated['answers'][2]['text'] = 'oxygen';
        $wrongtype = ['type' => 'truefalse', 'questiontext' => 'Wrong type', 'correct' => true];
        $notext = $this->multichoice('');

        $json = json_encode(['questions' => [$twocorrect, $tooshort, $duplicated, $wrongtype, $notext,
            $this->multichoice('Valid 1'), $this->multichoice('Valid 2'), $this->multichoice('Valid 3')]]);
        $questions = response_parser::parse($json, 'multichoice', 2);

        $this->assertSame(['Valid 1', 'Valid 2'], array_column($questions, 'questiontext'));
    }

    /**
     * True/false and short answer questions.
     */
    public function test_other_types(): void {
        $json = json_encode(['questions' => [
            ['questiontext' => 'The Earth is flat.', 'correct' => 'false'],
            ['questiontext' => 'Missing answer'],
        ]]);
        $questions = response_parser::parse($json, 'truefalse', 5);
        $this->assertCount(1, $questions);
        $this->assertFalse($questions[0]['correct']);
        $this->assertSame('The Earth is flat.', $questions[0]['name']);

        $json = json_encode(['questions' => [
            ['questiontext' => 'Capital of Peru?', 'answers' => ['Lima', 'lima*', 'Lima', '']],
        ]]);
        [$question] = response_parser::parse($json, 'shortanswer', 5);
        $this->assertSame(['Lima', 'lima'], $question['answers']);
    }

    /**
     * Long names are shortened.
     */
    public function test_long_name(): void {
        [$question] = response_parser::parse(
            json_encode([['questiontext' => str_repeat('word ', 60), 'correct' => true]]),
            'truefalse',
            5
        );
        $this->assertSame(100, \core_text::strlen($question['name']));
        $this->assertStringEndsWith('...', $question['name']);
    }

    /**
     * Unusable output raises an exception.
     */
    public function test_unusable_output(): void {
        $this->expectException(\moodle_exception::class);
        response_parser::parse('I cannot help with that.', 'multichoice', 5);
    }

    /**
     * Valid JSON without valid questions raises an exception.
     */
    public function test_no_valid_question(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('novalidquestions', 'qbank_aigen'));
        response_parser::parse('{"questions": [{"questiontext": ""}]}', 'truefalse', 5);
    }
}
