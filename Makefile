SHELL=/bin/bash

help:
	cat Makefile

setup:
	make build-clean; \
	make start

build:
	docker-compose build

build-clean:
	docker-compose build --no-cache

start:
	docker-compose up

stop:
	docker-compose stop

enter-running-neos:
	docker-compose exec neos /bin/bash
