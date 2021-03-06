# Symfony Docker

A [Docker](https://www.docker.com/)-based installer and runtime for the [Symfony](https://symfony.com) web framework, with full [HTTP/2](https://symfony.com/doc/current/weblink.html), HTTP/3 and HTTPS support.

![CI](https://github.com/dunglas/symfony-docker/workflows/CI/badge.svg)

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker-compose build --pull --no-cache` to build fresh images
3. Run `docker-compose up` (the logs will be displayed in the current shell)
4. Enter docker container `docker exec -it image-api_php_1 sh`
5. Run `php bin/console doctrine:fixtures:load` to create test user with username "user" and password "password".

## Preparing autotests
1. Run `php bin/console --env=test doctrine:database:create`
2. Run `php bin/console --env=test doctrine:schema:create`
3. Run `php bin/console --env=test doctrine:fixtures:load`

## Runing autotests
1. Run `php ./bin/phpunit ./tests/`

## API Methods

---

#### Login `GET /login`

params:
* `username` - string
* `password` - string

response:
* `token` - string
---
#### Post Image `POST /image`

headers:
* `X-AUTH-TOKEN` - string. Token from "login" response.

params:
* `image` - string. Base64 encoded image content.

response:
* `url` - string. Full URL to uploaded image, including `{imageId}`.
---
#### Get Image `GET /image/{imageId}`

headers:
* `X-AUTH-TOKEN` - string. Token from "login" response.

response:
* Binary content of the image.

---

## Features

* Production, development and CI ready
* Automatic HTTPS (in dev and in prod!)
* HTTP/2, HTTP/3 and [Preload](https://symfony.com/doc/current/web_link.html) support
* Built-in [Mercure](https://symfony.com/doc/current/mercure.html) hub
* [Vulcain](https://vulcain.rocks) support
* Just 2 services (PHP FPM and Caddy server)
* Super-readable configuration

**Enjoy!**

## Docs

1. [Build options](docs/build.md)
2. [Using Symfony Docker with an existing project](docs/existing-project.md)
3. [Support for extra services](docs/extra-services.md)
4. [Deploying in production](docs/production.md)
5. [Installing Xdebug](docs/xdebug.md)
6. [Using a Makefile](docs/makefile.md)
7. [Troubleshooting](docs/troubleshooting.md)

## Credits

Created by [K??vin Dunglas](https://dunglas.fr), co-maintained by [Maxime Helias](https://twitter.com/maxhelias) and sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).
