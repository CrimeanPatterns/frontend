```shell script
cd packer
packer init .
packer build -var-file=environments/prod.hcl images.pkr.hcl
```
Сборка только образа backup:
```shell script
packer build -var-file=environments/awardwallet.hcl -only '*backup*' images.pkr.hcl
```