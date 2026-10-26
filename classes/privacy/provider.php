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

namespace qbank_aigen\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use qbank_aigen\local\drafts;

/**
 * Privacy API implementation (GDPR).
 *
 * The plugin keeps unreviewed questions for one day and sends course content
 * (never personal data) to the configured language model.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the stored and transmitted data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(drafts::TABLE, [
            'userid' => 'privacy:metadata:draft:userid',
            'questions' => 'privacy:metadata:draft:questions',
            'timecreated' => 'privacy:metadata:draft:timecreated',
        ], 'privacy:metadata:draft');
        $collection->add_subsystem_link('core_ai', [], 'privacy:metadata:core_ai');
        $collection->add_external_location_link('aiprovider', [
            'prompt' => 'privacy:metadata:aiprovider:prompt',
        ], 'privacy:metadata:aiprovider');
        return $collection;
    }

    /**
     * Contexts where the user has drafts.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT contextid FROM {' . drafts::TABLE . '} WHERE userid = :userid',
            ['userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Users with drafts in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {' . drafts::TABLE . '} WHERE contextid = :contextid',
            ['contextid' => $userlist->get_context()->id]
        );
    }

    /**
     * Export the drafts of the user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $records = $DB->get_records(drafts::TABLE, ['userid' => $userid, 'contextid' => $context->id]);
            $exported = [];
            foreach ($records as $record) {
                $exported[] = (object) [
                    'questions' => json_decode($record->questions),
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            if ($exported) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'qbank_aigen')],
                    (object) ['drafts' => $exported]
                );
            }
        }
    }

    /**
     * Delete all drafts of a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $DB->delete_records(drafts::TABLE, ['contextid' => $context->id]);
    }

    /**
     * Delete the drafts of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contextids() as $contextid) {
            $DB->delete_records(drafts::TABLE, ['userid' => $userid, 'contextid' => $contextid]);
        }
    }

    /**
     * Delete the drafts of several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['contextid'] = $userlist->get_context()->id;
        $DB->delete_records_select(drafts::TABLE, "contextid = :contextid AND userid $insql", $params);
    }
}
