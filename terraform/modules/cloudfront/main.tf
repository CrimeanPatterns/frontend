terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "5.81.0"
    }
  }
}

locals {
  cache_policy_caching_disabled = "4135ea2d-6df8-44a3-9df3-4b5a84be39ad"
  cache_policy_with_accept_and_qs = "da7bade4-2353-414f-9d4e-6e787a04368b"
  origin_request_policy_all_viewer = "216adef6-5c7f-47e4-b989-5492eafa07d3"
  origin_request_policy_with_accept_and_qs = "2fd8782d-e917-40ac-92da-1b9c5918a8ef"
  origin_request_policy_with_qs_and_host = "a37b0b4c-a22f-44a3-b5d4-21a68261b35a"
  origin_request_policy_all = "07d752be-bc03-4fbe-90ca-000a28140a24"
}

resource "aws_cloudfront_cache_policy" "with-qs" {
  name = "with-qs"
  min_ttl = 1
  default_ttl = 86400
  parameters_in_cache_key_and_forwarded_to_origin {
    headers_config {
      header_behavior = "whitelist"
      headers {
        items = ["Host", "X-AW-MOBILE-NATIVE"]
      }
    }
    query_strings_config {
      query_string_behavior = "all"
    }
    cookies_config {
      cookie_behavior = "whitelist"
      cookies {
        items = ["BLOGSESSID", "wordpress_loginuser_last_visit", "native", "wordpress_logged_in_*"]
      }
    }
    enable_accept_encoding_brotli = true
    enable_accept_encoding_gzip = true
  }
}

resource "aws_cloudfront_cache_policy" "with-accept-and-qs" {
  name = "with-accept-and-qs"
  min_ttl = 1
  default_ttl = 86400
  parameters_in_cache_key_and_forwarded_to_origin {
    headers_config {
      header_behavior = "whitelist"
      headers {
        items = ["Accept", "Referer", "Host"]
      }
    }
    query_strings_config {
      query_string_behavior = "all"
    }
    cookies_config {
      cookie_behavior = "none"
    }
    enable_accept_encoding_brotli = true
    enable_accept_encoding_gzip = true
  }
}

resource "aws_cloudfront_cache_policy" "blog-html" {
  name        = "blog-static"
  comment     = "based on qs, mobile-native header and cookie"
  default_ttl = 600
  max_ttl     = 600
  min_ttl     = 1
  parameters_in_cache_key_and_forwarded_to_origin {
    cookies_config {
      cookie_behavior = "whitelist"
      cookies {
        items = ["native"]
      }
    }
    headers_config {
      header_behavior = "whitelist"
      headers {
        items = ["X-AW-MOBILE-NATIVE"]
      }
    }
    query_strings_config {
      query_string_behavior = "all"
    }
  }
}

resource "aws_cloudfront_origin_request_policy" "blog-html" {
  name    = "blog-html"
  comment = "based on qs, mobile-native header and cookie"
  cookies_config {
    cookie_behavior = "whitelist"
    cookies {
      items = ["native", "wordpress_loginuser_last_visit", "wordpress_logged_in_*"]
    }
  }
  headers_config {
    header_behavior = "whitelist"
    headers {
      items = ["X-AW-MOBILE-NATIVE"]
    }
  }
  query_strings_config {
    query_string_behavior = "all"
  }
}

resource "aws_cloudfront_response_headers_policy" "static" {
    name    = "static"
    comment = "static"
    remove_headers_config {
      items {
        header = "Set-Cookie"
      }
    }
}

