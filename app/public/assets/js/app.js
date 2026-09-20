var csrf = document.querySelector('input[name=csrf]');
var CSRF_TOKEN = csrf ? csrf.value : '';

function postForm(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams(data).toString()
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var shell = document.getElementById('shell');
    var navToggle = document.getElementById('navToggle');
    if (shell && navToggle) {
        function setNav(open) {
            shell.classList.toggle('is-nav-open', open);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        navToggle.addEventListener('click', function () {
            setNav(!shell.classList.contains('is-nav-open'));
        });
        shell.querySelectorAll('.sidebar a').forEach(function (a) {
            a.addEventListener('click', function () { setNav(false); });
        });
        document.addEventListener('click', function (e) {
            if (shell.classList.contains('is-nav-open') &&
                !e.target.closest('.sidebar') && !e.target.closest('#navToggle')) {
                setNav(false);
            }
        });
    }

    var boards = document.querySelectorAll('.board');
    boards.forEach(function (board) {
        var cols = board.querySelectorAll('.board__col');
        var cards = board.querySelectorAll('.task-card');

        cards.forEach(function (card) {
            card.addEventListener('dragstart', function (e) {
                card.classList.add('dragging');
                e.dataTransfer.setData('text/plain', card.getAttribute('data-id'));
                e.dataTransfer.effectAllowed = 'move';
            });
            card.addEventListener('dragend', function () {
                card.classList.remove('dragging');
                cols.forEach(function (c) { c.classList.remove('is-over'); });
            });
        });

        cols.forEach(function (col) {
            col.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                col.classList.add('is-over');
            });
            col.addEventListener('dragleave', function () {
                col.classList.remove('is-over');
            });
            col.addEventListener('drop', function (e) {
                e.preventDefault();
                col.classList.remove('is-over');
                var taskId = e.dataTransfer.getData('text/plain');
                var status = col.getAttribute('data-status');
                if (!taskId || !status) return;

                postForm('index.php?action=change_status&_x=' + Math.random(), {
                    action: 'change_status',
                    csrf: CSRF_TOKEN,
                    task_id: taskId,
                    status: status
                }).then(function (res) { return res.json(); }).then(function (data) {
                    if (data && data.ok) {
                        window.location.reload();
                    } else {
                        alert('Не удалось изменить статус (нет прав?).');
                    }
                }).catch(function () {
                    alert('Ошибка сети.');
                });
            });
        });
    });
});