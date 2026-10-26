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

/**
 * Navigation node of the generator in the question bank menu.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation extends \core_question\local\bank\navigation_node_base {
    /**
     * Title.
     *
     * @return string
     */
    public function get_navigation_title(): string {
        return get_string('navtitle', 'qbank_aigen');
    }

    /**
     * Key.
     *
     * @return string
     */
    public function get_navigation_key(): string {
        return 'aigen';
    }

    /**
     * URL.
     *
     * @return \moodle_url
     */
    public function get_navigation_url(): \moodle_url {
        return new \moodle_url('/question/bank/aigen/generate.php');
    }
}
