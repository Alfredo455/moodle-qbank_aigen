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

use GuzzleHttp\Psr7\Response;
use qbank_aigen\local\ai\backend;
use qbank_aigen\local\ai\demo_backend;
use qbank_aigen\local\ai\openai_backend;
use qbank_aigen\local\generation_request;
use qbank_aigen\local\generator;
use qbank_aigen\local\prompt_builder;

/**
 * Tests of the prompt, the backends and the generation pipeline.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_aigen\local\generator
 * @covers     \qbank_aigen\local\prompt_builder
 * @covers     \qbank_aigen\local\generation_request
 * @covers     \qbank_aigen\local\ai\openai_backend
 * @covers     \qbank_aigen\local\ai\demo_backend
 */
final class generator_test extends \advanced_testcase {
    /** @var string Source material used in the tests. */
    private const SOURCE = 'Photosynthesis is the process plants use to convert light energy into chemical energy. ' .
        'It takes place in the chloroplasts, which contain a green pigment called chlorophyll. ' .
        'During photosynthesis, plants absorb carbon dioxide and release oxygen into the atmosphere. ' .
        'The glucose produced is used by the plant as a source of energy for growth.';

    /**
     * The prompt contains the request parameters, the rules of the type and the source.
     */
    public function test_prompt(): void {
        $request = new generation_request(self::SOURCE, 'multichoice', 3, 'hard', 'Español', 'Focus on gases');
        $prompt = prompt_builder::build($request);

        $this->assertStringContainsString('Question type: multichoice', $prompt);
        $this->assertStringContainsString('Number of questions: 3', $prompt);
        $this->assertStringContainsString('Language of the questions and answers: Español', $prompt);
        $this->assertStringContainsString('hard (analysis', $prompt);
        $this->assertStringContainsString('exactly one of them is correct', $prompt);
        $this->assertStringContainsString('Focus on gases', $prompt);
        $this->assertStringContainsString("\"\"\"\n" . self::SOURCE . "\n\"\"\"", $prompt);
    }

    /**
     * Long sources are truncated at a sentence boundary.
     */
    public function test_prepare_source(): void {
        $source = prompt_builder::prepare_source(self::SOURCE, 200);
        $this->assertStringEndsWith('chlorophyll.', $source);
        $this->assertSame('Say "hi".', prompt_builder::prepare_source("Say \"\"\"hi\"\"\".", 100));
    }

    /**
     * Invalid requests are rejected.
     */
    public function test_invalid_request(): void {
        $this->expectException(\coding_exception::class);
        new generation_request(self::SOURCE, 'essay', 3);
    }

    /**
     * The whole pipeline with a fake model.
     */
    public function test_generate_with_fake_backend(): void {
        $this->resetAfterTest();
        $backend = new class implements backend {
            /** @var string Last prompt received. */
            public string $prompt = '';

            /**
             * Always available.
             *
             * @return bool
             */
            public function is_available(): bool {
                return true;
            }

            /**
             * No policy.
             *
             * @param int $userid
             * @return bool
             */
            public function requires_policy_acceptance(int $userid): bool {
                return false;
            }

            /**
             * Record the prompt and answer with fenced JSON.
             *
             * @param string $prompt
             * @param \context $context
             * @param int $userid
             * @return string
             */
            public function generate(string $prompt, \context $context, int $userid): string {
                $this->prompt = $prompt;
                $fence = str_repeat(chr(96), 3);
                return $fence . "json\n" . json_encode(['questions' => [
                    ['questiontext' => 'Plants release oxygen.', 'correct' => true],
                    ['questiontext' => 'Chlorophyll is blue.', 'correct' => false],
                ]]) . "\n" . $fence;
            }
        };

        $questions = (new generator($backend))->generate(
            new generation_request(self::SOURCE, 'truefalse', 2),
            \context_system::instance(),
            2
        );

        $this->assertCount(2, $questions);
        $this->assertFalse($questions[1]['correct']);
        $this->assertStringContainsString('Question type: truefalse', $backend->prompt);
    }

    /**
     * The OpenAI-compatible backend sends a JSON-mode chat request and reads the answer.
     */
    public function test_openai_backend(): void {
        $this->resetAfterTest();
        set_config('apikey', 'sk-test', 'qbank_aigen');
        set_config('model', 'llama3.1', 'qbank_aigen');
        $history = [];
        ['mock' => $mock] = $this->get_mocked_http_client($history);
        $mock->append(new Response(200, [], json_encode(['choices' => [['message' => ['content' => '{"questions": []}']]]])));
        $mock->append(new Response(401, [], json_encode(['error' => ['message' => 'Invalid API key']])));

        $backend = new openai_backend();
        $this->assertTrue($backend->is_available());
        $this->assertSame('{"questions": []}', $backend->generate('Hello', \context_system::instance(), 2));

        $request = $history[0]['request'];
        $this->assertSame(openai_backend::DEFAULT_ENDPOINT, (string) $request->getUri());
        $this->assertSame('Bearer sk-test', $request->getHeaderLine('Authorization'));
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('llama3.1', $body['model']);
        $this->assertSame('json_object', $body['response_format']['type']);
        $this->assertSame('Hello', $body['messages'][1]['content']);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid API key');
        $backend->generate('Hello', \context_system::instance(), 2);
    }

    /**
     * The demo backend produces valid questions of every type without a model.
     */
    public function test_demo_backend(): void {
        $this->resetAfterTest();
        $generator = new generator(new demo_backend());
        foreach (generation_request::QTYPES as $qtype) {
            $questions = $generator->generate(new generation_request(self::SOURCE, $qtype, 3), \context_system::instance(), 2);
            $this->assertCount(3, $questions, $qtype);
            $this->assertSame($qtype, $questions[0]['type']);
        }
    }
}
