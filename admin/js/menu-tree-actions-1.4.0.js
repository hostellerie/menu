(function (window) {
    'use strict';

    function init() {
        var $ = window.jQuery;
        var $table;
        var menuId;
        var actionUrl;
        var request = null;
        var pendingOrder = null;

        if (!$) {
            window.setTimeout(init, 50);
            return;
        }

        $table = $('#menu_table');
        if (!$table.length) {
            return;
        }

        /* Stop the legacy ordering adapter when it is also present. */
        $table.data('menu-order-handle-ready', true);

        if (typeof $.fn.tableDnD !== 'function') {
            window.setTimeout(init, 50);
            return;
        }

        if ($table.data('menu-tree-actions-ready')) {
            return;
        }
        $table.data('menu-tree-actions-ready', true);

        menuId = parseInt($table.attr('data-menuid'), 10) || 0;
        actionUrl = String($table.attr('data-tree-action-url') || '');
        if (!menuId || !actionUrl) {
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

        function saveOrder() {
            var orders = currentOrder();

            if (!orders) {
                return;
            }

            if (request !== null) {
                /* Only the newest not-yet-sent order matters. */
                pendingOrder = orders;
                return;
            }

            request = $.ajax({
                type: 'POST',
                url: actionUrl,
                dataType: 'json',
                cache: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    menu_id: menuId,
                    orders: orders
                }
            }).always(function () {
                request = null;

                if (pendingOrder !== null) {
                    orders = pendingOrder;
                    pendingOrder = null;
                    saveOrder();
                }
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
            onDrop: saveOrder
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
                    saveOrder();
                    $(this).focus();
                }
            } else if (key === 'ArrowDown' || keyCode === 40) {
                $target = $row.nextAll('tr[id^="mid_"]').first();
                if ($target.length) {
                    event.preventDefault();
                    $row.insertAfter($target);
                    saveOrder();
                    $(this).focus();
                }
            }
        });
    }

    init();
}(window));
