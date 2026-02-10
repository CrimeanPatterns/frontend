#!/bin/bash -x

set -euxo pipefail

TAG=`git rev-parse HEAD`
REGISTRY=718278292471.dkr.ecr.us-east-1.amazonaws.com
CLUSTER_NAME=frontend
CDN_BUCKET_NAME=aw-static

use_beta_host=false
use_beta_database=false
export PYTHONUNBUFFERED=1

if [[ "$beta" == "true" ]]; then
  NULL_REGISTRY=null-frontend-beta.awardwallet.com
  if [[ "$web" == "true" && "$workers" == "true" ]]; then
    echo "could not deploy beta to both workers and web, please select one"
    exit 1
  fi
  if [[ "$web" == "true" ]]; then
    use_beta_host=true
    CDN_BUCKET_NAME=aw-static-beta
  fi
#  if [[ "$workers" == "true" ]]; then
#    use_beta_database=true
#  fi
  SERVICE_NAME_WEB=web-beta-2
  SERVICE_NAME_WORKERS=workers-beta
  BAKE_GROUP=beta
else
  NULL_REGISTRY=null-frontend.awardwallet.com
  SERVICE_NAME_WEB=web-5
  SERVICE_NAME_WORKERS=workers-5
  BAKE_GROUP=all
fi

if [[ "$run_migrations" == "true" ]]; then
  workers=true
fi

echo "CLUSTER=$CLUSTER_NAME" >>BUILD_INFO
echo "SERVICE_NAME_WEB=$SERVICE_NAME_WEB" >>BUILD_INFO
echo "SERVICE_NAME_WORKERS=$SERVICE_NAME_WORKERS" >>BUILD_INFO

# sync configs
rsync app/config/parameters-prod.yml app/config/parameters.yml

# get rabbit host
RABBITMQ_HOST=`
set -eu pipefail;
echo "
import yaml
with open('app/config/parameters.yml', 'r') as stream:
    config = yaml.safe_load(stream)
    print(config['parameters']['env(RABBITMQ_HOST)'])
" | python3
`
echo "rabbit host: $RABBITMQ_HOST";

aws ecr get-login-password | docker login --username AWS --password-stdin 718278292471.dkr.ecr.us-east-1.amazonaws.com
docker pull docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-debug-multiarch
docker pull docker.awardwallet.com/php/frontend-ubuntu20.04-php7.4-prod-multiarch

mkdir -p build/docker

export REPO_AND_PATH=$REGISTRY/frontend
export BETA_HOST=$use_beta_host
export OUTPUT=type=image,push=true
export TAG=$TAG
export DOCKER_BUILDKIT=1
export BUILDKIT_INLINE_CACHE=1
export CDN_BUCKET_NAME=$CDN_BUCKET_NAME

# build base image first, to prevent double build when building fpm and worker
docker buildx bake -f docker-bake.hcl $BAKE_GROUP

# reset image for hotfixes
if [[ "$workers" == "true" && "$web" == "true" && "$beta" == "false" ]]; then
    rm -f build/active_image
fi

# workers
if [[ "$workers" == "true" ]]; then
    cat docker/prod/worker.json \
      | sed -e "s/:TAG/:$TAG/g" \
      | sed -e "s/%RABBITMQ_HOST%/$RABBITMQ_HOST/g" \
      > build/docker/worker.tmp
    WORKERS_REVISION=$(aws ecs register-task-definition --family "frontend-worker" --cli-input-json "file://build/docker/worker.tmp" | jq -r ".taskDefinition.revision")
    echo "WORKERS_REVISION=$WORKERS_REVISION" >>BUILD_INFO
    docker/prod/prepare-ecs-task.py --source-task-family "frontend-worker" --target-task-family "frontend-task"
fi

if [[ "$run_migrations" == "true" ]]; then
  docker/prod/run-ecs-task.py --cluster $CLUSTER_NAME --task-family "frontend-task" --container worker --command 'app/console doctrine:migrations:migrate --no-interaction -vv'
fi

docker/prod/run-ecs-task.py --cluster $CLUSTER_NAME --task-family "frontend-task" --container worker --command 'app/console rabbitmq:setup-fabric -vv'

# web
if [[ "$web" == "true" ]]; then
    # nginx
    if [[ "$deploy" == "true" ]]; then
        # using ip because dns resolving inside amqp proxy is not reliable
        rabbit_ip_address=$(dig +short $RABBITMQ_HOST)
        cat docker/prod/web.json \
          | sed -e "s/:TAG/:$TAG/g" \
          | sed -e "s/%RABBITMQ_HOST%/$rabbit_ip_address/g" \
          > build/docker/web.tmp
        WEB_REVISION=$(aws ecs register-task-definition --family "frontend-web" --cli-input-json "file://build/docker/web.tmp" | jq -r ".taskDefinition.revision")
        echo "WEB_REVISION=$WEB_REVISION" >>BUILD_INFO
        aws ecs update-service --service $SERVICE_NAME_WEB --cluster $CLUSTER_NAME --task-definition frontend-web
        if [[ "$beta" == "false" ]]; then
            echo $REGISTRY/frontend/fpm:$TAG >>build/active_image
        fi
    fi
fi

if [[ "$workers" == "true" && "$deploy" == "true" ]]; then
    aws ecs update-service --service $SERVICE_NAME_WORKERS --cluster $CLUSTER_NAME --task-definition frontend-worker
    if [[ "$beta" == "false" ]]; then
        echo $REGISTRY/frontend/worker:$TAG >>build/active_image
    fi
fi

if [[ "$deploy" == "true" ]]; then
    if [[ "$web" == "true" ]]; then
        docker/prod/wait-ecs-service.py $CLUSTER_NAME $SERVICE_NAME_WEB
    fi
    if [[ "$workers" == "true" ]]; then
        docker/prod/wait-ecs-service.py $CLUSTER_NAME $SERVICE_NAME_WORKERS
    fi
fi

if [[ "$beta" == "false" && "$deploy" == "true" ]]; then
    cat build/active_image | uniq > build/active_image.new
    mv -f build/active_image.new build/active_image
    docker rmi -f $(docker images | grep $REGISTRY/frontend | grep worker | tail -n +3 | awk '{ print $1":"$2; }') || true
    docker rmi -f $(docker images | grep $REGISTRY/frontend | grep fpm | tail -n +3 | awk '{ print $1":"$2; }') || true
    docker rmi -f $(docker images | grep $REGISTRY/frontend | grep nginx | tail -n +3 | awk '{ print $1":"$2; }') || true
    docker rmi -f $(docker images | grep $REGISTRY/frontend | grep fluentbit | tail -n +3 | awk '{ print $1":"$2; }') || true
    docker rmi -f $(docker images | grep $REGISTRY/frontend | grep prod | tail -n +3 | awk '{ print $1":"$2; }') || true
fi

