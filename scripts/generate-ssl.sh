#!/bin/bash
set -e
mkdir -p nginx/ssl

# Create OpenSSL configuration file with SAN for wildcard subdomain support
cat > nginx/ssl/openssl.cnf <<EOF
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
req_extensions = v3_req

[dn]
CN = localhost

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = localhost
DNS.2 = *.localhost
EOF

# Generate self-signed certificate with wildcard subdomain support
openssl req -x509 -newkey rsa:2048 -nodes \
    -keyout nginx/ssl/localhost.key \
    -out nginx/ssl/localhost.crt \
    -days 365 \
    -config nginx/ssl/openssl.cnf \
    -extensions v3_req

# Generate DH parameters
openssl dhparam -out nginx/ssl/dhparam.pem 2048

# Set permissions
chmod 600 nginx/ssl/localhost.key
chmod 644 nginx/ssl/localhost.crt nginx/ssl/dhparam.pem

# Clean up config file
rm -f nginx/ssl/openssl.cnf

echo "SSL certificates generated with wildcard subdomain support (*.localhost)"