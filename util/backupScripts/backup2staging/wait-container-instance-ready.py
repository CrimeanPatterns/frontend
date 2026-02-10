#!/usr/bin/env python3

from boto3 import client
import logging as l
import argparse
import time

l.basicConfig(format='%(message)s', level=l.INFO)

def parse_args():
    ap = argparse.ArgumentParser()
    ap.add_argument('--service', help='filter instances by this service (attribute service:<some>)', required=True)
    args = ap.parse_args()
    return args

args = parse_args()
ecs = client('ecs')

ready = False
start_time = time.time()
while not ready and (time.time() - start_time) < 300:
    instances = ecs.list_container_instances(cluster="frontend", filter="attribute:service == {0}".format(args.service))['containerInstanceArns']
    ready = len(instances) > 0
    if not ready:
        print("waiting for container instances of service {0}".format(args.service))
        time.sleep(5)

print("container instance is ready")