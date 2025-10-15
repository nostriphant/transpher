FROM nostriphant/nip-01:main

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip"]

RUN ["docker-php-ext-configure", "pcntl", "--enable-pcntl"]
RUN ["docker-php-ext-install", "zip", "pcntl"]

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]

WORKDIR "/app"
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "relay.php", "."]
COPY ["src", "src"]

EXPOSE 80

CMD ["/usr/local/bin/php", "-d", "memory_limit=512M", "/app/relay.php", "0.0.0.0:80"]
