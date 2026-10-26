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
 * What the teacher asked for.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generation_request {
    /** @var string[] Supported question types. */
    public const QTYPES = ['multichoice', 'truefalse', 'shortanswer'];

    /** @var string[] Supported difficulty levels. */
    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /** @var int Maximum number of questions per request. */
    public const MAX_QUESTIONS = 10;

    /**
     * Constructor.
     *
     * @param string $source Source material (plain text).
     * @param string $qtype One of {@see self::QTYPES}.
     * @param int $count Number of questions.
     * @param string $difficulty One of {@see self::DIFFICULTIES}.
     * @param string $language Name of the language of the questions, e.g. "Español".
     * @param string $instructions Extra instructions from the teacher.
     */
    public function __construct(
        /** @var string Source material. */
        public readonly string $source,
        /** @var string Question type. */
        public readonly string $qtype,
        /** @var int Number of questions. */
        public readonly int $count,
        /** @var string Difficulty. */
        public readonly string $difficulty = 'medium',
        /** @var string Language name. */
        public readonly string $language = 'English',
        /** @var string Extra instructions. */
        public readonly string $instructions = '',
    ) {
        if (!in_array($qtype, self::QTYPES, true)) {
            throw new \coding_exception("Unsupported question type: {$qtype}");
        }
        if (!in_array($difficulty, self::DIFFICULTIES, true)) {
            throw new \coding_exception("Unsupported difficulty: {$difficulty}");
        }
        if ($count < 1 || $count > self::MAX_QUESTIONS) {
            throw new \coding_exception("The number of questions must be between 1 and " . self::MAX_QUESTIONS);
        }
    }
}
