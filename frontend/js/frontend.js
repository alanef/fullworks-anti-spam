/*
 * @copyright (c) 2019.
 * @author            Alan Fuller (support@fullworks)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworks.net
 *
 * This file is part of Fullworks Security.
 *
 *     Fullworks Security is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.a
 *
 *     Fullworks Security is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with   Fullworks Security.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 */

var fas_honeyinput = "<input type='hidden' name='" + FullworksAntiSpamFELO.name + "' value='" + FullworksAntiSpamFELO.value + "' />";
var fas_forms = "#commentform, #comments-form, .wpd_comm_form";


(function ($) {
    'use strict';
    /** anti spam fields */
    $(function () {
        $(fas_forms).prepend(fas_honeyinput);
        var whatToObserve = {
            childList: true,
            attributes: true,
            subtree: true,
            attributeOldValue: true,
            attributeFilter: ['class', 'style']
        };
        var mutationObserver = new MutationObserver(function (mutationRecords) {
            $.each(mutationRecords, function (index, mutationRecord) {
                if (mutationRecord.type === 'childList') {
                    if (mutationRecord.addedNodes.length > 0) {
                        mutationRecord.addedNodes.forEach(function (addnode) {
                            $(addnode).find(fas_forms).prepend(fas_honeyinput);
                        });
                    }
                }
            });
        });
        mutationObserver.observe(document.body, whatToObserve);
    });

})(jQuery);
