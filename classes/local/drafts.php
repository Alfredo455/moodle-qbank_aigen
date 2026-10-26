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
 * Generated questions waiting for review, and the per-user rate limit.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drafts {
    /** @var string Table name. */
    public const TABLE = 'qbank_aigen_draft';

    /** @var int Default number of generations allowed per user and hour. */
    public const DEFAULT_MAX_PER_HOUR = 20;

    /**
     * Store a batch of generated questions.
     *
     * @param int $userid
     * @param \context $context
     * @param \stdClass $category
     * @param string $qtype
     * @param array[] $questions
     * @return int Draft id.
     */
    public static function create(int $userid, \context $context, \stdClass $category, string $qtype, array $questions): int {
        global $DB;
        return $DB->insert_record(self::TABLE, [
            'userid' => $userid,
            'contextid' => $context->id,
            'categoryid' => $category->id,
            'qtype' => $qtype,
            'questions' => json_encode($questions, JSON_UNESCAPED_UNICODE),
            'timecreated' => \core\di::get(\core\clock::class)->time(),
        ]);
    }

    /**
     * Load a draft of the user.
     *
     * @param int $draftid
     * @param int $userid
     * @return \stdClass Draft record with the questions decoded.
     */
    public static function get(int $draftid, int $userid): \stdClass {
        global $DB;
        $draft = $DB->get_record(self::TABLE, ['id' => $draftid, 'userid' => $userid], '*', MUST_EXIST);
        $draft->questions = json_decode($draft->questions, true) ?: [];
        return $draft;
    }

    /**
     * Delete a draft.
     *
     * @param int $draftid
     */
    public static function delete(int $draftid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['id' => $draftid]);
    }

    /**
     * Whether the user reached the hourly generation limit.
     *
     * @param int $userid
     * @return bool
     */
    public static function limit_reached(int $userid): bool {
        global $DB;
        $max = (int) (get_config('qbank_aigen', 'maxperhour') ?: self::DEFAULT_MAX_PER_HOUR);
        $since = \core\di::get(\core\clock::class)->time() - HOURSECS;
        return $DB->count_records_select(
            self::TABLE,
            'userid = :userid AND timecreated > :since',
            ['userid' => $userid, 'since' => $since]
        ) >= $max;
    }
}
