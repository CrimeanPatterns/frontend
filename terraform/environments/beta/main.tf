terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  backend "s3" {
    profile = "default"
    bucket = "aw-configs"
    key = "frontend-terraform-beta"
    region = "us-east-1"
  }

  required_version = ">= 0.14.9"
}

provider "aws" {
  region = "us-east-1"
}

resource "aws_ecs_service" "web" {
  name = "web-beta-2"
  cluster = "arn:aws:ecs:us-east-1:718278292471:cluster/frontend"
  scheduling_strategy = "REPLICA"
  desired_count = 1
  task_definition = "frontend-web:2351"
  deployment_minimum_healthy_percent = 0
  deployment_maximum_percent = 100
  load_balancer {
    container_name = "nginx"
    container_port = 80
    target_group_arn = "arn:aws:elasticloadbalancing:us-east-1:718278292471:targetgroup/frontend-beta-2/113eb532b9d36ac2"
  }
  lifecycle {
    ignore_changes = [task_definition]
  }
  capacity_provider_strategy {
    capacity_provider = "frontend-web-5"
    weight = 100
  }
}
