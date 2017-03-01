# pingFail
php service for monitoring sites status

Наблосал для себя небольшой сервис на php который мониторит состояние сайтов.
Пока отправляет только почту, где пишет когда упал и когда поднялся.
В будущем хочу сделать уведомление по viber.

* git clone
* composer install
* bin/run.php service
* cp config/config.json.example config/config.json
* configure config.json
 
run:
```/var/www/pingFail/bin/run.php service ```

the best use supervisor

```apt-get install supervisor```

``` joe /etc/supervisor/conf.d/ping_fail.conf ```

insert config: (replace path)
```
[program:long_script]
command=/var/www/pingFail/bin/run.php service
autostart=true
autorestart=true
stderr_logfile=/var/www/pingFail/logs/error.log
stdout_logfile=/var/www/pingFail/logs/out.log
```
```service supervisor restart```

``` sudo supervisorctl ```
``` restart all ```
``` quit ```


