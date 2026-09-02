# Obraz nginx z wbudowanym public/ — bez polegania na tym, że host ma już
# zbudowane assety (npm run build). Ten sam etap budowania co w głównym
# Dockerfile, żeby obie strony (PHP-FPM i nginx) serwowały identyczne pliki.
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --silent && npm run build

FROM nginx:stable-alpine
COPY docker-deploy/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build
