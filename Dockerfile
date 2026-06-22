FROM php:8.3-apache

ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN a2enmod rewrite headers

RUN { \
    echo "upload_max_filesize = 160M"; \
    echo "post_max_size = 170M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "max_input_time = 120"; \
} > /usr/local/etc/php/conf.d/custom.ini

COPY . /var/www/html/

RUN mkdir -p /var/www/html/data && chown -R www-data:www-data /var/www/html/data && chmod 775 /var/www/html/data

EXPOSE 80
