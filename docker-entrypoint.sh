#!/bin/sh
set -e

echo "Aguardando o MySQL em ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
attempt=0
# --skip-ssl: o cliente MariaDB da imagem (Debian trixie) exige certificado
# válido por padrão e recusa o autoassinado do MySQL 8. É só um teste de
# disponibilidade na rede interna do Docker; o Laravel conecta via pdo_mysql.
until mysqladmin ping --skip-ssl -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-secret}" --silent; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "MySQL não respondeu a tempo. Abortando."
        exit 1
    fi
    sleep 2
done
echo "MySQL pronto."

php artisan migrate --force
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port=8000
