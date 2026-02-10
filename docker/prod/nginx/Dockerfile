# syntax=docker.io/docker/dockerfile:1.4@sha256:9ba7531bd80fb0a858632727cf7a112fbfd19b17e94c4e84ced81e24ef1a0dbc
FROM nginx:1.25.1@sha256:67f9a4f10d147a6e04629340e6493c9703300ca23a2f7f3aa56fe615d75d31ca AS tools
RUN \
    set -eux pipefail pipefail; \
    apt-get update ; \
    apt-get install -y --no-install-recommends rsync ; \
    which rsync

FROM nginx:1.25.1@sha256:67f9a4f10d147a6e04629340e6493c9703300ca23a2f7f3aa56fe615d75d31ca

COPY default.conf /etc/nginx/conf.d/default.conf
COPY default.conf /etc/nginx/conf.d/live.conf.template
COPY maintenance.conf /etc/nginx/conf.d/maintenance.conf.template
COPY auth.conf fastcgi-symfony.conf force-host-and-proto.conf limiter.conf /etc/nginx/
COPY maintenance_on.sh maintenance_off.sh /root/

WORKDIR /www/awardwallet/web/
# copy rarely modified vendors as a layer, for cache
COPY --from=fpm /www/awardwallet/web/assets/common/vendors/ ./assets/common/vendors/
# sync new files over it
RUN \
    --mount=type=bind,from=fpm,source=/www/awardwallet/web,target=/fpm-web \
    --mount=type=bind,from=tools,source=/usr,target=/usr \
    set -eux pipefail pipefail; \
    rsync --recursive  --exclude '*.php' --exclude '.git' --verbose /fpm-web/ ./
