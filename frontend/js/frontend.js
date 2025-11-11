/*
 * @copyright (c) 2024.
 * @author            Alan Fuller (support@fullworks)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworks.net
 *
 * This file is part of Fullworks Anti Spam.
 *
 *     Fullworks Anti Spam is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     Fullworks Anti Spam is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with   Fullworks Anti Spam.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 */
const fas_forms = FullworksAntiSpamFELO.form_selectors;
let activityTimer;
let sustainedActivity = false;
let honeyInputAdded = false;

(function ($) {
    'use strict';

    // Function to detect if the page was accessed via the back button
    const isBackAction = () => {
        return history.state && history.state.isBackAction;
    };

    // Function to mark that the current state is not a back action
    const markAsNotBackAction = () => {
        if (!history.state || !history.state.isBackAction) {
            history.replaceState({ isBackAction: false }, document.title, location.href);
        }
    };

    // Function to mark the state as a back action
    const markAsBackAction = () => {
        history.pushState({ isBackAction: true }, document.title, location.href);
    };

    // Mark the current state as not a back action on page load
    $(window).on('load', markAsNotBackAction);

    /** anti spam fields */
    $(function () {
        const addHoneyInput = () => {
            if ((sustainedActivity || isBackAction()) && !honeyInputAdded) {
                $.ajax({
                    url: FullworksAntiSpamFELO.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: { action: "fwas_get_keys" },
                    success: function (response) {
                        const fas_honeyinput = `<input type='hidden' name='${response.name}' value='${response.value}' />`;
                        $(fas_forms).each(function () {
                            if (!$(this).find(`input[name='${FullworksAntiSpamFELO.name}']`).length) {
                                $(this).prepend(fas_honeyinput);
                            }
                        });
                        honeyInputAdded = true;
                    }
                });
            }
        };
        const detectActivity = () => {
            if (!sustainedActivity) {
                clearTimeout(activityTimer);
                activityTimer = setTimeout(() => {
                    sustainedActivity = true;
                    addHoneyInput();
                }, 1000); // wait for 1 seconds of sustained activity
            }
        };
        $(document).on('mousemove keypress', detectActivity);
        // Adding 'focus' event to catch Tab key navigation
        $(document).on('focus', 'input, select, textarea, button, a', detectActivity);
        // Mutation observer to ensure the form keeps the honeyinput field
        const mutationObserver = new MutationObserver(addHoneyInput);
        mutationObserver.observe(document.body, { childList: true, subtree: true });

        // Mark the current state as a back action before the page unloads
        window.addEventListener('pagehide', markAsBackAction);

        // Handle page restoration from back/forward cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page was restored from bfcache, ensure honeyinput is added
                addHoneyInput();
            }
        });
    });
})(jQuery);