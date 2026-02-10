# used by spotUp.py, spotCancel.py for creating clean base for staging
# https://jenkins.awardwallet.com/job/Frontend/job/backups/job/clean-base-for-staging/

resource "aws_launch_template" "backup-processor" {
  name = "${var.name}-backup-processor"
  update_default_version = true
  image_id = var.image_id
  key_name = var.key_name
  tag_specifications {
    resource_type = "instance"
    tags = {
      Name = "${var.name}-backup-processor",
      "map-migrated" = "mig47932"
    }
  }
  tag_specifications {
    resource_type = "volume"
    tags = {
      "map-migrated" = "mig47932"
    }
  }
  iam_instance_profile {
    name = "frontend-instance"
  }
  ebs_optimized = true
  metadata_options {
    http_endpoint = "enabled"
    http_tokens = "optional"
    http_put_response_hop_limit = 2
    http_protocol_ipv6          = "disabled"
    instance_metadata_tags      = "enabled"
  }
  network_interfaces {
    associate_public_ip_address = false
    security_groups = var.security_group_ids
  }
  user_data = base64encode(<<EOF
#!/bin/sh
echo "ECS_CLUSTER=${var.ecs_cluster_name}" >>/etc/ecs/ecs.config
echo "ECS_ENGINE_TASK_CLEANUP_WAIT_DURATION=10m" >>/etc/ecs/ecs.config
EOF
  )
}

resource "aws_autoscaling_group" "backup-processors" {
  name = "${var.name}-backup-processors"
  desired_capacity = 0
  desired_capacity_type = "units"
  max_size = 1
  min_size = 0
  capacity_rebalance = false
  vpc_zone_identifier = var.subnet_ids
  protect_from_scale_in = false
  mixed_instances_policy {
    launch_template {
      launch_template_specification {
        launch_template_id = aws_launch_template.backup-processor.id
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
      on_demand_base_capacity = 1 # always on-demand, spot instance will be killed in long run
      spot_allocation_strategy = "capacity-optimized"
    }
  }
  enabled_metrics = [
    "GroupDesiredCapacity",
    "GroupInServiceInstances",
  ]
  lifecycle {
    ignore_changes = [desired_capacity]
  }
  tag {
    key                 = "map-migrated"
    propagate_at_launch = true
    value               = "mig47932"
  }
  tag {
    key                 = "AmazonECSManaged"
    propagate_at_launch = true
    value               = ""
  }
}

resource "aws_ecs_capacity_provider" "main" {
  name = "clean-base-for-staging-workers"
  auto_scaling_group_provider {
    auto_scaling_group_arn         = aws_autoscaling_group.backup-processors.arn
  }
}

