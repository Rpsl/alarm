#!/bin/bash

# Этот скрипт необходимо добавить в крон от root пользователя, т.к. он будет опрашивать ваш сервер на пример изменения расписания.
#
# Перемменные:
#
#	USER_MPC - пользователь от которого запускается mpc и которому в крон будут добавленны задания.
#

USER_MPC=pi


#	URL_TO_SITE - адрес вашего сайта на с которого будет запрашиваться расписание.
#

URL_TO_SITE="http://yoursite.com/cron";


# PATH_TO_MPC_SCRIPT - путь к скрипту запуска mpd
#

PATH_TO_MPC_SCRIPT="\/home\/pi\/mpc_start.sh "


PATTERN="%RUN_SCRIPT%"

curl -sS $URL_TO_SITE | sed "s/$PATTERN/$PATH_TO_MPC_SCRIPT/g" > /tmp/mpc_cron && crontab -u $USER_MPC /tmp/mpc_cron && rm -f /tmp/mpc_cron