
resource "aws_instance" "web" {
  depends_on = [
    aws_db_instance.rds
  ]

subnet_id = data.aws_subnet.subnet_1.id

  vpc_security_group_ids = [
    aws_security_group.web_sg.id
  ]

  ami                    = "ami-0fa3fe0fa7920f68e"
  instance_type          = var.instance_type

  user_data = templatefile("user_data.sh" , {
    db_endpoint = aws_db_instance.rds.address
    db_username = var.db_username
    db_password = var.db_password
  })

  tags = {
    Name = "techajuda"
  }

}
