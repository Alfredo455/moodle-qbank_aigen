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

use qbank_aigen\local\ai\backend;
use qbank_aigen\local\ai\backend_factory;

/**
 * Orchestrates prompt building, the call to the model and the parsing of the answer.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator {
    /** @var backend The language model. */
    protected backend $backend;

    /**
     * Constructor.
     *
     * @param backend|null $backend Defaults to the configured backend.
     */
    public function __construct(?backend $backend = null) {
        $this->backend = $backend ?? backend_factory::create();
    }

    /**
     * The backend in use.
     *
     * @return backend
     */
    public function get_backend(): backend {
        return $this->backend;
    }

    /**
     * Generate questions.
     *
     * @param generation_request $request
     * @param \context $context
     * @param int $userid
     * @return array[] Normalised questions.
     */
    public function generate(generation_request $request, \context $context, int $userid): array {
        $maxsource = (int) (get_config('qbank_aigen', 'maxsourcechars') ?: prompt_builder::DEFAULT_MAX_SOURCE);
        $prompt = prompt_builder::build($request, $maxsource);
        $output = $this->backend->generate($prompt, $context, $userid);
        return response_parser::parse($output, $request->qtype, $request->count);
    }
}
