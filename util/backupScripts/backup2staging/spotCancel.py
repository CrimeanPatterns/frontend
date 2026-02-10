#!/usr/bin/env python3

import sys
import logging as l
import boto3
import time

def wait_for_zero_size(group_name, desired_size, poll_interval=30):

    while True:
        response = asg.describe_auto_scaling_groups(AutoScalingGroupNames=[group_name])
        groups = response['AutoScalingGroups']

        if not groups:
            l.error("Auto Scaling group '{}' not found.".format(group_name))
            sys.exit(1)

        size = len(list(filter(lambda instance: instance['LifecycleState'] == 'InService', groups[0]['Instances'])))

        if size == desired_size:
            print("Auto Scaling group '{}' has reached desired size.".format(group_name))
            return

        print("Waiting for Auto Scaling group '{}' to reach desired size. Current size: {}".format(group_name, size))
        time.sleep(poll_interval)


l.basicConfig(format='%(asctime)s %(levelname)s %(message)s', level=l.INFO)
l.info('stopping spot')

asg = boto3.client('autoscaling')
group_name = 'frontend-backup-processors'

# asg created by terraform "backup" module
asg.update_auto_scaling_group(AutoScalingGroupName=group_name, DesiredCapacity=0)
wait_for_zero_size(group_name, 0)

l.info("spot stopped")
