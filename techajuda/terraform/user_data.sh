#!/bin/bash
yum update -y
yum install -y httpd php php-mysqli git
yum install -y mariadb105

sudo systemctl enable httpd
sudo systemctl start httpd

DB_ENDPOINT="${db_endpoint}"
DB_USERNAME="${db_username}"
DB_PASSWORD="${db_password}"

rm -rf /var/www/html
git clone https://github.com/Caxaxa1/techajuda.git /var/www/html

cat > /var/www/html/index.php << EOF
<?php header("Location: .techajuda/public/index.php"); ?>
EOF

echo "Esperando o RDS ficar pronto..." > /root/rds.log

# # Espera até conseguir conectar
# until mysql -h "$db_endpoint" -P 3306 -u "$db_username" -p"$db_password" -e "SELECT 1" &> /dev/null
# do
#   echo "$(date) - RDS ainda não está disponível" >> /root/rds.log
#   sleep 10
# done

echo "RDS pronto" >> /root/rds.log

# mysql -h "$DB_ENDPOINT" -P 3306 -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS systechpump;" >> /root/rds.log
mysql -h "${db_endpoint}" -P 3306 -u "${db_username}" -p"${db_password}"  < /var/www/html/database/BD.sql 


rm -f /var/www/html/src/config.php

cat > /var/www/html/src/config.php << EOF
<?php
function conectarBD(){
    \$conexao = mysqli_connect("${db_endpoint}", "${db_username}", "${db_password}", "techajuda");

    mysqli_query(\$conexao, "SET NAMES 'utf8'");
    mysqli_query(\$conexao, "SET character_set_connection=utf8");
    mysqli_query(\$conexao, "SET character_set_client=utf8");
    mysqli_query(\$conexao, "SET character_set_results=utf8");

    return \$conexao;
}

    \$conexao = conectarBD();
?>
EOF
