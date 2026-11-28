#!/bin/sh
# Start kontenera cron: instaluje harmonogram i uruchamia crond na pierwszym planie (PID 1).
set -eu

# plik mógł trafić do repozytorium z końcami linii CRLF (Windows), których crond nie rozumie
tr -d '\r' < /app/docker/cron/crontab > /tmp/crontab
crontab /tmp/crontab

# crond uruchamia zadania z pustym środowiskiem, więc zapisujemy zmienne aplikacji (baza, poczta)
# do pliku, który każda linia crontab wczytuje poleceniem ". /etc/cron.env"
env | grep -E '^(DB_|MAIL_|APP_URL=|FORCE_HTTPS=)' \
    | sed -e "s/'/'\\\\''/g" -e "s/^\([^=]*\)=\(.*\)$/export \1='\2'/" > /etc/cron.env
chmod 600 /etc/cron.env

exec crond -f -l 8
