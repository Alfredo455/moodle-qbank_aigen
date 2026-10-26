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
 * Returns the backend selected in the plugin settings.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backend_factory {
    /** @var string Moodle AI subsystem. */
    public const CORE_AI = 'coreai';

    /** @var string OpenAI-compatible API. */
    public const OPENAI = 'openai';

    /** @var backend|null Backend forced by tests. */
    private static ?backend $override = null;

    /**
     * The configured backend.
     *
     * @return backend
     */
    public static function create(): backend {
        global $CFG;
        if (self::$override) {
            return self::$override;
        }
        if (!empty($CFG->qbank_aigen_demo) || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
            return new demo_backend();
        }
        return get_config('qbank_aigen', 'backend') === self::OPENAI ? new openai_backend() : new core_ai_backend();
    }

    /**
     * Force a backend (for automated tests only).
     *
     * @param backend|null $backend Null to restore the configured one.
     */
    public static function set_override(?backend $backend): void {
        self::$override = $backend;
    }
}
