FROM php:8.4

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip", "libgmp-dev", "libsodium-dev", "autoconf", "build-essential", "git", "libtool", "pkgconf"]

RUN ["docker-php-ext-configure", "pcntl", "--enable-pcntl"]
RUN ["docker-php-ext-install", "zip", "gmp", "pcntl"]

RUN ["rm", "-rf", "/tmp/pear"]
RUN ["docker-php-ext-enable", "sodium"]

WORKDIR "/opt"
RUN ["apt", "install", "--yes", "autoconf", "libsecp256k1-dev"]
RUN ["git", "clone", "https://github.com/1ma/secp256k1-nostr-php"]
WORKDIR "/opt/secp256k1-nostr-php"
RUN ["make", "ext"]
RUN ["make", "check"]
RUN ["make", "install"]
ADD ["./docker/ext-secp256k1-nostr-php.ini", "$PHP_INI_DIR/conf.d/ext-secp256k1-nostr-php.ini"]

WORKDIR "/app"
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "relay.php", "."]
COPY ["src", "src"]

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]

EXPOSE 80

CMD ["/usr/local/bin/php", "-d", "memory_limit=512M", "/app/relay.php", "0.0.0.0:80"]
