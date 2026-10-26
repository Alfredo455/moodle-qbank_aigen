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

use core_ai\aiactions\generate_text;
use core_ai\manager;

/**
 * Uses the AI subsystem introduced in Moodle 4.5.
 *
 * Providers (OpenAI, Azure OpenAI...), their keys, rate limits and logging are
 * configured by the site administrator in Site administration > General > AI.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_ai_backend implements backend {
    /**
     * At least one enabled provider must support text generation.
     *
     * @return bool
     */
    public function is_available(): bool {
        return manager::is_action_available(generate_text::class);
    }

    /**
     * The AI subsystem requires every user to accept the site AI policy.
     *
     * @param int $userid
     * @return bool
     */
    public function requires_policy_acceptance(int $userid): bool {
        return !manager::get_user_policy_status($userid);
    }

    /**
     * Generate text through the configured providers.
     *
     * @param string $prompt
     * @param \context $context
     * @param int $userid
     * @return string
     */
    public function generate(string $prompt, \context $context, int $userid): string {
        $action = new generate_text(
            contextid: $context->id,
            userid: $userid,
            prompttext: $prompt,
        );
        $response = \core\di::get(manager::class)->process_action($action);
        if (!$response->get_success()) {
            throw new \moodle_exception('aierror', 'qbank_aigen', '', $response->get_errormessage());
        }
        return (string) ($response->get_response_data()['generatedcontent'] ?? '');
    }
}
