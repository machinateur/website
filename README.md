# website

The [machinateur.dev](https://machinateur.dev/) website. Somewhere between static and dynamic or something.

## Installation

The website can either be run in a classic environment or using docker.

### Classic

```bash
git clone git@github.com:machinateur/website.git
cd website
composer install
touch .env.local
```

Make sure to edit your `.env.local` file accordingly.

```bash
symfony serve --port=1312
```

Now open `https://127.0.0.1:1312/` to see if everything is fine.

### Docker

```bash
git clone git@github.com:machinateur/website.git
cd website
touch .env.local
```

Make sure to edit your `.env.local` file accordingly.

```bash
docker compose build
docker compose up -d
```

Now open `https://127.0.0.1:1312/` to see if everything is fine.

### Docker

```bash
git clone git@github.com:machinateur/website.git
cd website
touch .env.local
```

Make sure to edit your `.env.local` file accordingly.

```bash
docker compose build
docker compose up -d
```

Now open `https://127.0.0.1:1312/` to see if everything is fine.

Going to `http://127.0.0.1:1311/` should result in an HTTPS error, as the redirect to `https://` on that port fails.

### Optimization

The build coverage optimization script should run before each deployment/publishing.

Make sure the project is running at port `1312` before proceeding.

```bash
./build.sh
./build-coverage.sh
```

Let it build the coverage code. Format the output css if desired.

The open a new private window and run an audit.

The lighthouse audits performed with `APP_ENV=dev` will warn about the `x-robots-tag` header
[set automatically by symfony](https://symfony.com/doc/current/reference/configuration/framework.html#disallow-search-engine-index).
The overall performance is better in production environments due to caching.

### Cache

The cache files are located under `var/cache/%kernel.environment%/`. To clear it, execute below command or simply delete
the cache folder.

```bash
php bin/console cache:clear
```

### Log locations and rotations

Logs are stored under `var/log/%kernel.environment%.log`. How many files are kept can be controlled using the
`APP_LOG_ROTATION_COUNT` environment variable, which defaults to `7` (keeping logs from the last 7 days including
today for later reference).

## Deployment

Make sure to edit your `.env.prod.local` file accordingly.

```bash
export APP_ENV=prod
export APP_DEBUG=0
composer install --no-dev --optimize-autoloader
composer dump-env prod
```

### FTP upload files and folders

* `bin/`
* `config/`
* `docker/`
* `public/`
* `res/`
* `src/`
* `templates/`
* `tests/`
* `var/`
* `vendor/`
* `.env.local.php`
* `.gitattributes`
* `.gitignore`
* `.php-version`
* `ads-config.json`
* `build.sh`
* `build-coverage.sh`
* `build-coverage.js`
* `clear.sh`
* `clear-coverage.sh`
* `composer.json`
* `composer.lock`
* `docker-compose.yml`
* `Dockerfile`
* `LICENSE`
* `package.json`
* `package-lock.json`
* `phpunit.xml.dist`
* `README.md`
* `symfony.lock`

*Optional: `.htpasswd`, if auth is enabled in `public/.htaccess`.*

### Credential generation and configuration

The `htpasswd` cli tool is available to add/update credentials.

## License

It's MIT.
