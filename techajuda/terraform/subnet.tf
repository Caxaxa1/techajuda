data "aws_vpc" "default" {
  default = true
}

data "aws_availability_zones" "available" {}

data "aws_subnets" "default_subnets" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
}

data "aws_subnet" "subnet_1" {
  id = data.aws_subnets.default_subnets.ids[0]
}

data "aws_subnet" "subnet_2" {
  id = data.aws_subnets.default_subnets.ids[1]
}

resource "aws_db_subnet_group" "rds_subnet_group" {
  name       = "rds-subnet-group"
  subnet_ids = [
    data.aws_subnet.subnet_1.id,
    data.aws_subnet.subnet_2.id
  ]

  tags = {
    Name = "techajuda-rds-subnet-group"
  }
}

# Security Group do RDS permitindo conexão do EC2
resource "aws_security_group" "rds_sg" {
  name        = "rds-sg"
  description = "Allow EC2 access to RDS"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.web_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}
