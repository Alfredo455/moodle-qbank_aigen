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
 * Loading state of the generation form: generating can take up to a minute.
 *
 * @module     qbank_aigen/generate
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/**
 * Disable the submit button and show a progress message when the form is submitted.
 */
export const init = () => {
    const form = document.querySelector('form.mform');
    if (!form) {
        return;
    }
    form.addEventListener('submit', async() => {
        const button = form.querySelector('[type="submit"]');
        if (!button) {
            return;
        }
        button.disabled = true;
        const message = await getString('generating', 'qbank_aigen');
        button.insertAdjacentHTML('afterend',
            `<span class="ml-3 text-muted" role="status"><span class="spinner-border spinner-border-sm mr-2"></span></span>`);
        button.nextElementSibling.append(message);
    });
};
