openssl pkcs12 -in z1031544_nonprod.p12 -out key.pem -nocerts -nodes
openssl pkcs12 -in z1031544_nonprod.p12 -out crt.pem -clcerts -nokeys
