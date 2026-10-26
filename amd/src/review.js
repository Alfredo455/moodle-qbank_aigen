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
 * Selection helpers of the review page.
 *
 * @module     qbank_aigen/review
 * @copyright  2026 Alfredo (https://github.com/Alfredo455)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

const SELECTORS = {
    ROOT: '[data-region="qbank_aigen-review"]',
    SELECTALL: '[data-action="qbank_aigen-selectall"]',
    ITEM: '[data-region="qbank_aigen-select"]',
    COUNT: '[data-region="qbank_aigen-selectedcount"]',
};

/**
 * Update the "select all" checkbox and the counter after a change.
 *
 * @param {HTMLElement} root
 */
const refresh = async(root) => {
    const items = [...root.querySelectorAll(SELECTORS.ITEM)];
    const selected = items.filter((item) => item.checked).length;
    const selectAll = root.querySelector(SELECTORS.SELECTALL);
    selectAll.checked = selected === items.length;
    selectAll.indeterminate = selected > 0 && selected < items.length;
    root.querySelector(SELECTORS.COUNT).textContent = await getString('selectedcount', 'qbank_aigen', selected);
    root.querySelector('[name="save"]').disabled = selected === 0;
};

/**
 * Register the listeners.
 */
export const init = () => {
    const root = document.querySelector(SELECTORS.ROOT);
    if (!root) {
        return;
    }
    root.addEventListener('change', (e) => {
        if (e.target.matches(SELECTORS.SELECTALL)) {
            root.querySelectorAll(SELECTORS.ITEM).forEach((item) => {
                item.checked = e.target.checked;
            });
        }
        if (e.target.matches(`${SELECTORS.SELECTALL}, ${SELECTORS.ITEM}`)) {
            refresh(root);
        }
    });
};
