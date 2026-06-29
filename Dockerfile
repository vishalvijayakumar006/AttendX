FROM php:8.2-apache

# -------------------------------------------------
# 1️⃣ Install the PHP extensions your app needs
# -------------------------------------------------
#   - pdo_mysql : for MySQL/MariaDB connections
#   - gd        : for QR‑code PNG generation
# -------------------------------------------------
RUN apt-get update && apt-get install -y 
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev 
    && docker-php-ext-install pdo_mysql gd zip

# -------------------------------------------------
# 2️⃣ Enable Apache rewrite module (used by many PHP routers)
# -------------------------------------------------
RUN a2enmod rewrite

# -------------------------------------------------
# 3️⃣ Copy *all* source files into the container’s web root
# -------------------------------------------------
COPY . /var/www/html/

# -------------------------------------------------
# 4️⃣ Create the uploads folder (where QR PNGs are saved) 
#    and make it writable for the web‑server user (www-data)
# -------------------------------------------------
RUN mkdir -p /var/www/html/uploads/qrcodes && chmod -R 777 /var/www/html/uploads

# -------------------------------------------------
# 5️⃣ Start Apache in the foreground (required by Render)
# -------------------------------------------------
CMD [\"apache2-foreground\"]
