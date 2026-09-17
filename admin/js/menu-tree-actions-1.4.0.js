(function (window, document) {
    'use strict';

    var attempts = 0;
    var maxAttempts = 100;

    function start() {
        var $ = window.jQuery;
        var $table;
        var $token;
        var tableNode;
        var menuId;
        var postUrl;
        var tokenName;
        var tokenValue;
        var submitting = false;

        if (!$) {
            retry();
            return;
        }

        $table = $('#menu_table');
        $token = $('#menu-order-token input[type="hidden"]').first();

        if (!$table.length || !$token.length) {
            return;
        }

        /* Prevent the legacy/cached menu-order-handle.js from taking control. */
        $table.data('menu-order-handle-ready', true);

        if (typeof $.fn.tableDnD !== 'function') {
            retry();
            return;
        }

        if ($table.data('menu-tree-actions-ready')) {
            return;
        }
        $table.data('menu-tree-actions-ready', true);

        tableNode = $table.get(0);
        menuId = parseInt($table.attr('data-menuid'), 10) || 0;
        postUrl = $table.attr('data-post-url') || window.location.href;
        tokenName = $token.attr('name');
        tokenValue = $token.val();

        function lockPage() {
            if (submitting) {
                return false;
            }
            submitting = true;
            $table.css('pointer-events', 'none');
            $('body').css('cursor', 'wait');
            return true;
        }

        function submitPost(fields) {
            var form;
            var name;
            var input;

            if (!lockPage()) {
                return;
            }

            form = document.createElement('form');
            form.method = 'post';
            form.action = postUrl;
            form.style.display = 'none';

            for (name in fields) {
                if (Object.prototype.hasOwnProperty.call(fields, name)) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = fields[name];
                    form.appendChild(input);
                }
            }

            input = document.createElement('input');
            input.type = 'hidden';
            input.name = tokenName;
            input.value = tokenValue;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
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

        /*
         * Capture activation clicks before the legacy inline onclick handler
         * can call this.form.submit(). This guarantees that drag and activation
         * use the exact same native POST + redirect path and the same page lock.
         */
        tableNode.addEventListener('click', function (event) {
            var target = event.target || event.srcElement;
            var form;
            var midInput;
            var activeInput;
            var mid;
            var active;

            if (!target || target.tagName !== 'INPUT' || target.type !== 'checkbox') {
                return;
            }

            form = target.form;
            if (!form) {
                return;
            }

            event.preventDefault();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }
            event.stopPropagation();

            midInput = form.querySelector('input[name="mid"]');
            activeInput = form.querySelector('input[name="active"]');
            mid = midInput ? parseInt(midInput.value, 10) || 0 : 0;
            active = activeInput ? parseInt(activeInput.value, 10) || 0 : 0;

            if (!mid || !menuId) {
                window.location.reload();
                return;
            }

            submitPost({
                mode: 'activate',
                menu: menuId,
                mid: mid,
                active: active
            });
        }, true);

        $table.on('keydown.menuTreeActions', 'td.menu-drag-handle', function (event) {
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

    function retry() {
        attempts++;
        if (attempts <= maxAttempts) {
            window.setTimeout(start, 50);
        }
    }

    start();
}(window, document));
