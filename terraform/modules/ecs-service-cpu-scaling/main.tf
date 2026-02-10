resource "aws_cloudwatch_metric_alarm" "cpu_high" {
  alarm_name          = "${var.ecs_cluster_name}-${var.service_name}-cpu-high"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 1
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 120
  statistic           = "Average"
  threshold           = var.cpu_high_threshold
  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
  alarm_actions = [
    aws_appautoscaling_policy.scale_up_policy.arn
  ]
  tags = { "map-migrated" = "mig47932", "autoscaling" = "true" }
}

resource "aws_cloudwatch_metric_alarm" "cpu_low" {
  alarm_name          = "${var.ecs_cluster_name}-${var.service_name}-cpu-low"
  comparison_operator = "LessThanThreshold"
  evaluation_periods  = 1
  metric_name         = "CPUUtilization"
  namespace           = "AWS/EC2"
  period              = 300
  statistic           = "Average"
  threshold           = var.cpu_low_threshold
  dimensions = {
    AutoScalingGroupName = var.asg_name
  }
  alarm_actions = [
    aws_appautoscaling_policy.scale_down_policy.arn
  ]
  tags = { "map-migrated" = "mig47932", "autoscaling" = "true" }
}

resource "aws_appautoscaling_policy" "scale_up_policy" {
  name               = "${var.ecs_cluster_name}-${var.service_name}-scale-up"
  depends_on         = [aws_appautoscaling_target.scale_target]
  service_namespace  = "ecs"
  resource_id        = "service/${var.ecs_cluster_name}/${var.service_name}"
  scalable_dimension = "ecs:service:DesiredCount"
  step_scaling_policy_configuration {
    adjustment_type         = "PercentChangeInCapacity"
    cooldown                = 5
    metric_aggregation_type = "Maximum"
    min_adjustment_magnitude = 1
    step_adjustment {
      metric_interval_lower_bound = 0
      scaling_adjustment          = 20
    }
  }
}

resource "aws_appautoscaling_policy" "scale_down_policy" {
  name               = "${var.ecs_cluster_name}-${var.service_name}-scale-down-policy"
  depends_on         = [aws_appautoscaling_target.scale_target]
  service_namespace  = "ecs"
  resource_id        = "service/${var.ecs_cluster_name}/${var.service_name}"
  scalable_dimension = "ecs:service:DesiredCount"
  step_scaling_policy_configuration {
    adjustment_type         = "PercentChangeInCapacity"
    cooldown                = 60
    metric_aggregation_type = "Maximum"
    min_adjustment_magnitude = 1
    step_adjustment {
      metric_interval_upper_bound = 0
      scaling_adjustment          = -5
    }
  }
}

resource "aws_appautoscaling_target" "scale_target" {
  service_namespace  = "ecs"
  resource_id        = "service/${var.ecs_cluster_name}/${var.service_name}"
  scalable_dimension = "ecs:service:DesiredCount"
  min_capacity       = var.min_processes
  max_capacity       = var.max_processes
}
