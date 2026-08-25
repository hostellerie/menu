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

        function addToken(data) {
            data[tokenName] = tokenValue;
            return data;
        }

        function reloadOnFailure(request) {
            request.fail(function () {
                window.location.reload();
            });
            return request;
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

        // Remove whole-row handlers installed by the legacy initialization,
        // then bind TableDnD again using only the dedicated handle cells.
        $table.find('tbody tr')
            .off('mousedown touchstart')
            .css('cursor', '');

        $table.tableDnD({
            dragHandle: 'menu-drag-handle',
            onDrop: function () {
                var data = addToken({
                    orders: $.tableDnD.serialize(),
                    menu_id: menuId
                });

                reloadOnFailure($.ajax({
                    type: 'POST',
                    url: postUrl,
                    data: data
                }));
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

            $.ajax({
                type: 'POST',
                url: postUrl,
                data: addToken({
                    mode: 'move',
                    where: direction,
                    mid: parseInt($(this).attr('data-mid'), 10) || 0,
                    menu: menuId
                })
            }).always(function () {
                window.location.reload();
            });
        });
    }

    $(function () {
        initMenuOrderHandle(0);
    });
}(jQuery));
