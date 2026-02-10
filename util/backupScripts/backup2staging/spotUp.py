#!/usr/bin/env python3

import argparse
import logging as l
import boto3
import sys
import subprocess
from time import sleep
from time import time

# asg created by terraform "backup" module
asg_name = 'frontend-backup-processors'

ap = argparse.ArgumentParser()
ap.add_argument('--ipfile', help='path to the file to store ip address', required=True)
args = ap.parse_args()

l.basicConfig(format='%(asctime)s %(levelname)s %(message)s', level=l.INFO)
l.info('increasing asg size')

asg = boto3.client('autoscaling')
ec2 = boto3.client('ec2')

asg.update_auto_scaling_group(AutoScalingGroupName=asg_name, DesiredCapacity=1)
start = time()
while True:
    if (time() - start) > 180:
        l.error("failed to wait for instance")
        sys.exit(2)

    response = asg.describe_auto_scaling_groups(AutoScalingGroupNames=[asg_name])
    instances = list(filter(lambda instance: instance['LifecycleState'] == 'InService', response['AutoScalingGroups'][0]['Instances']))
    if len(instances) == 0:
        l.info('waiting for instance')
        sleep(10)
        continue

    instanceId = instances[0]['InstanceId']
    break

l.info('instanceId: ' + instanceId)
l.info('Trying to get IP address')
ip = ec2.describe_instances(InstanceIds=[instanceId])['Reservations'][0]['Instances'][0]['PrivateIpAddress']
l.info('IP Address: ' + ip)

if not ip:
    l.error('Failed to get instance ip address')
    sys.exit(137)

waiting=True
start = time()
while waiting:
    l.info('Waiting for instance become available')
    if (time() - start) > 300:
        l.error("failed to wait for ssh")
        sys.exit(2)
    sleep(10)
    p = subprocess.Popen('ssh -o StrictHostKeyChecking=no ec2-user@%s hostname' % (ip), shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    retval = p.wait()
    if retval == 0:
        waiting=False

l.info('Instance is available')

f = open(args.ipfile, 'w')
f.write(ip)
f.close()

l.info("wait for instance to register with ecs")
sleep(90)

sys.exit(0)

