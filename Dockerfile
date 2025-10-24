# Dockerfile for Basic WordPress with CTF Landing Pages theme
FROM wordpress:6.6-php8.2-apache

# Install system dependencies and WP-CLI
RUN apt-get update && apt-get install -y \
    unzip \
    netcat-openbsd \
    default-mysql-client \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Optionally install Google Cloud SDK (only needed for local development with Secret Manager)
# For Cloud Run deployments, the metadata server is used instead
# Uncomment the following lines if you need gcloud CLI for local development:
#
# RUN echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" | tee -a /etc/apt/sources.list.d/google-cloud-sdk.list \
#     && curl https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key --keyring /usr/share/keyrings/cloud.google.gpg add - \
#     && apt-get update && apt-get install -y google-cloud-cli \
#     && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Copy custom themes
COPY wp-content/themes/ctf-landing-pages /var/www/html/wp-content/themes/ctf-landing-pages

# Copy custom plugins (individually to avoid overwriting plugin directory)
RUN mkdir -p /var/www/html/wp-content/plugins
COPY wp-content/plugins/wp-petition-mailchimp /var/www/html/wp-content/plugins/ctf-custom-plugin

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

