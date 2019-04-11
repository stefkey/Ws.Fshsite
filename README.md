# Distribution der Frauenselbsthilfe nach Krebs Website (Ws.Fshsite)

## Repo Struktur

* `./app` - Quellcode der Neos Webseite
* `./docker` - Dockerfile und Konfiguration für die lokale Entwicklung
* `./docker_cached` - aktuell die Files aus dem composer cache des Neos Docker Containers
* `./docker-compose.yml` - Konfiguration der für die Entwicklung benötigten Container

## Entwicklung

Für die Entwicklung empfehlen wir die Verwendung von Docker. So vermeiden wir Versionskonflikte auf unterschiedlichen Systemen. Die Entwicklung erfolgt lokal auf dem Rechner des Entwicklers und nicht auf dem Server!

Bitte lade dir den entsprechenden Client herunter [https://www.docker.com/get-started]()

Das `Makefile` stellt alle nötigen Befehle für die Entwicklung zu Verfügung. Mit `make help` kann man sich die Befehle ausgeben lassen.

### Erstes Setup

`make setup` ausführen um die Container zu bauen und zu starten

Dieser Befehl sollte nur einmal am Anfang ausgeführt werden, wenn das Projekt lokal neu aufgesetzt wird.

### Weitere Befehle

* `make start` startet alle Container
* `make stop` stop alle Container
* `make build` nach Änderungen in der `docker-compose.yml` oder dem `docker/Dockerfile`
* `make enter-running-neos` erlaubt es dem Entwickler sich in den laufenden Neos Container zu verbinden

### Hilfreiche Neos-Befehle

Nach dem Ausführen von `make enter-running-neos` können in der Konsole alle wichtige `./flow` Befehle ausgeführt werden

* `./flow help` zeigt alle zur Verfügung stehenden Befehle

## Deployment

TODO

* kopieren via SFTP?
* kopieren via rsync?
* wie passiert der build?
* evtl. via Script
* Dateien in `./app` können deployed werden. Alles mit Docker muss nicht auf den Server geladen werden.
