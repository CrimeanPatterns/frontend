resource "aws_cloudwatch_metric_alarm" "high-cpu-usage" {
  for_each = var.instances
  alarm_name          = "${var.project_name}-${var.metric_name}-${each.key}"
  comparison_operator = var.compare_operation
  evaluation_periods  = "1"
  datapoints_to_alarm = "1"
  metric_name         = var.metric_name
  namespace           = "AWS/EC2"
  dimensions = {
    InstanceId = each.value.id
  }
  period              = "300"
  statistic           = "Average"
  threshold           = each.value.threshold
  alarm_description = "${var.project_name}: ${var.metric_name} for ${each.key}"
  alarm_actions     = [var.alarm_action]
  treat_missing_data = "breaching"
}