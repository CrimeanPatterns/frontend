#!/usr/bin/env python
# -*- coding: utf-8 -*-
# by Evgeniy Shumilov <eshumilov@awardwallet.com>

import argparse
import logging as l
import boto3
import datetime
from sys import exit

# Lifetime limit for backup-processor instances in seconds
limit = 43200
tag = 'temporary'


l.basicConfig(format='%(asctime)s %(levelname)s %(message)s', level=l.INFO)
l.info('Start working')

client = boto3.client('ec2')

l.info('Trying to get reservations for tag ' + tag)

reservations = client.describe_instances(Filters=[{'Name': 'tag:'+tag, 'Values': ['']}]).get('Reservations')

if not reservations:
    l.info("Can't found any temporary instances")
    exit(0)

iid = ''

dt = datetime.datetime.now()

inst = []

for r in reservations:
    Instances = r.get('Instances')
    for i in Instances:
        if i['State']['Name'] == 'running':
            for t in i.get('Tags'):
                if t['Key'] == 'Name' and t['Value'].startswith('backup-processor-'):
                    iid = i.get('InstanceId')
                    l.info('Instance ' + iid + ' was found, checking launch time')
                    inst.append(i)
                    diff = int(datetime.datetime.now().strftime('%s'))-int(i['LaunchTime'].strftime('%s'))
                    if diff > limit:
                        l.info('Instance ' + iid + ' is working too long, terminating')
                        ec2 = boto3.resource('ec2')
                        ec2.instances.filter(InstanceIds=[iid]).terminate()
                        exit(127)
                    else:
                        l.info('Instance ' + iid + ' was checked for lifetime')
exit(0)
