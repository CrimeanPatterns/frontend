resource "aws_iam_policy" "blog-tests" {
  name = "blog-tests"
  description = "read artifacts for tests"
  policy = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": [
                "s3:GetObjectAcl",
                "s3:GetObject",
                "s3:GetObjectAttributes"
            ],
            "Resource": "arn:aws:s3:::/aw-builder-artifacts/blog-plugins.zip"
        }
    ]
}
EOF
}

resource "aws_iam_policy_attachment" "builer-agent-instance-attach-blog-tests" {
  name = "builer-agent-instance-attach-blog-tests"
  roles = ["builder-agent-instance"]
  policy_arn = aws_iam_policy.blog-tests.arn
}



