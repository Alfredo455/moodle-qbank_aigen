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

/**
 * English strings for the AI question generator.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptpolicy'] = 'I accept the AI usage policy';
$string['aierror'] = 'The AI service returned an error: {$a}';
$string['aigen:generate'] = 'Generate questions with AI';
$string['aipolicy'] = 'AI usage policy';
$string['apiendpoint'] = 'API endpoint';
$string['apiendpoint_desc'] = 'Chat completions URL of an OpenAI-compatible API. Examples: https://api.openai.com/v1/chat/completions or, for a self-hosted Ollama server, http://ollama.internal:11434/v1/chat/completions.';
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Leave empty for self-hosted models that do not require authentication.';
$string['backend'] = 'AI backend';
$string['backend_coreai'] = 'Moodle AI subsystem (recommended)';
$string['backend_desc'] = 'The Moodle AI subsystem uses the providers configured in Site administration > General > AI, with usage logging and policy acceptance. The OpenAI-compatible API lets you use any provider or a self-hosted model.';
$string['backend_openai'] = 'OpenAI-compatible API';
$string['count'] = 'Number of questions';
$string['difficulty'] = 'Difficulty';
$string['difficulty_easy'] = 'Easy: recall of key facts';
$string['difficulty_hard'] = 'Hard: analysis and reasoning';
$string['difficulty_medium'] = 'Medium: understanding and application';
$string['discard'] = 'Discard and start again';
$string['generate'] = 'Generate questions';
$string['generating'] = 'Generating questions, this can take up to a minute...';
$string['instructions'] = 'Additional instructions';
$string['instructions_help'] = 'Optional guidance for the AI, for example "Focus on chapter 2" or "Use examples from Latin America".';
$string['intro'] = 'Paste your course material or pick a page of the course. The AI writes the questions, you review them, and the ones you keep are saved as drafts tagged "ai-generated".';
$string['invalidresponse'] = 'The AI answer could not be understood. Please try again.';
$string['keep'] = 'Keep';
$string['maxperhour'] = 'Generations per user and hour';
$string['maxperhour_desc'] = 'Limits the cost of the AI service.';
$string['maxsourcechars'] = 'Maximum source length';
$string['maxsourcechars_desc'] = 'Longer source material is truncated at a sentence boundary, in characters.';
$string['model'] = 'Model';
$string['model_desc'] = 'Model name, for example gpt-4o-mini or llama3.1.';
$string['navtitle'] = 'AI generator';
$string['noneselected'] = 'Select at least one question.';
$string['notconfigured'] = 'No AI service is available. Ask the administrator to enable an AI provider with the "Generate text" action, or to configure an OpenAI-compatible API in the plugin settings.';
$string['novalidquestions'] = 'The AI did not return any valid question. Try again with a longer or clearer source.';
$string['options'] = 'Options';
$string['pluginname'] = 'AI question generator';
$string['privacy:metadata:aiprovider'] = 'The source material chosen by the teacher is sent to the configured language model. It does not include personal data.';
$string['privacy:metadata:aiprovider:prompt'] = 'The instructions and the course material used to write the questions.';
$string['privacy:metadata:core_ai'] = 'When the Moodle AI subsystem is used, it logs every request.';
$string['privacy:metadata:draft'] = 'Generated questions waiting for review. They are deleted after one day.';
$string['privacy:metadata:draft:questions'] = 'The generated questions.';
$string['privacy:metadata:draft:timecreated'] = 'When the questions were generated.';
$string['privacy:metadata:draft:userid'] = 'The teacher who generated the questions.';
$string['questionssaved'] = '{$a} question(s) saved as drafts. Review them before using them in a quiz.';
$string['ratelimit'] = 'You have reached the maximum number of generations per hour. Please try again later.';
$string['review'] = 'Review the generated questions';
$string['reviewintro'] = '{$a->count} {$a->qtype} question(s) generated. The selected ones will be saved as drafts in "{$a->category}".';
$string['saveselected'] = 'Save selected questions';
$string['selectedcount'] = '{$a} selected';
$string['source'] = 'Source material';
$string['sourcepage'] = 'Course page';
$string['sourcetext'] = 'Text';
$string['sourcetext_placeholder'] = 'Paste here the content the questions must be about: notes, a chapter, a transcript...';
$string['sourcetooshort'] = 'The text must have at least {$a} characters.';
$string['sourcetype'] = 'Use';
$string['taskcleanup'] = 'Delete unreviewed AI generated questions';
