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
    key = "frontend-terraform-prod"
    region = "us-east-1"
  }

  required_version = ">= 0.14.9"
}

provider "aws" {
  region = "us-east-1"
}

locals {
  rabbit_instance_id = "i-092b064b3f53cd033"
  project_name = "frontend"
  vpc_id = "vpc-01342366"
  ssh_keypair_name = "ansible3"
  nat_b1_subnet_id = "subnet-0b66f36e4b8f3812a"
  ecs_cluster_id = "arn:aws:ecs:us-east-1:718278292471:cluster/frontend"
  ecs_cluster_name = "frontend"
  default_security_group = "sg-d55cb6a8"
}

data "aws_ami" "ecs_based" {
  most_recent = true
  filter {
    name = "name"
    values = ["ecs-based-*"]
  }
  owners = ["self"]
}

data aws_ssm_parameter slack-url {
  name   = "/config/slack-alarm-url"
  with_decryption = true
}

# should move to email ?
resource "aws_ecs_cluster_capacity_providers" "main" {
  cluster_name = local.ecs_cluster_name
  capacity_providers = [module.web-5.capacity_provider_name, module.workers-5.capacity_provider_name, module.backup.capacity_provide_name]
}

module "blog" {
  source = "../../modules/blog"
  suffix = "prod"
  custom-headers = []
  source-domain = "awardwallet.com"
}

module "builder" {
  source = "../../modules/builder"
}

module "backup" {
  source = "../../modules/backup"
  name = "frontend"
  image_id = data.aws_ami.ecs_based.image_id
  #instance_types = ["c6id.8xlarge", "c6id.4xlarge", "c6id.2xlarge", "c5d.4xlarge", "c3.4xlarge"]
  instance_types = ["i3.xlarge"] # need at least 300gb. c6id.2xlarge, c5ad.2xlarge, i3.large (2 vcpu, slow)
  key_name = local.ssh_keypair_name
  security_group_ids = [local.default_security_group, "sg-0e2c000bc7995c769"]
  subnet_ids = [local.nat_b1_subnet_id] # main-b
  ecs_cluster_name = "frontend"
  service_name = "backup"
}

module "resources" {
  source = "../../modules/resources"
  segment_attachments_bucket_name = "aw-prod-segment-attachments"
}

module "notify_slack" {
  source  = "terraform-aws-modules/notify-slack/aws"
  version = "~> 4.0"

  sns_topic_name = "frontend-slack"
  slack_webhook_url = data.aws_ssm_parameter.slack-url.value
  slack_channel     = "aw_jenkins"
  slack_username    = "CloudWatch"
  lambda_function_name = "frontend_notify_slack"

  tags = {
    Name = "frontend-cloudwatch-alerts-to-slack"
  }
}

module "cpu-monitoring" {
  source = "../../modules/instance-monitoring"
  alarm_action = module.notify_slack.slack_topic_arn
  instances = {
    rabbit = {
      id : local.rabbit_instance_id
      threshold : 40
    }
  }
  project_name = local.project_name
}

module "cpu-credits-monitoring" {
  source = "../../modules/instance-monitoring"
  alarm_action = module.notify_slack.slack_topic_arn
  instances = {
    rabbit = {
      id : local.rabbit_instance_id
      threshold : 100
    }
  }
  project_name = local.project_name
  compare_operation = "LessThanOrEqualToThreshold"
  metric_name = "CPUCreditBalance"
}

module "monitoring" {
  source = "../../modules/monitoring"
  slack_topic_arn = module.notify_slack.slack_topic_arn
}

module "web-5" {
  source = "../../modules/ecs-service"
  vpc_id = local.vpc_id
  security_group_ids = [local.default_security_group, "sg-1a10e568"] # default, allow all from elb
  key-pair-name = local.ssh_keypair_name
  subnet_ids = [local.nat_b1_subnet_id]
  ami_id = data.aws_ami.ecs_based.id
  ecs_cluster_id = local.ecs_cluster_id
  ecs_cluster_name = local.ecs_cluster_name
  snapshot_id = one(data.aws_ami.ecs_based.block_device_mappings).ebs.snapshot_id
  empty_task_definition = "frontend-web:2622"
  asg_name = "frontend-web-5"
  balancers = [
    {
      container_name: "nginx",
      container_port: 80,
      target_group_arn: "arn:aws:elasticloadbalancing:us-east-1:718278292471:targetgroup/frontend/2f51233f381adff6"
    },
    {
      container_name: "nginx",
      container_port: 80,
      target_group_arn: "arn:aws:elasticloadbalancing:us-east-1:718278292471:targetgroup/frontend-sub-domains/a178dfb994a49913"
    }
  ]
  service_registries = []
  iam_role_name = "frontend-instance"
  instance_types = ["t3a.medium"]
  on_demand_base_capacity = 2
  service_name = "web-5"
  min_healthy_percentage = 50
  min_instances = 0
  desired_capacity = 2
  instance_tags = {
    "frontend-web" = ""
  }
  container_stop_timeout = "30s"
  ordered_placement_strategies = [
    {
      field = "instanceId"
      type = "spread"
    }
  ]
  placement_constraints = []
}

module "workers-5" {
  source = "../../modules/ecs-service"
  vpc_id = local.vpc_id
  security_group_ids = [local.default_security_group]
  key-pair-name = local.ssh_keypair_name
  subnet_ids = [local.nat_b1_subnet_id]
  ami_id = data.aws_ami.ecs_based.id
  ecs_cluster_id = local.ecs_cluster_id
  ecs_cluster_name = local.ecs_cluster_name
  snapshot_id = one(data.aws_ami.ecs_based.block_device_mappings).ebs.snapshot_id
  empty_task_definition = "frontend-worker:2526"
  asg_name = "frontend-workers-5"
  balancers = []
  service_registries = []
  iam_role_name = "frontend-instance"
  instance_types = ["t3a.medium"]
  on_demand_base_capacity = 4
  service_name = "workers-5"
  min_healthy_percentage = 50
  min_instances = 2
  desired_capacity = 2
  instance_tags = {
    "frontend-worker" = ""
  }
  ordered_placement_strategies = []
  placement_constraints = ["distinctInstance"]
}

module "cloudfront" {
  source = "../../modules/cloudfront"
}

data "aws_route53_zone" "infra" {
  name = "infra.awardwallet.com."
  private_zone = true
}

resource "aws_route53_record" "camoufox-browser" {
  zone_id = data.aws_route53_zone.infra.id
  name    = "camoufox-browser.infra.awardwallet.com"
  type    = "A"
  ttl     = "60"
  records = ["192.168.4.80"] # actions-runner-x86
}

