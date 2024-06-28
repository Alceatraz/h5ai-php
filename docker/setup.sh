#!/usr/bin/env sh

echo '[INIT] Initialization ...'

echo '[INIT] Create required folders'

mkdir -p /app/h5ai/_h5ai
mkdir -p /app/logs/php83
mkdir -p /app/logs/nginx
mkdir -p /app/temp/public
mkdir -p /app/temp/private

echo '[INIT] Copy default conf -> /app/conf/'

cp -r /opt/conf/* /app/conf

echo '[INIT] Copy default h5ai -> /app/h5ai/'

cp -r /opt/_h5ai /app/h5ai/

echo '[INIT] Done! Remember use chown to give RW permission to docker volume!'
