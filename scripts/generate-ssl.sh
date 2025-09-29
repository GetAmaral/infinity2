#!/bin/bash
set -e
mkdir -p nginx/ssl
openssl req -x509 -newkey rsa:2048 -nodes \
    -keyout nginx/ssl/localhost.key \
    -out nginx/ssl/localhost.crt \
    -days 365 -subj "/CN=localhost"
openssl dhparam -out nginx/ssl/dhparam.pem 2048
chmod 600 nginx/ssl/localhost.key
chmod 644 nginx/ssl/localhost.crt nginx/ssl/dhparam.pem