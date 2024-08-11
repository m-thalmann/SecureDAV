# Requirements

::: tip
You don't have to think about this when using the [Docker](./docker.md) image.
:::

To run SecureDAV, you need to have the following software installed on your server:

- [PHP](https://www.php.net/) 8.2 or higher
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) 18 or higher
- [NPM](https://www.npmjs.com/)
- A SQL database (e.g. [MySQL](https://www.mysql.com/), [PostgreSQL](https://www.postgresql.org/), [SQLite](https://www.sqlite.org/))
- A web server (e.g. [Apache](https://httpd.apache.org/), [Nginx](https://www.nginx.com/))
- A mail server for sending emails (you can also use a 3rd party service like a Google account or similar)

To check the PHP requirements run the following command at the root of the project:

```bash
composer check-platform-reqs
```

## Optional requirements

Additionally you can install the following software for a better experience:

- [Redis](https://redis.io/) for caching and queueing

When using Redis you have to either install the `PhpRedis` extension or the `Predis` library. The `PhpRedis` extension is recommended for better performance.
See the [Laravel documentation](https://laravel.com/docs/11.x/redis#introduction) for more information.
