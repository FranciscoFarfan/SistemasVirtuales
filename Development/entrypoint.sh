set -e

if [ ! -d /var/lib/mysql/mysql ]; then
    echo ">> Inicializando el directorio de datos de MariaDB..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null
fi

rm -f /var/run/apache2/apache2.pid /run/mysqld/mysqld.pid
mkdir -p /run/mysqld && chown -R mysql:mysql /run/mysqld /var/lib/mysql

cat > /etc/apache2/conf-enabled/db-env.conf <<CONF
SetEnv DB_HOST ${DB_HOST}
SetEnv DB_NAME ${DB_NAME}
SetEnv DB_USER ${DB_USER}
SetEnv DB_PASS ${DB_PASS}
CONF

mysqld_safe --user=mysql --bind-address=127.0.0.1 --default-time-zone='-06:00' > /dev/null 2>&1 &

for i in $(seq 1 30); do
    mysqladmin ping --silent && break
    sleep 1
done

if [ ! -d "/var/lib/mysql/${DB_NAME}" ]; then
    echo ">> Creando base de datos y usuario de la aplicación..."
fi
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

consulta() { mysql -u root -N -B "${DB_NAME}" -e "$1"; }
ES_V1=$(consulta "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='alumnos' AND COLUMN_NAME='nombre_alumno'")
if [ "$ES_V1" = "1" ]; then
    echo ">> Datos de la versión 1 encontrados: se respaldan en la tabla alumnos_v1"
    consulta "RENAME TABLE alumnos TO alumnos_v1"
fi

mysql -u root "${DB_NAME}" < /docker-init/init.sql

if [ "$ES_V1" = "1" ]; then
    echo ">> Migrando alumnos de la versión 1 con nombres y apellidos separados..."
    mysql -u root "${DB_NAME}" < /docker-init/migrar_v1.sql
fi

USUARIOS=$(consulta "SELECT COUNT(*) FROM usuarios")
if [ "$USUARIOS" = "0" ]; then
    HASH=$(ADMIN_PASS="${ADMIN_PASS}" php -r 'echo password_hash(getenv("ADMIN_PASS"), PASSWORD_DEFAULT);')
    consulta "INSERT INTO usuarios (nombre, apellido_paterno, usuario, password_hash, rol)
              VALUES ('Administrador', 'del sistema', '${ADMIN_USER}', '${HASH}', 'admin')"
    echo ">> Usuario administrador creado: ${ADMIN_USER} (cambia la contraseña al entrar)"
else
    echo ">> Base de datos existente encontrada: se conservan los datos."
fi

apagar() {
    echo ">> Deteniendo servicios..."
    apachectl -k graceful-stop 2>/dev/null || true
    mysqladmin -u root shutdown 2>/dev/null || true
    exit 0
}
trap apagar SIGTERM SIGINT

echo ">> Sistema de Tutorías listo en http://localhost:10085"

apachectl -D FOREGROUND &
wait $!