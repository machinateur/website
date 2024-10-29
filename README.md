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

### Sitemap

The sitemap has to be generated before each deployment using the `bin/console sitemap` command:

```
Description:
  Create the sitemap (text format for google).

Usage:
  sitemap [options] [--] <sitemap-path> [<twig-path>]

Arguments:
  sitemap-path                 The path to write the sitemap to.
  twig-path                    The path to scan for content struct.

Options:
      --url-scheme=URL-SCHEME  The url scheme to use. [default: "https"]
      --url-host=URL-HOST      The url host to use. [default: "127.0.0.1"]
      --url-port=URL-PORT      The url port to use. [default: "8000"]
  -f, --filter=FILTER          A filter regex pattern to match against the sitemap urls. (multiple values allowed)
  -h, --help                   Display help for the given command. When no command is given display help for the list command
  -q, --quiet                  Do not output any message
  -V, --version                Display this application version
      --ansi|--no-ansi         Force (or disable --no-ansi) ANSI output
  -n, --no-interaction         Do not ask any interactive question
  -e, --env=ENV                The Environment name. [default: "dev"]
      --no-debug               Switch off debug mode.
  -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Cache

The cache files are located under `var/cache/%kernel.environment%/`. To clear it, execute below command or simply delete
 the cache folder.

```bash
php bin/console cache:clear
```

### Log locations and rotations

Logs are stored under `var/log/%kernel.environment%.log`. The number of log files in rotation is currently set to `0`,
 so they are actually never rotated, but all get their own neat little timestamp.

## Deployment

Make sure to edit your `.env.prod.local` file accordingly.

```bash
export APP_ENV=prod
export APP_DEBUG=0
composer install --no-dev --optimize-autoloader
composer dump-env prod
```

Or use the `deploy.sh` to execute those commands (MINGW64).

### Custom `.htaccess` for production

The provided `.htaccess` has several commented sections, which can (and should) be enabled upon deployment to the
 production environment. While running inside the docker container, these are set by the apache virtual-host
 configuration.

In a shared hosting production environment, as it is currently being used, these sections and directives should be set
 by the `.htaccess` file. It's not the best solution, but still viable for environments without direct configuration
 access.

### FTP upload files and folders

- `bin/`
- `config/`
- `docker/`
- `public/`
- `res/`
- `src/`
- `templates/`
- `tests/`
- `var/`
- `vendor/`
- `.env.local.php`
- `.gitattributes`
- `.gitignore`
- `.php-version`
- `ads-config.json`
- `build.sh`
- `build-coverage.sh`
- `build-coverage.js`
- `clear.sh`
- `clear-coverage.sh`
- `composer.json`
- `composer.lock`
- `docker-compose.yml`
- `Dockerfile`
- `LICENSE`
- `package.json`
- `package-lock.json`
- `phpunit.xml.dist`
- `README.md`
- `symfony.lock`

*Optional: `.htpasswd`, if auth is enabled in `public/.htaccess`.*

### Credential generation and configuration (for testing)

When using the `.htpasswd` approach for testing, the `htpasswd` cli tool is available to add/update credentials. It's
 installed inside the docker container out of the box (since it comes with apache).

It's recommended to place a `.htpasswd-raw` file alongside the normal `.htpasswd` (**both outside of `public/`**), so
 the original credentials for each user won't have to be re-created when lost.

## License

It's MIT.
