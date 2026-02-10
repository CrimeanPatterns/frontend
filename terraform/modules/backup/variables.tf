variable "name" {
  type = string
}

variable "image_id" {
  type = string
}

variable "key_name" {
  type = string
}

variable "security_group_ids" {
  type = set(string)
}

variable "subnet_ids" {
  type = set(string)
}

variable "instance_types" {
  type = set(string)
}

variable "ecs_cluster_name" {
  type = string
}

variable "service_name" {
  type = string
}
