# syntax=docker.io/docker/dockerfile:1.4@sha256:9ba7531bd80fb0a858632727cf7a112fbfd19b17e94c4e84ced81e24ef1a0dbc

ARG beta_host
ARG beta_database=false
ARG symfony_env

FROM docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-debug-multiarch AS debug-base
WORKDIR /www/awardwallet
RUN set -eux; \
    mkdir ~/.ssh; \
    ssh-keyscan github.com >> ~/.ssh/known_hosts ; \
    echo $PATH > /root/path.txt

FROM docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-prod-multiarch AS prod-base
WORKDIR /www/awardwallet
COPY --from=debug-base /root/.ssh/known_hosts /root/.ssh/known_hosts

FROM debug-base AS vendors
COPY --from=src composer.json composer.lock ./
# jpgraph examples removed because there is symlink which break autoloader generation
RUN --mount=type=ssh,required=true \
    --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json \
    --mount=type=cache,target=/root/.cache/composer \
    set -eux; \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-progress --no-dev --no-scripts --no-autoloader --prefer-dist ; \
    rm -Rf vendor/jpgraph/jpgraph/lib/jpgraph/src/Examples
RUN \
    --mount=type=bind,from=src,source=docker/prod/apply-vendor-patches.sh,target=docker/prod/apply-vendor-patches.sh \
    --mount=type=bind,from=src,source=docker/prod/patches,target=docker/prod/patches \
    set -eux; \
    docker/prod/apply-vendor-patches.sh

FROM docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-debug-multiarch AS npm
WORKDIR /www/awardwallet
COPY --from=src package.json yarn.lock webpack-extension-client.config.js ./
RUN \
    --mount=type=ssh \
    --mount=type=secret,id=npmrc,target=/root/.npmrc \
    --mount=type=cache,id=yarn-local-cache-v6,target=/usr/local/share/.cache/yarn/v6 \
    --mount=type=cache,id=yarn-root-cache-v6,target=/root/.yarn/cache \
    set -eux; \
    md5sum /root/.npmrc ; \
    SYMFONY_ENV=prod YARN_CACHE=/root/.yarn/cache yarn install

FROM docker.awardwallet.com/service/allsites-data as allSites

FROM scratch AS src
COPY --link . .

FROM prod-base AS sources
COPY --from=vendors /www/awardwallet/ .
# node-modules are not needed in prod
COPY --from=npm /www/awardwallet/web/ web/
COPY --from=allSites /www/allSites/data ./data/
COPY --from=src . .
RUN ls -l docker/php

FROM sources AS cache
ARG beta_host
ARG beta_database=false
ARG symfony_env
ENV SYMFONY_ENV=$symfony_env
RUN set -eux; \
  ln -s /www/awardwallet/docker/30-awardwallet-php.ini /etc/php/$PHP_VERSION/cli/conf.d/30-awardwallet-php.ini; \
  ln -s /www/awardwallet/docker/30-awardwallet-php.ini /etc/php/$PHP_VERSION/fpm/conf.d/30-awardwallet-php.ini; \
  ln -s /www/awardwallet/docker/prod/php/40-php-fpm.ini /etc/php/$PHP_VERSION/fpm/conf.d/40-php-fpm.ini
