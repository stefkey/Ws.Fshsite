#!/bin/sh

composer install

echo "---------------------> Give Database a Headstart"
sleep 3

./flow doctrine:migrate
./flow user:create --roles Administrator admin password LocalDev Admin
./flow resource:publish

./flow server:run --host=0.0.0.0
