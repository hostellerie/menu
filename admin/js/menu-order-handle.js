(function ($) {
    'use strict';

    function initMenuOrderHandle(attempt) {
        var $table = $('#menu_table');
        var $token = $('#menu-order-token input[type="hidden"]').first();

        if (!$table.length || !$token.length) {
            return;
        }

        if (typeof $.fn.tableDnD !== 'function') {
            if (attempt < 50) {
                window.setTimeout(function () {
                    initMenuOrderHandle(attempt + 1);
                }, 100);
            }
            return;
        }

        if ($table.data('menu-order-handle-ready')) {
            return;
        }
        $table.data('menu-order-handle-ready', true);

        var menuId = parseInt($table.attr('data-menuid'), 10) || 0;
        var postUrl = $table.attr('data-post-url') || window.location.href;
        var tokenName = $token.attr('name');
        var tokenValue = $token.val();
        var submitting = false;

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

        function submitPost(fields) {
            var $form;
            var form;

            if (submitting) {
                return;
            }
            submitting = true;

            // Prevent a second tree action from being started while the browser
            // is navigating away. Geeklog CSRF tokens are one-time tokens.
            $table.css('pointer-events', 'none');

            $form = $('<form>', {
                method: 'post',
                action: postUrl
            }).css('display', 'none');

            $.each(fields, function (name, value) {
                $('<input>', {
                    type: 'hidden',
                    name: name,
                    value: value
                }).appendTo($form);
            });

            $('<input>', {
                type: 'hidden',
                name: tokenName,
                value: tokenValue
            }).appendTo($form);

            $('body').append($form);
            form = $form.get(0);

            // Use the native DOM submit method deliberately. jQuery .trigger('submit')
            // can be intercepted by page-level submit handlers and leave the old
            // menu tree visible with an already-consumed CSRF token.
            form.submit();
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
                var orders = currentOrder();

                if (!orders || menuId <= 0) {
                    window.location.reload();
                    return;
                }

                submitPost({
                    orders: orders,
                    menu_id: menuId
                });
            }
        });

        $table.on('keydown', 'td.menu-drag-handle', function (event) {
            var direction = null;
            var key = event.key || '';
            var keyCode = event.which || event.keyCode;

            if (key === 'ArrowUp' || keyCode === 38) {
                direction = 'up';
            } else if (key === 'ArrowDown' || keyCode === 40) {
                direction = 'down';
            }

            if (direction === null) {
                return;
            }

            event.preventDefault();

            submitPost({
                mode: 'move',
                where: direction,
                mid: parseInt($(this).attr('data-mid'), 10) || 0,
                menu: menuId
            });
        });
    }

    $(function () {
        initMenuOrderHandle(0);
    });
}(jQuery));