RUN \
    --mount=type=bind,from=docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-debug-multiarch,source=/usr/bin/composer,target=/usr/bin/composer \
    set -eux pipefail pipefail; \
    du -hs . ; \
    composer dumpautoload --no-dev --optimize --apcu; \
    if [ "$beta_host" = "true" ]; then \
        docker/updateYml.php app/config/parameters.yml parameters/host 'beta3.awardwallet.com'; \
        docker/updateYml.php app/config/parameters.yml parameters/business_host 'business-beta3.awardwallet.com'; \
        docker/updateYml.php app/config/parameters.yml parameters/cdn_host "//d2pipr1hsg5fwg.cloudfront.net"; \
    fi; \
    if [ "$beta_database" = "true" ]; then \
        docker/updateYml.php app/config/parameters.yml parameters/database_host 'frontend-beta.c4i704xbihsy.us-east-1.rds.amazonaws.com'; \
        docker/updateYml.php app/config/parameters.yml parameters/read_replica_database_host 'frontend-beta.c4i704xbihsy.us-east-1.rds.amazonaws.com'; \
    fi; \
    assets_version=`date +"%s"` ; \
    docker/updateYml.php app/config/parameters.yml parameters/assets_version $assets_version; \
    mkdir -p app/logs/checklogs; \
    mkdir -p app/cache; \
    composer run-script --no-dev build-prod; \
    S3_FAKE=1 SSM_FAKE=1 app/console fos:js-routing:dump -vv --target=web/js/routes.json --format=json; \
    S3_FAKE=1 SSM_FAKE=1 app/console fos:js-routing:dump -vv --target=web/js/routes.js; \
    S3_FAKE=1 SSM_FAKE=1 app/console bazinga:js-translation:dump --merge-domains --format=js web/assets -vv ; \
    S3_FAKE=1 SSM_FAKE=1 app/console cache:warmup -vv; \
    S3_FAKE=1 SSM_FAKE=1 app/console aw:save-page /maintenance --expected-http-code=503 >web/maintenance.html

FROM cache as webpack
RUN \
    --mount=type=cache,target=/root/.yarn/.cache/webpack,id=webpack_cache_v7_${beta_host} \
    --mount=type=cache,target=/root/node_modules_cache,id=node_modules_cache_v7_${beta_host} \
    --mount=type=bind,from=npm,source=/www/awardwallet/node_modules,target=/root/node_modules,rw \
    --mount=type=bind,from=debug-base,source=/usr,target=/usr \
    --mount=type=bind,from=debug-base,source=/root/path.txt,target=/root/path.txt \
    --mount=type=ssh,required=true \
    --mount=type=cache,target=/root/yarn-cache,id=yarn_cache_v5_${beta_host} \
    set -eux pipefail; \
    PATH=$(cat /root/path.txt) ; \
    CI=true ; \
    cdn_host=`docker/getYml.php app/config/parameters.yml parameters/cdn_host` ; \
    assets_version=`docker/getYml.php app/config/parameters.yml parameters/assets_version` ; \
    ln -s /root/node_modules_cache /root/node_modules/.cache  ; \
    ln -s /root/node_modules /www/awardwallet/node_modules  ; \
    YARN_CACHE_FOLDER=/root/yarn-cache yarn run grunt --gruntfile desktopGrunt.js ; \
    CDN_HOST=${cdn_host} CDN_PATH=/p YARN_CACHE_FOLDER=/root/yarn-cache yarn run build ; \
    grunt --no-color build:mobile:templates; \
    grunt --no-color --cdn_host=$cdn_host build:mobile:release:git ; \
    find web -name "*.less" -delete ; \
    find web -name "*.scss" -delete ; \
    find web -name "*.ts" -delete

FROM prod AS grunt
RUN \
    --mount=type=cache,target=/root/node_modules_cache,id=node_modules_cache_v7_${beta_host} \
    --mount=type=bind,from=npm,source=/www/awardwallet/node_modules,target=/root/node_modules,rw \
    --mount=type=bind,from=debug-base,source=/usr,target=/usr \
    --mount=type=bind,from=debug-base,source=/root/path.txt,target=/root/path.txt \
    set -eux pipefail; \
    PATH=$(cat /root/path.txt) ; \
    CI=true ; \
    ln -s /root/node_modules_cache /root/node_modules/.cache  ; \
    ln -s /root/node_modules /www/awardwallet/node_modules  ; \
    cdn_host=`docker/getYml.php app/config/parameters.yml parameters/cdn_host` ; \
    assets_version=`docker/getYml.php app/config/parameters.yml parameters/assets_version` ; \
    grunt --no-color --cdn_host=$cdn_host --assets_version=$assets_version --gruntfile desktopGrunt.js

