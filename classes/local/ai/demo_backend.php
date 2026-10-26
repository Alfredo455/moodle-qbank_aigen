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

namespace qbank_aigen\local\ai;

/**
 * Offline backend for demos and automated tests: builds simple fill-in-the-blank
 * questions from the sentences of the source text, without any language model.
 *
 * It answers with exactly the same JSON format as a real model, so the whole
 * pipeline (parsing, review, saving) can be tried without an API key. Enable it
 * with $CFG->qbank_aigen_demo = true; in config.php.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class demo_backend implements backend {
    /**
     * Always available.
     *
     * @return bool
     */
    public function is_available(): bool {
        return true;
    }

    /**
     * Nothing leaves the server, no policy needed.
     *
     * @param int $userid
     * @return bool
     */
    public function requires_policy_acceptance(int $userid): bool {
        return false;
    }

    /**
     * Build the questions from the source embedded in the prompt.
     *
     * @param string $prompt
     * @param \context $context
     * @param int $userid
     * @return string JSON
     */
    public function generate(string $prompt, \context $context, int $userid): string {
        preg_match('/"""\s*(.*?)\s*"""/s', $prompt, $source);
        preg_match('/Question type: (\w+)/', $prompt, $type);
        preg_match('/Number of questions: (\d+)/', $prompt, $count);
        $type = $type[1] ?? 'multichoice';
        $count = (int) ($count[1] ?? 3);

        $sentences = array_values(array_filter(
            preg_split('/(?<=[.!?])\s+/u', trim($source[1] ?? '')),
            fn($s) => \core_text::strlen($s) >= 40
        ));
        preg_match_all('/\b[\p{L}]{6,}\b/u', $source[1] ?? '', $words);
        $vocabulary = array_values(array_unique($words[0]));

        $questions = [];
        foreach (array_slice($sentences, 0, $count) as $i => $sentence) {
            preg_match_all('/\b[\p{L}]{6,}\b/u', $sentence, $candidates);
            if (empty($candidates[0])) {
                continue;
            }
            $keyword = $candidates[0][array_search(
                max(array_map('strlen', $candidates[0])),
                array_map('strlen', $candidates[0])
            )];
            $blank = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/u', '_____', $sentence, 1);
            $name = 'Demo ' . ($i + 1) . ': ' . $keyword;

            if ($type === 'truefalse') {
                $questions[] = ['type' => 'truefalse', 'name' => $name, 'questiontext' => $sentence, 'correct' => true,
                    'generalfeedback' => 'The statement appears literally in the source material.'];
            } else if ($type === 'shortanswer') {
                $questions[] = ['type' => 'shortanswer', 'name' => $name, 'questiontext' => "Complete: {$blank}",
                    'answers' => [$keyword], 'generalfeedback' => "The missing word is \"{$keyword}\"."];
            } else {
                $others = [];
                foreach ($vocabulary as $word) {
                    $others[\core_text::strtolower($word)] = $word;
                }
                unset($others[\core_text::strtolower($keyword)]);
                $distractors = array_slice(array_values($others), $i * 3, 3);
                $answers = [['text' => $keyword, 'correct' => true, 'feedback' => 'Correct.']];
                foreach ($distractors as $distractor) {
                    $answers[] = ['text' => $distractor, 'correct' => false, 'feedback' => "The missing word is \"{$keyword}\"."];
                }
                $questions[] = ['type' => 'multichoice', 'name' => $name, 'questiontext' => "Complete: {$blank}",
                    'answers' => $answers, 'generalfeedback' => "The sentence is: {$sentence}"];
            }
        }
        return json_encode(['questions' => $questions]);
    }
}
