FROM php:8.3

# Use the default production configuration
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN ["apt", "update"]

RUN ["apt-get", "install", "-y", "libzip-dev", "zip", "libgmp-dev", "libsodium-dev"]
RUN ["docker-php-ext-install", "zip", "gmp"]

RUN ["pecl", "install", "--force", "redis"]
RUN ["rm", "-rf", "/tmp/pear"]
RUN ["docker-php-ext-enable", "redis", "sodium"]

WORKDIR "/app"
COPY ["composer.json", "composer.lock", "bootstrap.php", "agent.php", "websocket.php", "."]
COPY ["src", "src"]

RUN ["/usr/local/bin/php", "-r", "copy('https://getcomposer.org/installer', 'composer-setup.php');"]
RUN ["/usr/local/bin/php", "-r", "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"]
RUN ["/usr/local/bin/php", "composer-setup.php"]
RUN ["/usr/local/bin/php", "-r", "unlink('composer-setup.php');"]
RUN ["/usr/local/bin/php", "composer.phar", "--no-dev", "install"]

CMD ["/usr/local/bin/php", "/app/agent.php"]
