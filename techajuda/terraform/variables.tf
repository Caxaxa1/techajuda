variable "region" {
  default = "us-east-1"
}

variable "instance_type" {
  default = "t2.micro"
}

variable "db_username" {
  type = string
  default = "systechpump"
}

variable "db_password" { 
  type = string
  default = "Systech123"
  sensitive = true
}
