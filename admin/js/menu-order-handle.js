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

        function clearDragState() {
            var list = rows();
            var i;
            var handle;

            for (i = 0; i < list.length; i += 1) {
                list[i].removeAttribute('data-menu-dragging');
                handle = findHandle(list[i]);
                if (handle) {
                    handle.removeAttribute('data-menu-dragging');
                }
            }
            draggedRow = null;
        }

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
            handle.setAttribute('draggable', 'true');

            handle.addEventListener('dragstart', function (event) {
                draggedRow = row;
                row.setAttribute('data-menu-dragging', '1');
                handle.setAttribute('data-menu-dragging', '1');

                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', String(mid));
                }
            });

            row.addEventListener('dragover', function (event) {
                var rect;
                var before;

                if (!draggedRow || draggedRow === row) {
                    return;
                }

                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                rect = row.getBoundingClientRect();
                before = event.clientY < rect.top + (rect.height / 2);
                tbody.insertBefore(draggedRow, before ? row : row.nextSibling);
            });

            row.addEventListener('drop', function (event) {
                if (!draggedRow) {
                    return;
                }
                event.preventDefault();
                saveOrder();
                clearDragState();
            });

            handle.addEventListener('dragend', function () {
                if (draggedRow) {
                    saveOrder();
                }
                clearDragState();
            });

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
            });
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