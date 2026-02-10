variable "TAG" {
  default = "latest"
}
variable "REPO_AND_PATH" {
  default = "null.awardwallet.com/frontend"
}

variable "SYMFONY_ENV" {
  default = "prod"
}

variable "OUTPUT" {
  default = "type=image"
}

variable "CDN_BUCKET_NAME" {
  default = "aw-static-beta"
}

variable "HOME" {
  default = "$HOME"
}

variable "BETA_HOST" {
  default = "false"
}

group "all" {
  targets = ["fluentbit", "nginx", "fpm", "worker", "builder", "assets-upload"]
}

group "beta" {
  targets = ["fluentbit", "nginx", "fpm", "assets-upload"]
}

target "fluentbit" {
  context = "docker/prod/fluentbit"
  tags = ["${REPO_AND_PATH}/fluentbit:${TAG}", "${REPO_AND_PATH}/fluentbit"]
  output = ["${OUTPUT}"]
}

target "nginx" {
  context = "docker/prod/nginx"
  contexts = {
    fpm = "target:fpm"
  }
  tags = ["${REPO_AND_PATH}/nginx:${TAG}"]
  output = ["${OUTPUT}"]
}

target "src" {
  ssh = ["default"]
  context = "."
  contexts = {
    build = "build"
  }
  target = "src"
  dockerfile = "docker/php/Dockerfile"
}

target "prod" {
  ssh = ["default"]
  context = "docker/php"
  contexts = {
    src = "target:src"
  }
  target = "prod"
  args = {
    symfony_env = "${SYMFONY_ENV}"
    beta_host = "${BETA_HOST}"
    prod_base_image = "${REPO_AND_PATH}/prod:${TAG}"
  }
  secret = [
    "type=file,id=npmrc,src=${HOME}/.npmrc",
    "type=file,id=composer_auth,src=${HOME}/.composer/auth.json"
  ]
  tags = ["${REPO_AND_PATH}/prod:${TAG}"]
  output = ["type=image"]
}

target "fpm" {
  context = "docker/php"
  contexts = {
    prod = "target:prod"
  }
  target = "fpm"
  tags = ["${REPO_AND_PATH}/fpm:${TAG}"]
  output = ["${OUTPUT}"]
}

target "worker" {
  context = "docker/php"
  contexts = {
    prod = "target:prod"
  }
  secret = [
    "type=file,id=npmrc,src=${HOME}/.npmrc",
    "type=file,id=composer_auth,src=${HOME}/.composer/auth.json"
  ]
  target = "worker"
  tags = ["${REPO_AND_PATH}/worker:${TAG}"]
  output = ["${OUTPUT}"]
}

target "builder" {
  context = "docker/php"
  contexts = {
    prod = "target:prod"
    web-admin = "web/admin"
    web-lib-admin = "web/lib/admin"
  }
  target = "builder"
  tags = ["${REPO_AND_PATH}/fpm:builder"]
  output = ["${OUTPUT}"]
}

target "assets-upload" {
  context = "docker/php"
  target = "assets-upload"
  contexts = {
    src = "target:src"
    prod = "target:prod"
  }
  secret = [
    "type=file,id=npmrc,src=${HOME}/.npmrc",
    "type=file,id=composer_auth,src=${HOME}/.composer/auth.json"
  ]
  args = {
    cdn_bucket_name = "${CDN_BUCKET_NAME}"
  }
  output = ["type=cacheonly"]
}

