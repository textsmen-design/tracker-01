#!/bin/bash
# Tracker_01 — локальный запуск окружения (MariaDB + PHP) для разработки.
# Используется XAMPP-окружение: PHP 8.2.4 из /Applications/XAMPP, MariaDB из XAMPP,
# отдельный datadir в домашнем каталоге (без sudo), чтобы не трогать системный /Applications/XAMPP/var.
# Повторный запуск безопасен: если процессы уже запущены — просто пропускает.

XAMPP=/Applications/XAMPP/xamppfiles
PHPBIN="$XAMPP/bin/php"
DATADIR="$HOME/xampp-mysql-data"
PORT=3306
SOCK=/tmp/xampp_mysql.sock
PHP_PORT=8080
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"

if [ ! -x "$PHPBIN" ]; then
    echo "XAMPP не найден в $XAMPP" >&2; exit 1
fi
if [ ! -d "$DATADIR/mysql" ]; then
    echo "Datadir не инициализирован ($DATADIR). Запустите: mariadb-install-db ..." >&2; exit 1
fi

# 1) MariaDB
if [ -S "$SOCK" ] && "$XAMPP/bin/mysqladmin" --no-defaults -h 127.0.0.1 -P $PORT -u root ping >/dev/null 2>&1; then
    echo "MariaDB уже запущен (127.0.0.1:$PORT)"
else
    nohup "$XAMPP/sbin/mariadbd" --no-defaults --datadir="$DATADIR" --basedir="$XAMPP" \
        --user="$(whoami)" --bind-address=127.0.0.1 --port=$PORT --socket=$SOCK \
        --pid-file=/tmp/xampp_mysql.pid --log-error=/tmp/xampp_mysql.err \
        > /tmp/xampp_mysql_console.log 2>&1 &
    sleep 5
    if "$XAMPP/bin/mysqladmin" --no-defaults -h 127.0.0.1 -P $PORT -u root ping >/dev/null 2>&1; then
        echo "MariaDB запущен (127.0.0.1:$PORT)"
    else
        echo "Не удалось запустить MariaDB. Лог: /tmp/xampp_mysql.err" >&2; exit 1
    fi
fi

# 2) PHP-сервер
if curl -s -o /dev/null "http://127.0.0.1:$PHP_PORT/index.php" 2>/dev/null; then
    echo "PHP-сервер уже работает ($PHP_PORT)"
else
    nohup "$PHPBIN" -S "127.0.0.1:$PHP_PORT" -t "$APP_DIR/app/public" \
        > /tmp/tracker_php_server.log 2>&1 &
    sleep 2
    echo "PHP-сервер запущен на http://127.0.0.1:$PHP_PORT"
fi

echo
echo "Приложение: http://127.0.0.1:$PHP_PORT"
echo "Вход: anna@demo.ru / maxim@demo.ru / elena@demo.ru, пароль demo123"