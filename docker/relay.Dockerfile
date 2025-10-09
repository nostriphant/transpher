FROM php:8.4

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip", "libgmp-dev", "libsodium-dev", "autoconf", "build-essential", "git", "libtool", "pkgconf"]

RUN ["docker-php-ext-configure", "pcntl", "--enable-pcntl"]
RUN ["docker-php-ext-install", "zip", "gmp", "pcntl", "sqlite3"]

RUN ["rm", "-rf", "/tmp/pear"]
RUN ["docker-php-ext-enable", "sodium"]

WORKDIR "/opt"
RUN ["git", "clone", "https://github.com/1ma/secp256k1-nostr-php"]
WORKDIR "/opt/secp256k1-nostr-php"
RUN ["git", "submodule", "init"]
RUN ["git", "submodule", "update"]
RUN ["make", "secp256k1", "ext"]
RUN ["make", "check"]
RUN ["make", "install"]
ADD ["./docker/ext-secp256k1-nostr-php.ini", "$PHP_INI_DIR/conf.d/ext-secp256k1-nostr-php.ini"]

WORKDIR "/app"
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "relay.php", "."]
COPY ["src", "src"]

RUN ["/usr/local/bin/php", "-r", "copy('https://getcomposer.org/installer', 'composer-setup.php');"]
RUN ["/usr/local/bin/php", "-r", "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"]
RUN ["/usr/local/bin/php", "composer-setup.php"]
RUN ["/usr/local/bin/php", "-r", "unlink('composer-setup.php');"]
RUN ["/usr/local/bin/php", "composer.phar", "--no-dev", "install"]

EXPOSE 80

CMD ["/usr/local/bin/php", "-d", "memory_limit=512M", "/app/relay.php", "0.0.0.0:80"]
