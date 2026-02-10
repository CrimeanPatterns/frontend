packer {
  required_plugins {
    amazon = {
      version = ">= 1.1.1"
      source = "github.com/hashicorp/amazon"
    }
  }
}

variable "ansible_dir" {
  type = string
  default = "~/ansible"
}

variable "aws_profile" {
  type = string
  default = "default"
}

variable "vpc_name" {
  type = string
  default = "Main"
}

variable "subnet_name" {
  type = string
  default = "Main-B"
}

source "amazon-ebs" "amazon_linux_2_x86" {
  instance_type = "t3.small"
  encrypt_boot = true
  profile = var.aws_profile
  source_ami_filter {
    filters = {
      name = "amzn2-ami-hvm-2.0*x86_64-ebs"
      root-device-type = "ebs"
      virtualization-type = "hvm"
    }
    owners = ["137112412989"] // amazon
    most_recent = true
  }
  vpc_filter {
    filters = {
      "tag:Name": var.vpc_name
    }
  }
  subnet_filter {
    filters = {
      "tag:Name": var.subnet_name
    }
  }
  ssh_username = "ec2-user"
}

build {
  name = "backup-worker"
  source "source.amazon-ebs.amazon_linux_2_x86" {
    ami_name = "frontend-backup-worker"
  }
  provisioner "ansible" {
    playbook_file = pathexpand("${var.ansible_dir}/playbooks/configure-base.yml")
    command = pathexpand("${var.ansible_dir}/bin/ansible-playbook-wrapper.sh")
    extra_arguments = ["-e", "{change_hostname: false}"]
  }
}
