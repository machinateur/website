# website

The [machinateur.dev](https://machinateur.dev/) website. Somewhere between static and dynamic or something.

## Installation

```shell
git clone git@github.com:machinateur/website.git
cd website
composer install
touch .env.local
```

Make sure to edit your `.env.local` file accordingly.

```shell
symfony serve --port=1312
```

Now open `https://127.0.0.1:1312/` to see if everything is fine.

### Optimization

```shell
./build.sh
./build-coverage.js
```

Let it build the coverage code. Format the output css if desired.

The open a new private window and run an audit.

The lighthouse audits performed with `APP_ENV=dev` will warn about the `x-robots-tag` header
[set automatically by symfony](https://symfony.com/doc/current/reference/configuration/framework.html#disallow-search-engine-index).
The overall performance is better in production environments due to caching.

### Deployment

Make sure to edit your `.env.prod.local` file accordingly.

```shell
export APP_ENV=prod
export APP_DEBUG=0
composer install --no-dev --optimize-autoloader
composer dump-env prod
```

## License

It's MIT.
