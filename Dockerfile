FROM php:8.2-apache

WORKDIR /var/www/html

# Install required PHP extensions and tools
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    curl \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    && docker-php-ext-install zip

# Docker Installation
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian \
    $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null \
    && apt-get update \
    && apt-get install -y docker-ce-cli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY ./src/ /var/www/html/
COPY src/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create docker group with same GID as host
RUN groupadd -g 996 docker && \
    usermod -aG docker www-data

# Set permissions
RUN mkdir -p /app/config && \
    chown -R www-data:www-data /var/www/html && \
    chown -R www-data:www-data /var/run/apache2 && \
    chown -R www-data:www-data /app/config

EXPOSE 80

# Start Apache in foreground
CMD ["/usr/local/bin/docker-entrypoint.sh"] 