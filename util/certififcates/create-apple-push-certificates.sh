#!/usr/bin/env bash

set -euxo pipefail

P12_FILE=$1
PEM_FILE=$(echo $P12_FILE | sed s/p12$/pem/)
KEY_FILE=$(echo $P12_FILE | sed s/p12$/key.pem/)
ENCRYPTED_KEY_FILE=$(echo $P12_FILE | sed s/p12$/key.encrypted.pem/)
BUNDLE_FILE=$(echo $P12_FILE | sed s/p12$/bundle.pem/)
S3_PEM_FILE=$(echo $P12_FILE | sed s/.p12$/_pem/ | sed s/-/_/g)
S3_PEM_FILE=$(basename $S3_PEM_FILE)
S3_P12_FILE=$(echo $P12_FILE | sed s/\\./_/g | sed s/-/_/g)
S3_P12_FILE=$(basename $S3_P12_FILE)

openssl pkcs12 -in $P12_FILE -out $PEM_FILE -clcerts -nokeys
openssl pkcs12 -in $P12_FILE -out $KEY_FILE -nocerts -nodes
openssl rsa -aes256 -in $KEY_FILE -out $ENCRYPTED_KEY_FILE
cat $PEM_FILE $ENCRYPTED_KEY_FILE > $BUNDLE_FILE
echo run: aws s3 cp $BUNDLE_FILE s3://aw-frontend-data/$S3_PEM_FILE
echo "this step is not required for ios pushes"
echo run: aws s3 cp $P12_FILE s3://aw-frontend-data/$S3_P12_FILE

