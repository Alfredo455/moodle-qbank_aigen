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
 * A language model able to turn a prompt into text.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface backend {
    /**
     * Whether the backend is configured and can be used.
     *
     * @return bool
     */
    public function is_available(): bool;

    /**
     * Whether the user must accept the AI policy before using the backend.
     *
     * @param int $userid
     * @return bool
     */
    public function requires_policy_acceptance(int $userid): bool;

    /**
     * Generate text from a prompt.
     *
     * @param string $prompt
     * @param \context $context Context the generation happens in.
     * @param int $userid User requesting the generation.
     * @return string The generated text.
     * @throws \moodle_exception When the model cannot be reached or refuses.
     */
    public function generate(string $prompt, \context $context, int $userid): string;
}
