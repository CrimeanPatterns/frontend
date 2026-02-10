data "aws_caller_identity" "current" {}

resource "aws_iam_policy" "cloudfront-for-w3c-total-cache" {
  name = "cloudfront-for-w3c-total-cache-${var.suffix}"
  description = "for w3c total cache plugin cdn functionality"
  policy = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "ecrRepos",
            "Effect": "Allow",
            "Action": [
                "ecr:GetDownloadUrlForLayer",
                "ecr:BatchGetImage",
                "ecr:BatchCheckLayerAvailability"
            ],
            "Resource": [
                "arn:aws:ecr:*:718278292471:repository/postfix"
            ]
        },
        {
            "Sid": "ecrAuth",
            "Effect": "Allow",
            "Action": [
                "ecr:GetAuthorizationToken"
            ],
            "Resource": "*"
        },
        {
            "Sid": "CloudFront",
            "Effect": "Allow",
            "Action": [
              "cloudfront:ListDistributions",
              "cloudfront:CreateDistribution"
            ],
            "Resource": [
                "*"
            ]
        }
    ]
}
EOF
}

resource "aws_iam_role" "blog-instance" {
  name = "blog-instance-${var.suffix}"
  assume_role_policy = <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Action": "sts:AssumeRole",
      "Principal": {
        "Service": "ec2.amazonaws.com"
      },
      "Effect": "Allow",
      "Sid": ""
    }
  ]
}
EOF
}

resource "aws_iam_policy" "blog-cache-controller" {
  name = "frontend-blog-cache-controller-plugin-${var.suffix}"
  description = "for C3 Cloudfront Cache Controller plugin functionality"
  policy = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": [
                "cloudfront:GetDistribution",
                "cloudfront:ListInvalidations",
                "cloudfront:GetStreamingDistribution",
                "cloudfront:GetDistributionConfig",
                "cloudfront:GetInvalidation",
                "cloudfront:CreateInvalidation"
            ],
            "Effect": "Allow",
            "Resource": "*"
        }
    ]
}
EOF
}

resource "aws_iam_policy" "blog-cloudwatch" {
  name = "frontend-blog-cloudwatch-${var.suffix}"
  description = "put metrics"
  policy = <<EOF
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": [
                "cloudwatch:PutMetricData"
            ],
            "Effect": "Allow",
            "Resource": "*"
        }
    ]
}
EOF
}

resource "aws_iam_policy_attachment" "blog-instance-attach-cloudfront-for-w3c-total-cache" {
  name = "blog-instance-attach-cloudfront-for-w3c-total-cache-${var.suffix}"
  roles = [aws_iam_role.blog-instance.name]
  policy_arn = aws_iam_policy.cloudfront-for-w3c-total-cache.arn
}

resource "aws_iam_policy_attachment" "blog-instance-attach-blog-cache-controller" {
  name = "blog-instance-attach-blog-cache-controller-${var.suffix}"
  roles = [aws_iam_role.blog-instance.name]
  policy_arn = aws_iam_policy.blog-cache-controller.arn
}

resource "aws_iam_policy_attachment" "blog-instance-attach-blog-cloudwatch" {
  name = "blog-instance-attach-blog-cloudwatch-${var.suffix}"
  roles = [aws_iam_role.blog-instance.name]
  policy_arn = aws_iam_policy.blog-cloudwatch.arn
}

resource "aws_iam_instance_profile" "blog-instance" {
  name = "blog-instance-${var.suffix}"
  role = aws_iam_role.blog-instance.name
}

resource "aws_cloudfront_cache_policy" "no-headers" {
  name        = "blog-${var.suffix}-no-headers"
  default_ttl = 86400
  max_ttl     = 31536000
  min_ttl     = 1
  parameters_in_cache_key_and_forwarded_to_origin {
    cookies_config {
      cookie_behavior = "none"
    }
    headers_config {
      header_behavior = "none"
    }
    query_strings_config {
      query_string_behavior = "all"
    }
    enable_accept_encoding_brotli = true
    enable_accept_encoding_gzip = true
  }
}

resource "aws_cloudfront_cache_policy" "with-accept" {
  name        = "blog-${var.suffix}-with-accept"
  default_ttl = 86400
  max_ttl     = 31536000
  min_ttl     = 1
  parameters_in_cache_key_and_forwarded_to_origin {
    cookies_config {
      cookie_behavior = "none"
    }
    headers_config {
      header_behavior = "whitelist"
      headers {
        items = ["accept"]
      }
    }
    query_strings_config {
      query_string_behavior = "all"
    }
    enable_accept_encoding_brotli = true
    enable_accept_encoding_gzip = true
  }
}

resource "aws_cloudfront_function" "tune-webp" {
  name    = "normalize-accept-${var.suffix}"
  runtime = "cloudfront-js-1.0"
  comment = "distinct caches for webp and non-webp browsers"
  publish = true
  code    = file("${path.module}/normalize-accept-function.js")
}

resource "aws_cloudfront_distribution" "main" {
  origin {
    connection_attempts = 3
    connection_timeout  = 10
    domain_name         = var.source-domain
    origin_id           = var.source-domain

    dynamic "custom_header" {
      for_each = var.custom-headers
      content {
        name = custom_header.value["name"]
        value = custom_header.value["value"]
      }
    }

    custom_origin_config {
      http_port                = 80
      https_port               = 443
      origin_keepalive_timeout = 5
      origin_protocol_policy   = "match-viewer"
      origin_read_timeout      = 30
      origin_ssl_protocols     = [
        "TLSv1",
        "TLSv1.1",
        "TLSv1.2",
      ]
    }
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = true
  }

  enabled             = true
  is_ipv6_enabled     = true
  comment             = "serve blog static for ${var.suffix} with w3c total cache from ${var.source-domain}"

  default_cache_behavior {
    allowed_methods  = ["GET", "HEAD"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = var.source-domain
    cache_policy_id = aws_cloudfront_cache_policy.no-headers.id
    compress = true
    viewer_protocol_policy = "allow-all"
    trusted_signers = ["self"]
  }

  dynamic "ordered_cache_behavior" {
    for_each = ["css", "js", "woff", "woff2", "less", "ico", "ttf", "otf", "xsl"]
    content {
      allowed_methods        = [
        "GET",
        "HEAD"
      ]
      cache_policy_id = aws_cloudfront_cache_policy.no-headers.id
      cached_methods         = [
        "GET",
        "HEAD"
      ]
      compress               = true
      path_pattern           = "*.${ordered_cache_behavior.value}"
      target_origin_id       = var.source-domain
      viewer_protocol_policy = "allow-all"
    }
  }

  dynamic "ordered_cache_behavior" {
    for_each = ["png", "jpg", "jpeg", "webp", "gif", "svg"]
    content {
      allowed_methods        = [
        "GET",
        "HEAD"
      ]
      cache_policy_id = aws_cloudfront_cache_policy.with-accept.id
      cached_methods         = [
        "GET",
        "HEAD"
      ]
      compress               = true
      path_pattern           = "*.${ordered_cache_behavior.value}"
      target_origin_id       = var.source-domain
      viewer_protocol_policy = "allow-all"
      function_association {
        event_type   = "viewer-request"
        function_arn = aws_cloudfront_function.tune-webp.arn
      }
    }
  }
}

