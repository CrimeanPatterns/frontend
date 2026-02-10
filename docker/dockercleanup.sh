#!/bin/sh

echo "Removing untagged images..."
docker images | grep -F '<none>' | awk '{ print "docker rmi -f "$3 }' | sh

echo "Removing unused volumes..."
volumes="$(docker volume ls -qf dangling=true | tr '\n' ' ')"
[ -n "$volumes" ] && docker volume rm $volumes
