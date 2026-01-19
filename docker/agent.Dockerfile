FROM nostriphant/php-secp256k1:main

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip"]

RUN ["docker-php-ext-configure", "pcntl", "--enable-pcntl"]
RUN ["docker-php-ext-install", "zip", "pcntl"]

WORKDIR "/app"
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "agent.php", "."]
COPY ["src", "src"]

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]

CMD ["/usr/local/bin/php", "/app/agent.php"]
