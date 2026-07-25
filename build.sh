#!/bin/bash

set -e

echo "Rozpoczynanie procesu budowania Laravel + Vite..."

if [ ! -f "artisan" ]; then
    echo "Blad: To nie jest katalog projektu Laravel (brak pliku artisan)."
    exit 1
fi

echo "Instalacja zaleznosci NPM..."
npm install

echo "Budowanie plikow za pomoca Vite..."
npm run build

echo "Czyszczenie cache Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Proces budowania zakonczony pomyslnie!"