FROM webpack AS prod
ENTRYPOINT ["/www/awardwallet/docker/prod/entrypoint.php"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
ENV SYNC_ENGINE 1
ENV SSM_WARMUP 1
RUN set -eux pipefail ; \
    mkdir web/images/uploaded ; \
    chown -R www-data:www-data app/logs app/cache web/images/uploaded

#FROM $prod_base_image AS requirejs-assets
#RUN \
#    --mount=type=bind,from=npm,source=/www/awardwallet/node_modules,target=/root/node_modules,rw \
#    --mount=type=bind,from=debug-base,source=/usr,target=/usr \
#    set -eux pipefail ; \
#    cdn_host=`docker/getYml.php app/config/parameters.yml parameters/cdn_host` ; \
#    assets_version=`docker/getYml.php app/config/parameters.yml parameters/assets_version` ; \
#    grunt --no-color --cdn_host=$cdn_host --assets_version=$assets_version --gruntfile desktopGrunt.js


FROM prod AS fpm
RUN set -eux pipefail ; \
  ln -s /www/awardwallet/docker/prod/fpm-supervisor.conf /etc/supervisor/conf.d/frontend-web.conf

FROM prod AS worker
RUN set -eux pipefail ; \
    ln -s /www/awardwallet/docker/prod/worker-supervisor.conf /etc/supervisor/conf.d/frontend-worker.conf

FROM prod AS builder
RUN set -eux pipefail ; \
    cp /www/awardwallet/docker/prod/builder/www.conf /etc/php/7.4/fpm/pool.d/
COPY --from=web-admin . web/admin/
COPY --from=web-lib-admin . web/lib/admin/
#RUN \
#  DEBIAN_FRONTEND=noninteractive apt-get upszdate && \
#	DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
#	  rsync \
#	  git \
#	  ssh \
#	  sudo
ENV HOME /var/lib/jenkins
RUN \
  set -eux pipefail ; \
  apt-get update && apt-get install -y --no-install-recommends git ssh ; \
  useradd --shell /bin/bash --home-dir /var/lib/jenkins -u 113 -o -c "" jenkins && \
  echo '%sudo	ALL=NOPASSWD:ALL' >> /etc/sudoers && \
  chown -R jenkins:jenkins app/logs app/cache /run/php
USER jenkins
ENTRYPOINT []
CMD ["/usr/sbin/php-fpm7.4", "-F"]

FROM docker.awardwallet.com/awscli as assets-upload
ARG cdn_bucket_name
RUN \
    --mount=type=bind,from=grunt,source=/www/awardwallet/web,target=/www/awardwallet/web \
    set -eux pipefail; \
    cd /www/awardwallet; \
    aws s3 sync web/a s3://$cdn_bucket_name/a \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"; \
    aws s3 sync web/assets s3://$cdn_bucket_name/assets \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"; \
    aws s3 sync web/m s3://$cdn_bucket_name/m \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"; \
    aws s3 sync web/b/$(ls web/b) s3://$cdn_bucket_name/b/$(ls web/b) \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"; \
    aws s3 sync web/images/email/newdesign s3://$cdn_bucket_name/images/email/newdesign \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"; \
    aws s3 sync web/bundles/fmelfinder s3://$cdn_bucket_name/bundles/fmelfinder \
    --exclude "*" \
    --include "*.js" --include "*.css" --include "*.png" --include "*.jpeg" --include "*.jpg" --include "*.gif" \
    --include "*.woff" --include "*.ttf" --include "*.ttc" --include "*.otf" --include "*.eot" --include "*.woff" \
    --include "*.woff2" --include "*.json" --include "*.svg" \
    --exclude "admin/*" --exclude "engine*" \
    --cache-control "max-age=604800"