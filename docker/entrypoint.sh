#!/bin/sh

composer install

echo "---------------------> Give Database a Headstart"
sleep 3

./flow database:setcharset
./flow doctrine:migrate
./flow user:create --roles Administrator admin password LocalDev Admin
./flow resource:publish

# more robust but takes a while when restarting the container
rm -rf Data/Temporary/

./flow server:run --host=0.0.0.0
