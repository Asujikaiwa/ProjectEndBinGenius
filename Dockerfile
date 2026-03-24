FROM php:8.2-fpm

# ติดตั้งโปรแกรมพื้นฐานที่ Laravel ต้องใช้
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev

# ติดตั้ง PHP extensions (เพื่อให้ต่อ Database และจัดการรูปได้)
RUN docker-php-ext-install pdo_mysql zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# ติดตั้ง Composer (ตัวจัดการ Package ของ Laravel)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ตั้งค่าให้เข้าไปทำงานที่โฟลเดอร์นี้
WORKDIR /var/www

# ก๊อปปี้ไฟล์โปรเจคทั้งหมดเข้าไปในตู้
COPY . .

# สั่งโหลด Library ต่างๆ
RUN composer install --no-scripts --no-autoloader

# ปรับสิทธิ์โฟลเดอร์เพื่อไม่ให้ติด Error Permission Denied
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]