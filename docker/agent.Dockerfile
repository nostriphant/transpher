FROM nostriphant/nip-01

RUN ["apt", "update"]
RUN ["apt-get", "install", "-y", "libzip-dev", "zip"]

RUN ["docker-php-ext-configure", "pcntl", "--enable-pcntl"]
RUN ["docker-php-ext-install", "zip", "pcntl"]

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN ["composer", "--no-dev", "install"]

WORKDIR "/app"
COPY ["VERSION", "composer.json", "composer.lock", "bootstrap.php", "agent.php", "."]
COPY ["src", "src"]

CMD ["/usr/local/bin/php", "/app/agent.php"]
