#!/bin/bash

# Enable error logging
set -e

# Funktion für formatierte Logs
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting container setup..."

# Ensure correct permissions on docker socket
if [ -e /var/run/docker.sock ]; then
    DOCKER_GID=$(stat -c '%g' /var/run/docker.sock)
    log "Docker socket GID: $DOCKER_GID"
    if [ "$DOCKER_GID" != "996" ]; then
        log "Adjusting docker group ID..."
        groupmod -g "$DOCKER_GID" docker
        usermod -aG docker www-data
    fi
fi

# Create and set permissions for config directory
log "Setting up config directory..."
mkdir -p /app/config
chown -R www-data:www-data /app/config
chmod 777 /app/config

# Fix permissions for docker socket
if [ -e /var/run/docker.sock ]; then
    chmod 666 /var/run/docker.sock
fi

# Start Apache
log "Starting Apache..."
exec apache2-foreground 