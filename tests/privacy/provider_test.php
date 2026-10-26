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
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use qbank_aigen\local\drafts;

/**
 * Tests of the privacy provider.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_aigen\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Metadata, export and deletion.
     */
    public function test_provider(): void {
        global $DB;
        $this->resetAfterTest();
        $this->assertCount(3, provider::get_metadata(new collection('qbank_aigen'))->get_collection());

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $category = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category(
            ['contextid' => $context->id]
        );
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        drafts::create($user1->id, $context, $category, 'truefalse', [['questiontext' => 'Q1']]);
        drafts::create($user2->id, $context, $category, 'truefalse', [['questiontext' => 'Q2']]);

        $this->assertEquals([$context->id], provider::get_contexts_for_userid($user1->id)->get_contextids());
        $userlist = new userlist($context, 'qbank_aigen');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], $userlist->get_userids());

        $this->export_context_data_for_user($user1->id, $context, 'qbank_aigen');
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'qbank_aigen')]);
        $this->assertSame('Q1', $data->drafts[0]->questions[0]->questiontext);

        provider::delete_data_for_user(new approved_contextlist($user1, 'qbank_aigen', [$context->id]));
        $this->assertFalse($DB->record_exists(drafts::TABLE, ['userid' => $user1->id]));

        provider::delete_data_for_users(new approved_userlist($context, 'qbank_aigen', [$user2->id]));
        $this->assertSame(0, $DB->count_records(drafts::TABLE));

        drafts::create($user1->id, $context, $category, 'truefalse', []);
        provider::delete_data_for_all_users_in_context($context);
        $this->assertSame(0, $DB->count_records(drafts::TABLE));
    }
}
