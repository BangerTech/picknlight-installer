#!/bin/bash

# Farben für die Ausgabe
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Starting Pick'n'Light Setup...${NC}"

# Verzeichnisse erstellen
echo "Creating directories..."
mkdir -p src/{steps,css,js,ajax,images,templates} config

# Docker Compose Version prüfen
if ! docker compose version > /dev/null 2>&1; then
    echo -e "${RED}Docker Compose V2 is not installed!${NC}"
    exit 1
fi

# Container stoppen und entfernen falls vorhanden
echo "Cleaning up existing containers..."
docker compose down

# Container neu bauen und starten
echo "Building and starting containers..."
docker compose up --build -d

# Warten auf Container-Start
echo "Waiting for container to start..."
sleep 5

# Container-Status prüfen
if ! docker compose ps | grep -q "picknlight-setup.*running"; then
    echo -e "${RED}Container failed to start!${NC}"
    echo -e "${YELLOW}Initial Container logs:${NC}"
    docker compose logs
    echo -e "\n${RED}Container failed to start. Showing live logs...${NC}"
    docker compose logs -f
    exit 1
fi

# Debug-Informationen
echo -e "\n${GREEN}Debug Information:${NC}"
echo "Container Status:"
docker compose ps
echo -e "\nContainer Details:"
docker inspect picknlight-setup

# Versuche Befehle im Container auszuführen
echo -e "\nTesting Container Access:"
docker compose exec -T setup-wizard bash -c 'id && ls -l /var/run/docker.sock && docker version'

echo -e "\n${GREEN}Setup wizard is running at http://localhost:8080${NC}"
echo -e "${YELLOW}Showing live container logs...${NC}"

# Logs im Hintergrund anzeigen
docker compose logs -f &

# PID des Log-Prozesses speichern
LOG_PID=$!

# Funktion zum Aufräumen beim Beenden
cleanup() {
    echo -e "\n${YELLOW}Stopping log output...${NC}"
    kill $LOG_PID 2>/dev/null
    exit 0
}

# Trap für SIGINT (Ctrl+C) und SIGTERM
trap cleanup SIGINT SIGTERM

# Warten auf Benutzer-Input
echo -e "\nPress Ctrl+C to stop the logs and exit"
wait $LOG_PID 