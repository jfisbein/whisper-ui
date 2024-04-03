#!/usr/bin/env bash

php -S 0.0.0.0:8000 -t /ui &

cd /ui
while true; do
    ./transcribe.sh
    sleep 60
done
