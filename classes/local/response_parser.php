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
 * Validates and normalises the questions returned by the language model.
 *
 * Language models do not always follow instructions: the JSON may be wrapped
 * in markdown fences or surrounded by text, and some questions may be
 * incomplete. Invalid questions are dropped instead of failing the whole batch.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_parser {
    /** @var int Maximum length of a question name. */
    private const NAME_LENGTH = 100;

    /**
     * Parse the model output.
     *
     * @param string $raw Model output.
     * @param string $qtype Expected question type.
     * @param int $max Maximum number of questions to keep.
     * @return array[] Normalised questions.
     * @throws \moodle_exception When no valid question can be extracted.
     */
    public static function parse(string $raw, string $qtype, int $max): array {
        $data = self::decode($raw);
        $items = $data['questions'] ?? (array_is_list($data) ? $data : []);

        $questions = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = self::normalise($item, $qtype);
            if ($question !== null) {
                $questions[] = $question;
            }
            if (count($questions) >= $max) {
                break;
            }
        }
        if (!$questions) {
            throw new \moodle_exception('novalidquestions', 'qbank_aigen');
        }
        return $questions;
    }

    /**
     * Extract the JSON document from the output.
     *
     * @param string $raw
     * @return array
     */
    public static function decode(string $raw): array {
        $text = trim($raw);
        // Remove markdown code fences.
        if (preg_match('/\x60{3}(?:json)?\s*(.+?)\s*\x60{3}/is', $text, $matches)) {
            $text = $matches[1];
        }
        $data = json_decode($text, true);
        if (!is_array($data)) {
            // Fall back to the outermost JSON object or array in the text.
            $start = strcspn($text, '{[');
            $end = max(strrpos($text, '}') ?: 0, strrpos($text, ']') ?: 0);
            if ($start < strlen($text) && $end > $start) {
                $data = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }
        if (!is_array($data)) {
            throw new \moodle_exception('invalidresponse', 'qbank_aigen');
        }
        return $data;
    }

    /**
     * Normalise one question, or return null if it is not usable.
     *
     * @param array $item
     * @param string $qtype
     * @return array|null
     */
    public static function normalise(array $item, string $qtype): ?array {
        if (isset($item['type']) && $item['type'] !== $qtype) {
            return null;
        }
        $questiontext = self::clean($item['questiontext'] ?? '');
        if ($questiontext === '') {
            return null;
        }
        $question = [
            'type' => $qtype,
            'name' => self::make_name($item['name'] ?? '', $questiontext),
            'questiontext' => $questiontext,
            'generalfeedback' => self::clean($item['generalfeedback'] ?? ''),
        ];

        switch ($qtype) {
            case 'multichoice':
                $answers = [];
                foreach ((array) ($item['answers'] ?? []) as $answer) {
                    $text = self::clean(is_array($answer) ? ($answer['text'] ?? '') : '');
                    if ($text === '') {
                        continue;
                    }
                    $answers[] = [
                        'text' => $text,
                        'correct' => filter_var($answer['correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'feedback' => self::clean($answer['feedback'] ?? ''),
                    ];
                }
                $correct = count(array_filter(array_column($answers, 'correct')));
                $texts = array_map(fn($a) => \core_text::strtolower($a['text']), $answers);
                if (count($answers) < 3 || count($answers) > 6 || $correct !== 1 || count(array_unique($texts)) !== count($texts)) {
                    return null;
                }
                $question['answers'] = $answers;
                break;

            case 'truefalse':
                if (!array_key_exists('correct', $item)) {
                    return null;
                }
                $correct = filter_var($item['correct'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($correct === null) {
                    return null;
                }
                $question['correct'] = $correct;
                break;

            case 'shortanswer':
                $answers = [];
                foreach ((array) ($item['answers'] ?? []) as $answer) {
                    $text = self::clean(is_array($answer) ? ($answer['text'] ?? '') : $answer);
                    // The asterisk is a wildcard in short answer questions.
                    $text = str_replace('*', '', $text);
                    if ($text !== '' && !in_array($text, $answers, true)) {
                        $answers[] = $text;
                    }
                }
                if (!$answers) {
                    return null;
                }
                $question['answers'] = array_slice($answers, 0, 5);
                break;
        }
        return $question;
    }

    /**
     * Plain text, trimmed, with collapsed whitespace.
     *
     * @param mixed $value
     * @return string
     */
    private static function clean($value): string {
        if (!is_scalar($value)) {
            return '';
        }
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    }

    /**
     * Question name: the one proposed, or the beginning of the question text.
     *
     * @param mixed $name
     * @param string $questiontext
     * @return string
     */
    private static function make_name($name, string $questiontext): string {
        $name = self::clean($name);
        if ($name === '') {
            $name = $questiontext;
        }
        return \core_text::strlen($name) > self::NAME_LENGTH
            ? rtrim(\core_text::substr($name, 0, self::NAME_LENGTH - 3)) . '...'
            : $name;
    }
}
