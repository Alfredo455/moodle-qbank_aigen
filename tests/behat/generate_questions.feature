@qbank @qbank_aigen
Feature: Generate questions with AI
  In order to build question banks faster
  As a teacher
  I need to generate questions from my course material, review them and save the good ones

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Biology  | BIO1      |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | BIO1   | editingteacher |
    And the following "activities" exist:
      | activity | name           | course | content                                                                                                                                                                                                                                                                                                  |
      | page     | Photosynthesis | BIO1   | Photosynthesis is the process plants use to convert light energy into chemical energy. It takes place in the chloroplasts, which contain a green pigment called chlorophyll. During photosynthesis, plants absorb carbon dioxide and release oxygen into the atmosphere. |

  Scenario: Generate, review and save questions from a course page
    Given I am on the "BIO1" "core_question > course question bank" page logged in as "teacher1"
    And I select "AI generator" from the "Question bank tertiary navigation" singleselect
    And I set the following fields to these values:
      | Use                  | Course page         |
      | Number of questions  | 2                   |
    When I press "Generate questions"
    Then I should see "Review the generated questions"
    And I should see "2 Multiple choice question(s) generated"
    And I set the field "qbank_aigen-q1" to "0"
    And I press "Save selected questions"
    And I should see "1 question(s) saved as drafts"
    And I should see "Demo 1:"
    And I should not see "Demo 2:"

  Scenario: The source text must be long enough
    Given I am on the "BIO1" "core_question > course question bank" page logged in as "teacher1"
    And I select "AI generator" from the "Question bank tertiary navigation" singleselect
    And I set the field "Text" to "Too short"
    When I press "Generate questions"
    Then I should see "The text must have at least 200 characters."
