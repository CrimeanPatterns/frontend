resource "aws_s3_bucket" "segment-attachments" {
  bucket = var.segment_attachments_bucket_name
  lifecycle {
    ignore_changes = [server_side_encryption_configuration]
  }
  tags = {
    "map-migrated" = "mig47932"
  }
}

resource "aws_s3_bucket_acl" "segment-attachments" {
  bucket = aws_s3_bucket.segment-attachments.id
  acl = "private"
}

resource "aws_s3_bucket_server_side_encryption_configuration" "segment-attachments" {
  bucket = aws_s3_bucket.segment-attachments.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = "aws:kms"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "example" {
  bucket = aws_s3_bucket.segment-attachments.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}