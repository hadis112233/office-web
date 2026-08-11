FROM node:24-alpine AS frontend-assets

WORKDIR /assets
RUN npm init -y >/dev/null 2>&1 \
    && npm install --omit=dev \
        pdfjs-dist@3.11.174 \
        pdf-lib@1.17.1 \
        jspdf@2.5.1 \
        jszip@3.10.1 \
        qrcode@1.5.4 \
        jsqr@1.4.0 \
        hash-wasm@4.12.0 \
        esbuild@0.28.1

RUN ./node_modules/.bin/esbuild \
        ./node_modules/qrcode/lib/browser.js \
        --bundle \
        --global-name=QRCode \
        --platform=browser \
        --format=iife \
        --minify \
        --outfile=/assets/qrcode.js \
    && ./node_modules/.bin/esbuild \
        ./node_modules/jsqr/dist/jsQR.js \
        --minify \
        --legal-comments=inline \
        --outfile=/assets/jsqr.min.js

FROM php:8.3-apache

ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN a2enmod rewrite headers expires

# 视频转码与压缩由本机容器完成，不依赖第三方上传服务
RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/*

RUN { \
    echo "upload_max_filesize = 160M"; \
    echo "post_max_size = 170M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "max_input_time = 120"; \
} > /usr/local/etc/php/conf.d/custom.ini

COPY . /var/www/html/

# 固定版本打包前端组件，局域网断网时仍可使用 PDF、二维码和校验工具。
RUN mkdir -p /var/www/html/static/vendor
COPY --from=frontend-assets /assets/node_modules/pdfjs-dist/build/pdf.min.js /var/www/html/static/vendor/pdf.min.js
COPY --from=frontend-assets /assets/node_modules/pdfjs-dist/build/pdf.worker.min.js /var/www/html/static/vendor/pdf.worker.min.js
COPY --from=frontend-assets /assets/node_modules/pdf-lib/dist/pdf-lib.min.js /var/www/html/static/vendor/pdf-lib.min.js
COPY --from=frontend-assets /assets/node_modules/jspdf/dist/jspdf.umd.min.js /var/www/html/static/vendor/jspdf.umd.min.js
COPY --from=frontend-assets /assets/node_modules/jszip/dist/jszip.min.js /var/www/html/static/vendor/jszip.min.js
COPY --from=frontend-assets /assets/qrcode.js /var/www/html/static/vendor/qrcode.js
COPY --from=frontend-assets /assets/jsqr.min.js /var/www/html/static/vendor/jsqr.min.js
COPY --from=frontend-assets /assets/node_modules/hash-wasm/dist/index.umd.min.js /var/www/html/static/vendor/hash-wasm.umd.min.js

# 构建时生成现代格式背景图；源码仍保留 JPG，非 Docker 环境可正常回退。
RUN ffmpeg -hide_banner -loglevel error -y \
    -i /var/www/html/static/images/5cc166b9137d2.jpg \
    -frames:v 1 -c:v libwebp -compression_level 6 -q:v 72 \
    /var/www/html/static/images/background.webp

RUN mkdir -p /var/www/html/data && chown -R www-data:www-data /var/www/html/data && chmod 775 /var/www/html/data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r '$r=@json_decode(@file_get_contents("http://127.0.0.1/api/health.php"),true); exit(($r["ok"]??false)?0:1);'
