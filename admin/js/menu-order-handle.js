(function () {
    'use strict';

    function initMenuOrderHandle() {
        var table = document.getElementById('menu_table');
        var tokenContainer = document.getElementById('menu-order-token');
        var token = tokenContainer ? tokenContainer.querySelector('input[type="hidden"]') : null;

        if (!table || !token) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        var menuId = parseInt(table.getAttribute('data-menuid'), 10) || 0;
        var postUrl = table.getAttribute('data-post-url') || window.location.href;
        var tokenName = token.getAttribute('name');
        var tokenValue = token.value;
        var draggedRow = null;
        var dragMoved = false;

        function rows() {
            return tbody.querySelectorAll('tr[id^="mid_"]');
        }

        function rowId(row) {
            return parseInt(String(row.id || '').replace(/^mid_/, ''), 10) || 0;
        }

        function currentOrder() {
            var result = [];
            var list = rows();
            var i;
            var mid;

            for (i = 0; i < list.length; i += 1) {
                mid = rowId(list[i]);
                if (mid > 0) {
                    result.push('menu_table[]=mid_' + mid);
                }
            }

            return result.join('&');
        }

        function post(data, reloadAlways) {
            var xhr = new XMLHttpRequest();
            var parts = [];
            var key;

            data[tokenName] = tokenValue;
            for (key in data) {
                if (Object.prototype.hasOwnProperty.call(data, key)) {
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                }
            }

            xhr.open('POST', postUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (reloadAlways || xhr.status < 200 || xhr.status >= 300) {
                    window.location.reload();
                }
            };
            xhr.send(parts.join('&'));
        }

        function saveOrder() {
            var order = currentOrder();
            if (!order || menuId <= 0) {
                window.location.reload();
                return;
            }
            post({orders: order, menu_id: menuId}, false);
        }

        function findHandle(row) {
            var cells = row.children;
            if (!cells || cells.length < 2) {
                return null;
            }
            return cells[cells.length - 2];
        }

        function rowFromPoint(x, y) {
            var node = document.elementFromPoint(x, y);
            while (node && node !== tbody) {
                if (node.tagName && node.tagName.toLowerCase() === 'tr'
                    && node.parentNode === tbody
                    && rowId(node) > 0) {
                    return node;
                }
                node = node.parentNode;
            }
            return null;
        }

        function moveDraggedRow(x, y) {
            var target;
            var rect;
            var before;

            if (!draggedRow) {
                return;
            }

            target = rowFromPoint(x, y);
            if (!target || target === draggedRow) {
                return;
            }

            rect = target.getBoundingClientRect();
            before = y < rect.top + (rect.height / 2);
            tbody.insertBefore(draggedRow, before ? target : target.nextSibling);
            dragMoved = true;
        }

        function startDrag(row) {
            if (!row) {
                return;
            }
            draggedRow = row;
            dragMoved = false;
            row.setAttribute('data-menu-dragging', '1');
            document.documentElement.setAttribute('data-menu-ordering', '1');
        }

        function finishDrag() {
            if (!draggedRow) {
                return;
            }

            draggedRow.removeAttribute('data-menu-dragging');
            document.documentElement.removeAttribute('data-menu-ordering');

            if (dragMoved) {
                saveOrder();
            }

            draggedRow = null;
            dragMoved = false;
        }

        function mouseMove(event) {
            if (!draggedRow) {
                return;
            }
            event.preventDefault();
            moveDraggedRow(event.clientX, event.clientY);
        }

        function mouseUp() {
            finishDrag();
        }

        function touchMove(event) {
            var touch;
            if (!draggedRow || !event.touches || !event.touches.length) {
                return;
            }
            event.preventDefault();
            touch = event.touches[0];
            moveDraggedRow(touch.clientX, touch.clientY);
        }

        function touchEnd() {
            finishDrag();
        }

        document.addEventListener('mousemove', mouseMove, false);
        document.addEventListener('mouseup', mouseUp, false);
        document.addEventListener('touchmove', touchMove, {passive: false});
        document.addEventListener('touchend', touchEnd, false);
        document.addEventListener('touchcancel', touchEnd, false);

        function installRow(row) {
            var mid = rowId(row);
            var handle = findHandle(row);

            if (!mid || !handle) {
                return;
            }

            handle.className += (handle.className ? ' ' : '') + 'menu-drag-handle';
            handle.setAttribute('tabindex', '0');
            handle.setAttribute('role', 'button');
            handle.setAttribute('aria-label', 'Order');
            handle.setAttribute('title', 'Order (↑/↓)');
            handle.setAttribute('data-mid', mid);

            handle.addEventListener('mousedown', function (event) {
                if (event.button !== 0) {
                    return;
                }
                event.preventDefault();
                startDrag(row);
            }, false);

            handle.addEventListener('touchstart', function (event) {
                if (!event.touches || !event.touches.length) {
                    return;
                }
                event.preventDefault();
                startDrag(row);
            }, {passive: false});

            handle.addEventListener('keydown', function (event) {
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
                post({
                    mode: 'move',
                    where: direction,
                    mid: mid,
                    menu: menuId
                }, true);
            }, false);
        }

        var list = rows();
        var i;
        for (i = 0; i < list.length; i += 1) {
            installRow(list[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMenuOrderHandle);
    } else {
        initMenuOrderHandle();
    }
}());