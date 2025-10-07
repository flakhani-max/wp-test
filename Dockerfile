# Dockerfile for Basic WordPress with Hello World theme
FROM wordpress:6.5-php8.2-apache

# Install system dependencies and WP-CLI
RUN apt-get update && apt-get install -y \
    unzip \
    netcat-openbsd \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Copy custom Hello World theme
COPY wp-content/themes/hello-world /var/www/html/wp-content/themes/hello-world

# Copy must-use plugins (auto-loaded by WordPress)
RUN mkdir -p /var/www/html/wp-content/mu-plugins
COPY wp-content/mu-plugins /var/www/html/wp-content/mu-plugins

# Copy custom entrypoint script
COPY custom-entrypoint.sh /usr/local/bin/custom-entrypoint.sh
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

# Use Cloud Run's PORT (default 8080)
EXPOSE 8080

# Use custom entrypoint
ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]

