# Dockerfile for Basic WordPress with CTF Landing Pages theme
FROM wordpress:6.6-php8.2-apache

# Install system dependencies, WP-CLI, and Composer
RUN apt-get update && apt-get install -y \
    unzip \
    netcat-openbsd \
    default-mysql-client \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy custom themes
COPY wp-content/themes/ctf-landing-pages /var/www/html/wp-content/themes/ctf-landing-pages

# Copy custom plugins (individually to avoid overwriting plugin directory)
RUN mkdir -p /var/www/html/wp-content/plugins
COPY wp-content/plugins/ctf-custom-plugin /var/www/html/wp-content/plugins/ctf-custom-plugin

# Install Stripe PHP library via Composer
WORKDIR /var/www/html/wp-content/plugins/ctf-custom-plugin
RUN composer install --no-dev --optimize-autoloader

# Pre-install WP Offload Media Lite (so we don't download on every container start)
WORKDIR /var/www/html/wp-content/plugins
RUN curl -L -o amazon-s3-and-cloudfront.zip https://downloads.wordpress.org/plugin/amazon-s3-and-cloudfront.3.2.11.zip \
    && unzip -q amazon-s3-and-cloudfront.zip \
    && rm amazon-s3-and-cloudfront.zip \
    && chown -R www-data:www-data amazon-s3-and-cloudfront

# ACF Pro will be installed at runtime from Secret Manager
# (Can't pre-install during build because license key is secret)

# Reset working directory
WORKDIR /var/www/html

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

