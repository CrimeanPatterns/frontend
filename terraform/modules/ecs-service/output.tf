output "asg_name" {
  value = aws_autoscaling_group.main.name
}

output "capacity_provider_name" {
  value = aws_ecs_capacity_provider.main.name
}