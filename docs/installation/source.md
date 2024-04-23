# From source

## Building the application

::: tip
You can also download the latest build from the [release page](https://github.com/m-thalmann/SecureDAV/releases/latest) on GitHub and skip the building process.
:::

To install from source, you need to clone the repository and install the dependencies.

```bash
git clone https://github.com/m-thalmann/SecureDAV.git
cd SecureDAV
composer install --no-interaction --no-progress --no-dev

npm ci
npm run build
```

You can then remove the `node_modules` directory to save some space.

## Setting up

Then create the `.env` file by copying the `.env.example` file and adjust the settings to your needs (see [Configuration](../configuration.md)).

```bash
cp .env.example .env
```

After that you can generate the application key and run the migrations.

```bash
php artisan key:generate
php artisan migrate
```

Finally, you can deploy the application by pointing your web server to the `public` directory.

::: warning
Checkout the [SabreDAV documentation](https://sabre.io/dav/webservers/) for more information on how to configure your web server for using WebDAV.
:::

## Setting up the cronjob

The backups and some cleanup functions depend on the Laravel scheduler. To run the scheduler, you have to add the following cronjob to your server:

```bash title="/etc/crontab"
# other cronjobs...

* * * * * www-data cd /project/root && php artisan schedule:run >> /dev/null 2>&1
```

## Using Redis (optional)

If you want to use Redis for caching and the queue, you have to adjust some settings in the `.env` file:

```bash{2-3,6-7} title=".env"
...
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# set the host and password for redis
REDIS_HOST=<redis host>
REDIS_PASSWORD=<redis password>
...
```

### Setting up the queue worker

When using Redis for the queue you have to use a queue worker to process the jobs.

You should use a process manager like `supervisord` to keep the worker running. Check the [Laravel documentation](https://laravel.com/docs/10.x/queues#supervisor-configuration) for more information.

You can also start the worker with the following command:

```bash
php artisan queue:work
```

## First run

Check the [Quick start after installation](../introduction.md#quick-start-after-installation) section to get started with the SecureDAV application.

## Important directories

- `storage/app/files`: The directory where all files are stored
- `storage/logs`: The directory where the logs are stored
