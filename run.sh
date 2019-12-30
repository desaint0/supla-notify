#!/bin/bash

CMD1='websocket';
CMD2='notify';

do_stop()
    {
	pid=`pgrep -f ${CMD1}`
        if [ "${pid}" != "" ] ; then
            kill -9 $pid
        fi
	pid=`pgrep -f ${CMD2}`
        if [ "${pid}" != "" ] ; then
            kill -9 $pid
        fi
    }

do_start()
    {
	sudo -u www-data php ./${CMD1}.php ./log/websocket.log
	sudo -u www-data php ./${CMD2}.php ./log/notify.log
    }
do_upgrade()
    {
	rsync -Oavp --delete --include-from=/var/www/html/supla-notify.inc /home/pawel/supla-notify/ .
	chown 33:33 * -R
    }    
case "$1" in
    start)
        do_start
    ;;
    stop)
        do_stop
    ;;
    restart)
        do_stop
        sleep 1
        do_start
    ;;
    update)
	do_stop
	do_upgrade
	do_start
    ;;
    upgrade)
	do_upgrade
    ;;
    install)

    DB='supla2';
    DBPASS=`cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1`;
    SOCKET_PASS=`cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1`;

    echo "Podaj scieżkę do pliku z certyfikatem SSL dla domeny ([enter] domyslnie z apache):"
    read SSL_CERT
    echo "Podaj scieżkę do pliku z kluczem do certyfikatu SSL dla domeny ([enter] domyslnie z apache):"
    read SSL_KEY
    if [ -z ${SSL_CERT} ]; then
	SSL_CERT=/etc/ssl/certs/ssl-cert-snakeoil.pem
    fi
    if [ -z ${SSL_KEY} ]; then
	SSL_KEY=/etc/ssl/private/ssl-cert-snakeoil.key
    fi

    cat ${SSL_CERT} > /etc/ssl/server.pem
    cat ${SSL_KEY} >> /etc/ssl/server.pem
    LOCAL_CERT=/etc/ssl/server.pem

    echo Podaj nazwę domeny pod którą instalujesz ten skrypt: 
    read HOSTNAME    
    echo Podaj nazwę użytkownika dla Mosquito: 
    read MQTT_USER    
    echo Podaj hasło dla Mosquito: 
    read MQTT_PASS    
    echo ""
    echo "Podaj nazwe domeny SUPLA API: ([enter] jesli taka sama jak skrypt)"
    read SUPLA_HOSTNAME    
    echo "Podaj port SUPLA API: ([enter] jesli 443)"
    read SUPLA_PORT    
    echo Podaj OAUTH do API SUPLA:
    read SUPLA_OAUTH    

    if [ -z ${SUPLA_HOSTNAME} ]; then
	SUPLA_HOSTNAME=${HOSTNAME}
    fi
    if [ -z ${SUPLA_PORT} ]; then
	SUPLA_PORT=443
    fi

    DIR=`pwd`;
    CFG=`cat ${DIR}/doc/config.ini`
    echo >${DIR}/config.ini
    while read line; do
	line=${line//%DIR%/${DIR}/}
        line=${line//%DB%/${DB}}
        line=${line//%DBPASS%/${DBPASS}}
	line=${line//%HOSTNAME%/${HOSTNAME}}
	line=${line//%MQTT_USER%/${MQTT_USER}}
	line=${line//%MQTT_PASS%/${MQTT_PASS}}
	line=${line//%LOCAL_CERT%/${LOCAL_CERT}}
	line=${line//%SOCKET_PASS%/${SOCKET_PASS}}
	line=${line//%SUPLA_HOSTNAME%/${SUPLA_HOSTNAME}}
	line=${line//%SUPLA_PORT%/${SUPLA_PORT}}
	line=${line//%SUPLA_OAUTH%/${SUPLA_OAUTH}}
	echo ${line} >> ${DIR}/config.ini
    done <${DIR}/doc/config.ini

    echo ${DBPASS} > /tmp/supladbpass
    sudo -u postgres dropdb ${DB}
    sudo -u postgres dropuser ${DB}
    sudo -u postgres psql -c "CREATE user ${DB} with password '${DBPASS}';"
    sudo -u postgres psql -c "CREATE database ${DB} owner ${DB}" 
    sudo -u postgres psql -c "GRANT all privileges on database ${DB} to ${DB}" 
    
    mkdir ${DIR}/templates_c
    mkdir ${DIR}/log
    mkdir ${DIR}/vendor
    chown 33:33 ${DIR} -R
    ;;
esac
