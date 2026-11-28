FROM nginx:stable-alpine

# Samopodpisany certyfikat dla localhost, ważny ok. 2 lata (wyłącznie do pracy lokalnej)
RUN apk add --no-cache openssl \
    && mkdir -p /etc/nginx/certs \
    && openssl req -x509 -nodes -newkey rsa:2048 -days 730 \
        -keyout /etc/nginx/certs/dev.key -out /etc/nginx/certs/dev.crt \
        -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" \
    && apk del openssl

ENV HTTPS_PORT=8443 FORCE_HTTPS=1

# obraz nginx zamienia pliki z /etc/nginx/templates/*.template na konfigurację w conf.d (podstawia zmienne środowiskowe)
COPY docker/nginx/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/nginx/app-common.conf /etc/nginx/app-common.conf
WORKDIR /app
