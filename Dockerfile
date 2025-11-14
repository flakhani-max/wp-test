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

# Pre-install ACF Pro using build argument for the license key
ARG ACF_PRO_KEY
RUN if [ -n "$ACF_PRO_KEY" ]; then \
        echo "Downloading ACF Pro during build..."; \
        curl -L -o acf-pro.zip "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_PRO_KEY}" \
        && unzip -q acf-pro.zip \
        && rm acf-pro.zip \
        && chown -R www-data:www-data advanced-custom-fields-pro \
        && echo "ACF Pro pre-installed successfully"; \
    else \
        echo "No ACF_PRO_KEY provided, skipping ACF Pro pre-installation"; \
    fi

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

