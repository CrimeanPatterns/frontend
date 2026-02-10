resource "aws_cloudwatch_metric_alarm" "high-queue" {
  alarm_name          = "frontend-high-queue"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "15"
  datapoints_to_alarm = "15"
  metric_name         = "messages_ready"
  namespace           = "AW/Rabbit"
  dimensions = {
    host = "frontend-rabbit"
  }
  period              = "60"
  statistic           = "Maximum"
  threshold           = "3000"
  alarm_description = "There are queue, check workers"
  alarm_actions     = [var.slack_topic_arn]
}

resource "aws_cloudwatch_metric_alarm" "postfix-errors" {
  alarm_name          = "frontend-postfix-errors"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "1"
  datapoints_to_alarm = "1"
  metric_name         = "postfix_errors"
  namespace           = "AW/Frontend"
  period              = "300"
  statistic           = "Maximum"
  threshold           = "10"
  alarm_description = "Postfix errors, see 'postfix errors' saved search in the kibana"
  alarm_actions     = [var.slack_topic_arn]
  insufficient_data_actions = [var.slack_topic_arn]
}