resource "aws_cloudfront_distribution" "passthru" {
  enabled = true
  aliases = ["awardwallet.com"]
  comment = "frontend passthru"
  http_version = "http2and3"
  is_ipv6_enabled = true
  default_cache_behavior {
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all_viewer
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
  origin {
    connection_attempts = 3
    connection_timeout = 10
    domain_name = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    origin_id   = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "match-viewer"
      origin_keepalive_timeout = 60
      origin_read_timeout = 60
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }
  viewer_certificate {
    acm_certificate_arn            = "arn:aws:acm:us-east-1:718278292471:certificate/ff43ddca-2e05-4289-8133-91cec63a0cea"
    minimum_protocol_version       = "TLSv1.2_2021"
    ssl_support_method             = "sni-only"
  }
  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }
  # blog substitutes webp images basing on Accept header
  dynamic "ordered_cache_behavior" {
    for_each = toset(["png", "jpg", "jpeg", "gif", "webp"])
    content {
      allowed_methods = ["GET", "HEAD"]
      cache_policy_id = local.cache_policy_with_accept_and_qs
      cached_methods = ["GET", "HEAD"]
      compress = false
      origin_request_policy_id = local.origin_request_policy_with_accept_and_qs
      path_pattern = "/blog/*.${ordered_cache_behavior.key}"
      smooth_streaming = false
      target_origin_id = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
      trusted_key_groups = []
      trusted_signers = []
      viewer_protocol_policy = "allow-all"
      response_headers_policy_id = aws_cloudfront_response_headers_policy.static.id
    }
  }
  # cache css and js with query string and gzip
  dynamic "ordered_cache_behavior" {
    for_each = toset(["js", "css"])
    content {
      allowed_methods = ["GET", "HEAD"]
      cache_policy_id = aws_cloudfront_cache_policy.with-qs.id
      cached_methods = ["GET", "HEAD"]
      compress = true
      origin_request_policy_id = local.origin_request_policy_with_qs_and_host
      path_pattern = "/*.${ordered_cache_behavior.key}"
      smooth_streaming = false
      target_origin_id = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
      trusted_key_groups = []
      trusted_signers = []
      viewer_protocol_policy = "allow-all"
      response_headers_policy_id = aws_cloudfront_response_headers_policy.static.id
    }
  }
  # do not cache mobile avatars - they require authorization
  ordered_cache_behavior {
    path_pattern = "/m/api/data/small-avatar/*"
    allowed_methods        = ["GET", "HEAD", "OPTIONS"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all_viewer
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
  # cache images and fonts with query string without gzip
  dynamic "ordered_cache_behavior" {
    for_each = toset(["png", "jpg", "jpeg", "gif", "webp", "woff2"])
    content {
      allowed_methods = ["GET", "HEAD"]
      cache_policy_id = aws_cloudfront_cache_policy.with-qs.id
      cached_methods = ["GET", "HEAD"]
      compress = false
      origin_request_policy_id = local.origin_request_policy_with_qs_and_host
      path_pattern = "/*.${ordered_cache_behavior.key}"
      smooth_streaming = false
      target_origin_id = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
      trusted_key_groups = []
      trusted_signers = []
      viewer_protocol_policy = "allow-all"
      response_headers_policy_id = aws_cloudfront_response_headers_policy.static.id
    }
  }
  # do not cache blog admin
  ordered_cache_behavior {
    path_pattern = "/blog/wp-admin/*"
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all_viewer
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
  ordered_cache_behavior {
    path_pattern = "/blog/wp-login.php"
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all_viewer
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
  # do not cache blog admin
  ordered_cache_behavior {
    path_pattern = "/blog/wp-json/*"
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
  # do not cache comments posting
  ordered_cache_behavior {
    path_pattern = "/blog/wp-comments-post.php"
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cache_policy_id = local.cache_policy_caching_disabled
    cached_methods         = ["GET", "HEAD"]
    compress = true
    origin_request_policy_id = local.origin_request_policy_all
    target_origin_id       = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
    viewer_protocol_policy = "allow-all"
    function_association {
      event_type   = "viewer-request"
      function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
    }
  }
   # cache blog html content
   ordered_cache_behavior {
     allowed_methods = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
     cache_policy_id = aws_cloudfront_cache_policy.with-qs.id
     cached_methods = ["GET", "HEAD"]
     compress = true
     origin_request_policy_id = local.origin_request_policy_with_qs_and_host
     path_pattern = "/blog/*"
     smooth_streaming = false
     target_origin_id = "frontend-vpc-566252517.us-east-1.elb.amazonaws.com"
     trusted_key_groups = []
     trusted_signers = []
     viewer_protocol_policy = "redirect-to-https"
     function_association {
       event_type   = "viewer-request"
       function_arn = "arn:aws:cloudfront::718278292471:function/add-client-ip"
     }
   }

}