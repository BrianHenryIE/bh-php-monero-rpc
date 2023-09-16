start:
	- docker compose down && docker compose up

shell-php:
	- docker exec -it php bash

shell-monero:
	- docker exec -it monero sh

clear-dep:
	- rm -rf ./vendor/

clear-stagenet:
	- rm -rf ./.docker/monero_data/stagenet/

clear-testnet:
	- rm -rf ./.docker/monero_data/testnet/

clear-monero:
	- rm -rf ./.docker/monero_data/*

clear-all:
	- clear-dep clear-monero
