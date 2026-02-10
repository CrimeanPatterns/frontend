variable "service_name" {
  type = string
}

variable "iam_role_name" {
  type = string
}

variable "ami_id" {
  type = string
}
variable "security_group_ids" {
  type = list(string)
}
variable "subnet_ids" {
  type = list(string)
}

variable "vpc_id" {
  type = string
}

variable "ecs_cluster_id" {
  type = string
}

variable "ecs_cluster_name" {
  type = string
}

variable "instance_types" {
  type = list(string)
}

variable "on_demand_base_capacity" {
  type = number
}

variable "instance_tags" {
  type = map(string)
  default = {}
}

variable "balancers" {
  type = list(object({container_name: string, container_port: number, target_group_arn: string}))
}

variable "ordered_placement_strategies" {
  type = list(object({field: string, type: string}))
}

variable "placement_constraints" {
  type = list(string)
}

variable "service_registries" {
  type = list(object({registry_arn: string}))
}

variable "key-pair-name" {
  type = string
}

variable "min_instances" {
  type = number
  default = 2
}

variable "snapshot_id" {
  type = string
}

variable "min_healthy_percentage" {
  type = number
  default = 50
}

variable "desired_capacity" {
    type = number
}

variable "asg_name" {
  type = string
}

variable "empty_task_definition" {
  type = string
}

variable "container_stop_timeout" {
  type = string
  default = "6m"
}