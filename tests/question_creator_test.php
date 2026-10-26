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

use core_question\local\bank\question_version_status;
use qbank_aigen\local\drafts;
use qbank_aigen\local\question_creator;

/**
 * Tests of the creation of the questions and of the drafts.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_aigen\local\question_creator
 * @covers     \qbank_aigen\local\drafts
 * @covers     \qbank_aigen\task\cleanup_drafts
 */
final class question_creator_test extends \advanced_testcase {
    /**
     * A question category in a new course.
     *
     * @return \stdClass
     */
    private function create_category(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->get_plugin_generator('core_question')->create_question_category(
            ['contextid' => \context_course::instance($course->id)->id]
        );
    }

    /**
     * Questions of every type are saved as tagged drafts with the right answers.
     */
    public function test_create_many(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->create_category();

        $saved = question_creator::create_many([
            [
                'type' => 'multichoice', 'name' => 'MC', 'questiontext' => 'Which gas? <script>',
                'generalfeedback' => 'Oxygen.',
                'answers' => [
                    ['text' => 'Oxygen', 'correct' => true, 'feedback' => 'Yes'],
                    ['text' => 'Nitrogen', 'correct' => false, 'feedback' => ''],
                    ['text' => 'Helium', 'correct' => false, 'feedback' => ''],
                ],
            ],
            ['type' => 'truefalse', 'name' => 'TF', 'questiontext' => 'The sky is green.', 'generalfeedback' => '',
                'correct' => false],
            ['type' => 'shortanswer', 'name' => 'SA', 'questiontext' => 'Capital of Peru?', 'generalfeedback' => '',
                'answers' => ['Lima']],
        ], $category);

        $this->assertCount(3, $saved);
        foreach ($saved as $question) {
            $version = $DB->get_record('question_versions', ['questionid' => $question->id]);
            $this->assertSame(question_version_status::QUESTION_STATUS_DRAFT, $version->status);
            $tags = \core_tag_tag::get_item_tags_array('core_question', 'question', $question->id);
            $this->assertContains(question_creator::TAG, $tags);
        }

        $mc = \question_bank::load_question($saved[0]->id);
        $this->assertSame('<p>Which gas? &lt;script&gt;</p>', $mc->questiontext);
        $this->assertCount(3, $mc->answers);
        $fractions = array_map(fn($a) => (float) $a->fraction, array_values($mc->answers));
        $this->assertSame([1.0, 0.0, 0.0], $fractions);

        $tf = \question_bank::load_question($saved[1]->id);
        $this->assertFalse((bool) $tf->rightanswer);

        $sa = \question_bank::load_question($saved[2]->id);
        $this->assertSame('Lima', reset($sa->answers)->answer);
        $this->assertEquals($category->id, $sa->category);
    }

    /**
     * Drafts belong to their author, the rate limit applies and old drafts are cleaned.
     */
    public function test_drafts(): void {
        global $DB;
        $this->resetAfterTest();
        $now = $this->mock_clock_with_frozen()->time();
        $category = $this->create_category();
        $teacher = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $context = \context::instance_by_id($category->contextid);
        set_config('maxperhour', 2, 'qbank_aigen');

        $draftid = drafts::create($teacher->id, $context, $category, 'truefalse', [['questiontext' => 'Q']]);
        $this->assertSame('Q', drafts::get($draftid, $teacher->id)->questions[0]['questiontext']);
        $this->assertFalse(drafts::limit_reached($teacher->id));
        drafts::create($teacher->id, $context, $category, 'truefalse', []);
        $this->assertTrue(drafts::limit_reached($teacher->id));

        try {
            drafts::get($draftid, $other->id);
            $this->fail('Drafts of other users must not be readable');
        } catch (\dml_missing_record_exception $e) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $e);
        }

        $this->mock_clock_with_frozen($now + 2 * DAYSECS);
        (new \qbank_aigen\task\cleanup_drafts())->execute();
        $this->assertSame(0, $DB->count_records(drafts::TABLE));
    }
}
