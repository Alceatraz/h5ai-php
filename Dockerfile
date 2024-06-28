FROM alpine:latest

COPY docker/conf /opt/conf
COPY build/_h5ai /opt/_h5ai
COPY docker/setup.sh /bin/setup

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.tuna.tsinghua.edu.cn/g' /etc/apk/repositories && \
    apk add --no-cache php83 php83-cgi php83-fpm nginx php83-session php83-json php83-xml php83-mbstring php83-exif php83-intl php83-gd php83-zip php83-opcache php83-fileinfo php83-sqlite3 ffmpeg imagemagick graphicsmagick supervisor tzdata && \
    rm -rf /etc/nginx && \
    rm -rf /etc/php83 && \
    chmod +x /bin/setup

CMD supervisord -c /app/conf/supervisord.conf

EXPOSE 80
VOLUME [ "/app/conf", "/app/data", "/app/logs", "/app/temp", "/app/h5ai" ]
