(function (window, document) {
    'use strict';

    var attempts = 0;
    var maxAttempts = 100;

    function start() {
        var $ = window.jQuery;
        var $table;
        var $token;
        var menuId;
        var actionUrl;
        var tokenName;
        var tokenValue;
        var requestActive = false;
        var queue = [];
        var pendingOrder = null;
        var retryTimer = null;

        if (!$) {
            retry();
            return;
        }

        $table = $('#menu_table');
        $token = $('#menu-order-token input[type="hidden"]').first();

        if (!$table.length || !$token.length) {
            return;
        }

        /* Prevent the legacy menu-order-handle.js from taking control. */
        $table.data('menu-order-handle-ready', true);

        if (typeof $.fn.tableDnD !== 'function') {
            retry();
            return;
        }

        if ($table.data('menu-tree-actions-ready')) {
            return;
        }
        $table.data('menu-tree-actions-ready', true);

        menuId = parseInt($table.attr('data-menuid'), 10) || 0;
        actionUrl = String($table.attr('data-tree-action-url') || '');
        tokenName = $token.attr('name');
        tokenValue = $token.val();

        if (!menuId || !actionUrl || !tokenName || !tokenValue) {
            return;
        }

        function currentOrder() {
            var parts = [];

            $table.find('tbody tr[id^="mid_"]').each(function () {
                var mid = parseInt(String(this.id).replace(/^mid_/, ''), 10) || 0;
                if (mid > 0) {
                    parts.push('menu_table[]=mid_' + mid);
                }
            });

            return parts.join('&');
        }

        function refreshToken(response) {
            var previousName = tokenName;

            if (!response || !response.tokenName || !response.tokenValue) {
                return false;
            }

            tokenName = response.tokenName;
            tokenValue = response.tokenValue;

            $('input[type="hidden"][name="' + previousName + '"]').each(function () {
                this.name = tokenName;
                this.value = tokenValue;
            });

            return true;
        }

        function setSavingState(state) {
            $table.removeClass('menu-tree-saving menu-tree-save-error');
            if (state === 'saving') {
                $table.addClass('menu-tree-saving');
            } else if (state === 'error') {
                $table.addClass('menu-tree-save-error');
            }
        }

        function requeue(fields) {
            if (fields.tree_action === 'order') {
                /* Preserve the newest DOM order instead of an older failed snapshot. */
                if (pendingOrder === null) {
                    pendingOrder = {
                        tree_action: 'order',
                        orders: currentOrder(),
                        menu_id: menuId
                    };
                }
            } else {
                queue.unshift(fields);
            }
        }

        function scheduleRetry() {
            if (retryTimer !== null) {
                return;
            }

            retryTimer = window.setTimeout(function () {
                retryTimer = null;
                pumpQueue();
            }, 1200);
        }

        function sendAction(fields, retryCount) {
            var data = $.extend({}, fields);
            data[tokenName] = tokenValue;

            $.ajax({
                type: 'POST',
                url: actionUrl,
                data: data,
                dataType: 'json',
                cache: false
            }).done(function (response) {
                if (response && response.ok === true && refreshToken(response)) {
                    requestActive = false;
                    setSavingState('idle');
                    pumpQueue();
                    return;
                }

                if (response) {
                    refreshToken(response);
                }

                if (response && response.retry === true && retryCount < 3) {
                    sendAction(fields, retryCount + 1);
                    return;
                }

                requestActive = false;
                requeue(fields);
                setSavingState('error');
                scheduleRetry();
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var refreshed = response ? refreshToken(response) : false;

                if (response && response.retry === true && refreshed && retryCount < 3) {
                    sendAction(fields, retryCount + 1);
                    return;
                }

                /*
                 * Do not reload the page here. The request may have reached the
                 * server even if its response was lost. Order and activation are
                 * idempotent, so retaining and retrying the desired state is safe.
                 */
                if (retryCount < 3) {
                    window.setTimeout(function () {
                        sendAction(fields, retryCount + 1);
                    }, 300 * (retryCount + 1));
                    return;
                }

                requestActive = false;
                requeue(fields);
                setSavingState('error');
                scheduleRetry();
            });
        }

        function pumpQueue() {
            var fields;

            if (requestActive) {
                return;
            }

            if (queue.length) {
                fields = queue.shift();
            } else if (pendingOrder !== null) {
                fields = pendingOrder;
                pendingOrder = null;
            } else {
                setSavingState('idle');
                return;
            }

            requestActive = true;
            setSavingState('saving');
            sendAction(fields, 0);
        }

        function enqueue(fields) {
            if (fields.tree_action === 'order') {
                /* Only the newest unsaved DOM order matters. */
                pendingOrder = fields;
            } else {
                queue.push(fields);
            }
            pumpQueue();
        }

        function saveCurrentOrder() {
            var orders = currentOrder();

            if (!orders) {
                return;
            }

            enqueue({
                tree_action: 'order',
                orders: orders,
                menu_id: menuId
            });
        }

        $table.find('tbody tr').each(function () {
            var $row = $(this);
            var $cells = $row.children('td');
            var $handle;
            var mid;

            if ($cells.length < 7) {
                return;
            }

            $handle = $cells.eq($cells.length - 2);
            mid = parseInt(String($row.attr('id') || '').replace(/^mid_/, ''), 10) || 0;

            if (!mid) {
                return;
            }

            $handle
                .addClass('menu-drag-handle')
                .attr({
                    tabindex: '0',
                    role: 'button',
                    'aria-label': 'Order',
                    title: 'Order (↑/↓)',
                    'data-mid': mid
                });
        });

        $table.find('tbody tr')
            .off('mousedown touchstart')
            .css('cursor', '');

        $table.tableDnD({
            dragHandle: 'menu-drag-handle',
            onDrop: function () {
                saveCurrentOrder();
            }
        });

        /*
         * The server-rendered onclick remains the no-JavaScript fallback.
         * Remove it once this enhanced handler is ready, then let the checkbox
         * change normally and persist its resulting state through AJAX.
         */
        $table.find('input[type="checkbox"]').each(function () {
            this.onclick = null;
            $(this).removeAttr('onclick');
        }).on('change.menuTreeActions', function () {
            var form = this.form;
            var midInput;
            var mid;

            if (!form) {
                return;
            }

            midInput = form.querySelector('input[name="mid"]');
            mid = midInput ? parseInt(midInput.value, 10) || 0 : 0;

            if (!mid) {
                return;
            }

            enqueue({
                tree_action: 'activate',
                mode: 'activate',
                menu: menuId,
                mid: mid,
                active: this.checked ? 1 : 0
            });
        });

        $table.on('keydown.menuTreeActions', 'td.menu-drag-handle', function (event) {
            var key = event.key || '';
            var keyCode = event.which || event.keyCode;
            var $row = $(this).closest('tr');
            var $target;

            if (key === 'ArrowUp' || keyCode === 38) {
                $target = $row.prevAll('tr[id^="mid_"]').first();
                if ($target.length) {
                    event.preventDefault();
                    $row.insertBefore($target);
                    saveCurrentOrder();
                    $(this).focus();
                }
            } else if (key === 'ArrowDown' || keyCode === 40) {
                $target = $row.nextAll('tr[id^="mid_"]').first();
                if ($target.length) {
                    event.preventDefault();
                    $row.insertAfter($target);
                    saveCurrentOrder();
                    $(this).focus();
                }
            }
        });
    }

    function retry() {
        attempts++;
        if (attempts <= maxAttempts) {
            window.setTimeout(start, 50);
        }
    }

    start();
}(window, document));
