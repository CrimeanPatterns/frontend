data "aws_caller_identity" "current" {}

resource "aws_launch_template" "main" {
  name = "${var.ecs_cluster_name}-${var.service_name}"
  update_default_version = true
  image_id = var.ami_id
  key_name = var.key-pair-name
  tag_specifications {
    resource_type = "instance"
    tags = merge({Name = "${var.ecs_cluster_name}-${var.service_name}", Test = "test1"}, var.instance_tags)
  }
  iam_instance_profile {
    name = var.iam_role_name
  }
  ebs_optimized = true
  metadata_options {
    http_endpoint = "enabled"
    http_tokens = "optional"
    http_put_response_hop_limit = 2
    instance_metadata_tags      = "enabled"
  }
  network_interfaces {
    associate_public_ip_address = false
    security_groups = var.security_group_ids
  }
  block_device_mappings {
    device_name = "/dev/xvda"
    ebs {
      delete_on_termination = true
      snapshot_id = var.snapshot_id
      volume_size = 30
      volume_type = "gp3"
      throughput = 125
    }
  }
  user_data = base64encode(<<EOF
#!/bin/sh
echo "ECS_CLUSTER=${var.ecs_cluster_name}" >>/etc/ecs/ecs.config
echo "ECS_INSTANCE_ATTRIBUTES={\"service\":\"${var.service_name}\"}" >>/etc/ecs/ecs.config
echo "ECS_CONTAINER_STOP_TIMEOUT=${var.container_stop_timeout}" >>/etc/ecs/ecs.config
EOF
  )
}

resource "aws_autoscaling_group" "main" {
  name = var.asg_name
  desired_capacity = 0 # recommended zero start size for asg capacity provider
  max_size = 20
  min_size = var.min_instances
  capacity_rebalance = false
  vpc_zone_identifier = var.subnet_ids
  protect_from_scale_in = false
  mixed_instances_policy {
    launch_template {
      launch_template_specification {
        launch_template_id = aws_launch_template.main.id
        version = aws_launch_template.main.latest_version
      }
      dynamic "override" {
        for_each = var.instance_types
        content {
          instance_type = override.value
          weighted_capacity = "1"
        }
      }
    }
    instances_distribution {
      on_demand_base_capacity = var.on_demand_base_capacity
    }
  }
  instance_refresh {
    strategy = "Rolling"
    preferences {
      min_healthy_percentage = 100
      max_healthy_percentage = 200
      scale_in_protected_instances = "Ignore"
      standby_instances = "Ignore"
      skip_matching = false
      instance_warmup = 120
    }
  }
  enabled_metrics = [
    "GroupDesiredCapacity",
    "GroupInServiceInstances"
  ]
  tag {
    key                 = "AmazonECSManaged"
    propagate_at_launch = true
    value               = ""
  }
  tag {
    key                 = "map-migrated"
    propagate_at_launch = true
    value               = "mig47932"
  }
  lifecycle {
    ignore_changes = [desired_capacity, tag, desired_capacity_type]
  }
}

resource "aws_ecs_capacity_provider" "main" {
  name = var.asg_name
  auto_scaling_group_provider {
    auto_scaling_group_arn         = aws_autoscaling_group.main.arn
    managed_termination_protection = "DISABLED"
    # managed draining should be enabled manually
    managed_scaling {
      status                    = "ENABLED"
      target_capacity           = 100
      instance_warmup_period = 60
    }
  }
}

resource "aws_ecs_service" "main" {
  name = var.service_name
  cluster = var.ecs_cluster_id
  scheduling_strategy = "REPLICA"
  desired_count = var.desired_capacity
  task_definition = var.empty_task_definition
  capacity_provider_strategy {
    capacity_provider = aws_ecs_capacity_provider.main.name
    weight = 100
  }
  dynamic "ordered_placement_strategy" {
    for_each = var.ordered_placement_strategies
    content {
      field = ordered_placement_strategy.value.field # "instanceId"
      type  = ordered_placement_strategy.value.type # "spread"
    }
  }
  dynamic "placement_constraints" {
    for_each = var.placement_constraints
    content {
      type = placement_constraints.value
    }
  }
  dynamic "load_balancer" {
    for_each = var.balancers
    content {
      container_name = load_balancer.value["container_name"]
      container_port = load_balancer.value["container_port"]
      target_group_arn = load_balancer.value["target_group_arn"]
    }
  }
  dynamic "service_registries" {
    for_each = var.service_registries
    content {
      registry_arn = service_registries.value["registry_arn"]
      container_name = "nginx"
      container_port = 80
    }
  }
  deployment_minimum_healthy_percent = var.min_healthy_percentage
  deployment_maximum_percent = 100
  lifecycle {
    ignore_changes = [task_definition, desired_count]
  }
}
