#!/usr/bin/env python3

import boto3
import logging as l
import requests
from array import array
import re
import argparse

l.basicConfig(format='%(message)s', level=l.INFO)

BUCKET_NAME = "aw-static"

def parse_args():
    ap = argparse.ArgumentParser()
    ap.add_argument('--prefix', required=True)
    ap.add_argument('--keep-last', required=True)
    ap.add_argument('--url')
    args = ap.parse_args()
    return args

def get_current_version(url : str) -> int:
    l.info("loading active version from {0}".format(url))
    response = requests.get(url)
    match = re.search(r"/(\d+)/boot\.js", response.text)
    if not match:
        print(response.text)
        raise ValueError("Could not find current version on {0}".format(url))
    result = match.group(1)
    l.info("current version: {0}".format(result))
    if len(result) < 5:
        raise ValueError("Invalid version found: {0}".format(result))
    return int(result)

def prepare_delete_list(folders : array, keep_last: int, url : str):
    l.info("keeping only last {0}".format(keep_last))
    folders.sort()
    result = folders
    if keep_last > 0:
        result = folders[0:-keep_last]

    if not url is None:
        current_version = get_current_version(url)
        if not current_version in folders:
            raise ValueError("current version is not found on s3")
        if current_version in result:
            l.info("do not delete current version")
            result.remove(current_version)
    
    l.info("deleting:")
    for folder in result:
        l.info(folder)
    return result

def delete_folders(prefix : str, folders : array):
    for folder in folders:
        key = prefix + "/" + str(folder) + "/"
        l.info("deleting {0}".format(key))
        bucket.objects.filter(Prefix=key).delete()

def clean_folder(prefix : str, keep_last : int, url : str):
    l.info("existing folders in {0}:".format(prefix))
    folders = []
    for subfolder in client.list_objects(Bucket=BUCKET_NAME, Prefix=prefix + "/", Delimiter = "/").get("CommonPrefixes"):
        folder_name = subfolder.get('Prefix')[len(prefix) + 1:-1]
        l.info(folder_name)
        folders.append(int(folder_name))

    to_delete = prepare_delete_list(folders, keep_last, url)   
    delete_folders(prefix, to_delete)

args = parse_args()
client = boto3.client('s3')
s3 = boto3.resource('s3')
bucket = s3.Bucket(BUCKET_NAME)
clean_folder(args.prefix, int(args.keep_last), args.url)
#clean_folder("p/b", 1, "https://awardwallet.com/")

l.info("done")
