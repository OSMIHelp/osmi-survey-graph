FROM nginx

COPY ./resources/nginx/default.conf /etc/nginx/conf.d/default.conf
