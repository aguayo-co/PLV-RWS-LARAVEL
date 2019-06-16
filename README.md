# Prilov - Laravel backend

Backend basado en Laravel para Prilov.

## Desarrollo

Instalar y configurar Laravel localmente.

```bash
$ cp .env.example .env
$ composer install
$ php artisan migrate --step
$ php artisan key:generate
$ php artisan passport:keys
$ php artisan passport:client --personal -n
```

Cargar datos de prueba

```bash
$ php artisan migration:refresh --step --seed
```

Unit testing

```bash
$ phpunit
```

## Cron y tareas

Las siguientes tareas son necesarias para que la aplicación se ejecute correctamente.
Se deben correr idealmente una vez cada hora cada una.

Esas tareas están programadas con en Schedule de Laravel. Si se corre Laravel Schedule
cada minuto, no es necesario ejecutar las tareas manualmente.


```bash
# Correr este comando cada minuto:
$ php artisan schedule:run

# O, correr estos comandos cada hora:
$ php artisan sales:delivered-to-completed
$ php artisan sale-returns:old-to-canceled
$ php artisan payments:pending-to-canceled
$ php artisan sales:shipped-to-delivered
$ php artisan ratings:publish
```


## Backup y restaurado de base de datos

Es necesario eliminar el `definer` de los `triggers` para poder restaurar un backup.
Se crea el backup y de forma inmediata se eliminan esas instrucciones.

```shell
#!/usr/bin/env zsh
docker run -it --rm -a STDOUT -v `pwd`/prilov/:/srv/prilov/ mysql:5.7 bash -c "mysqldump --defaults-extra-file=/srv/prilov/prilov-backup.cnf --set-gtid-purged=OFF --single-transaction=TRUE $DATABASE_BACKUP | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' > /srv/prilov/dump.sql"

#!/usr/bin/env zsh
docker run -it --rm -a STDOUT -v /root/prilov/:/srv/prilov/ mysql:5.7 bash -c "mysql --defaults-extra-file=/srv/prilov/prilov-restore.cnf $DATABASE_RESTORE < /srv/prilov/dump.sql"
```

```ini
# prilov-restore.cnf
[mysql]
user=
password=
host=
```

```ini
# prilov-backup.cnf
[mysqldump]
user=
password=
host=
```
