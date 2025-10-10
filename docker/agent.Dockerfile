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
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "agent.php", "."]
COPY ["src", "src"]

RUN ["curl", "-sS", "https://getcomposer.org/installer", "|", "php", "--", "--install-dir=/usr/local/bin", "--filename=composer"]
RUN ["/usr/local/bin/composer", "--no-dev", "install"]

CMD ["/usr/local/bin/php", "/app/agent.php"]
