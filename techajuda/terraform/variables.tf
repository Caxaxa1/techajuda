variable "region" {
  default = "us-east-1"
}

variable "instance_type" {
  default = "t2.micro"
}

variable "db_username" {
  type = string
  default = "techajuda"
}

variable "db_password" { 
  type = string
  default = "Techajuda123"
  sensitive = true
}
