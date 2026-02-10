#!/bin/sh
set -eov pipefail

[ -d "/ssd" ] || sudo mkdir /ssd

sudo lsblk
sudo swapoff -a

# c3 with two volumes
# sudo umount /media/ephemeral0
#sudo pvcreate -f /dev/sdb
#sudo pvcreate -f /dev/sdc
#sudo vgcreate ssd /dev/sdb
#sudo vgextend ssd /dev/sdc
#sleep 15
#sudo lvcreate -n ssd -l '100%FREE' ssd
#sudo mkfs.ext4 /dev/ssd/ssd
#sudo mount /dev/ssd/ssd /ssd

# c5.2xlarge, one volume
# DEVICE=/dev/nvme1n1
# i3.large, xen, one volume
DEVICE=/dev/nvme0n1

sudo mkfs -t ext4 $DEVICE
sudo mount $DEVICE /ssd

sudo chown ec2-user:ec2-user /ssd
sudo mkdir /ssd/tmp
sudo chmod 777 /ssd/tmp
sudo mount -B /ssd/tmp /tmp

mkdir -p /ssd/clickhouse-csv
chmod 777 /ssd/clickhouse-csv

mkdir -p /ssd/cards-report
chmod 777 /ssd/cards-report

threads=`grep -c ^processor /proc/cpuinfo`
#threads=$( expr 2 '*' "$threads" )
echo "threads: $threads"
echo $threads >/tmp/detected-threads
