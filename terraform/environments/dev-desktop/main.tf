terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
      version = "~> 3.0"
    }
  }

  backend "s3" {
    profile = "default"
    bucket = "aw-configs"
    key = "frontend-terraform-dev-desktop"
    region = "us-east-1"
  }

  required_version = ">= 0.14.9"
}

provider "aws" {
  region = "us-east-1"
}

data "aws_ssm_parameter" "cloudfront-auth" {
  name = "/frontend/dev-desktop/cloudfront-auth"
  with_decryption = true
}

module "blog" {
  source = "../../modules/blog"
  suffix = "dev-desktop"
  custom-headers = [
    {
      name  = "Authorization"
      value = "Basic ${data.aws_ssm_parameter.cloudfront-auth.value}"
    }
  ]
  source-domain = "dev-desktop.awardwallet.com"
}

