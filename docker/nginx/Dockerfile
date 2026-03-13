FROM nginx:1.27.1-alpine

RUN rm /etc/nginx/conf.d/default.conf
ADD docker/nginx/conf.d/jul-dx.conf /etc/nginx/conf.d
