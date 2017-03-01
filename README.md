# pingFail
php service for monitoring sites status

1. git clone
2. composer install
3. bin/run.php service
exp: 

/var/www/pingFail/bin/run.php service

or use supervisor

create /etc/supervisor/conf.d/ping_fail.conf

config:
[program:long_script]
command=/var/www/pingFail/bin/run.php service
autostart=true
autorestart=true
stderr_logfile=/var/www/pingFail/logs/error.log
stdout_logfile=/var/www/pingFail/logs/out.log
