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
 * Settings of the AI question generator.
 *
 * @package    qbank_aigen
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use qbank_aigen\local\ai\backend_factory;
use qbank_aigen\local\ai\openai_backend;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configselect(
        'qbank_aigen/backend',
        new lang_string('backend', 'qbank_aigen'),
        new lang_string('backend_desc', 'qbank_aigen'),
        backend_factory::CORE_AI,
        [
            backend_factory::CORE_AI => new lang_string('backend_coreai', 'qbank_aigen'),
            backend_factory::OPENAI => new lang_string('backend_openai', 'qbank_aigen'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'qbank_aigen/apiendpoint',
        new lang_string('apiendpoint', 'qbank_aigen'),
        new lang_string('apiendpoint_desc', 'qbank_aigen'),
        openai_backend::DEFAULT_ENDPOINT,
        PARAM_URL,
        60
    ));
    $settings->hide_if('qbank_aigen/apiendpoint', 'qbank_aigen/backend', 'neq', backend_factory::OPENAI);

    $settings->add(new admin_setting_configpasswordunmask(
        'qbank_aigen/apikey',
        new lang_string('apikey', 'qbank_aigen'),
        new lang_string('apikey_desc', 'qbank_aigen'),
        ''
    ));
    $settings->hide_if('qbank_aigen/apikey', 'qbank_aigen/backend', 'neq', backend_factory::OPENAI);

    $settings->add(new admin_setting_configtext(
        'qbank_aigen/model',
        new lang_string('model', 'qbank_aigen'),
        new lang_string('model_desc', 'qbank_aigen'),
        openai_backend::DEFAULT_MODEL,
        PARAM_TEXT
    ));
    $settings->hide_if('qbank_aigen/model', 'qbank_aigen/backend', 'neq', backend_factory::OPENAI);

    $settings->add(new admin_setting_configtext(
        'qbank_aigen/maxperhour',
        new lang_string('maxperhour', 'qbank_aigen'),
        new lang_string('maxperhour_desc', 'qbank_aigen'),
        \qbank_aigen\local\drafts::DEFAULT_MAX_PER_HOUR,
        PARAM_INT,
        4
    ));

    $settings->add(new admin_setting_configtext(
        'qbank_aigen/maxsourcechars',
        new lang_string('maxsourcechars', 'qbank_aigen'),
        new lang_string('maxsourcechars_desc', 'qbank_aigen'),
        \qbank_aigen\local\prompt_builder::DEFAULT_MAX_SOURCE,
        PARAM_INT,
        6
    ));
}
