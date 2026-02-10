variable "suffix" {
  type = string
}
variable "source-domain" {
  type = string
}
variable "custom-headers" {
  type = list(object({
    name = string
    value = string
  }))
}