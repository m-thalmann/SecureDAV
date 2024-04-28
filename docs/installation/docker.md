# Using Docker

SecureDAV publishes a Docker image to simplify the deployment of the SecureDAV application. The image is available on Docker Hub at [https://hub.docker.com/repository/docker/mthalmann/securedav](https://hub.docker.com/repository/docker/mthalmann/securedav).

::: warning
There is a known issue on Windows ([WSL issue](https://github.com/microsoft/WSL/issues/8443), [MariaDB issue](https://jira.mariadb.org/browse/MDEV-31486)), where binding the volume for the MariaDB database does not work correctly. In case this happens to you, you have to use a "normal" volume for it by removing the `./` prefix from the database volume path.
:::

## Usage

First you have to create a `.env` file by copying the `docker/.env.example` file and adjust the values to your needs. See the [Configuration](../configuration.md) page for more information.

### With docker-compose

The `docker-compose.yml` file inside of the `docker` directory provides an easy way to start the SecureDAV application with a MariaDB database and a Redis instance for caching.

Simply copy the file to the directory where your `.env` file is located and run the following command:

```bash
docker-compose up -d
```

::: tip NOTE
This will bind the volumes for files, logs and the database to locally created directories. You can adjust the paths in the `docker-compose.yml` file to your needs.
:::

### With the image

::: tip NOTE
The image does not include a database or Redis container. It requires dedicated containers to handle these services.
:::

Create a network:

```bash
docker network create --attachable securedav-net
```

Create a database container:

::: tip NOTE
SecureDAV was currently tests using SQLite and MariaDB. Other databases may work, but support is not guaranteed.
:::

```bash
docker run -d --name database \
    -e MYSQL_RANDOM_ROOT_PASSWORD=true \
    -e MYSQL_DATABASE=securedav \
    -e MYSQL_USER=usersecuredav \
    -e MYSQL_PASSWORD=secret \
    --network=securedav-net \
    -v ./database:/var/lib/mysql:Z \
    mariadb:11
```

Create a Redis container (optional):

```bash
docker run -d --name redis \
    --network=securedav-net \
    redis:alpine \
    redis-server --appendonly yes --requirepass redissecret
```

Create the SecureDAV container:

```bash
docker run -d --name securedav \
    -p 80:80 \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=database \
    -e DB_DATABASE=securedav \
    -e DB_USERNAME=usersecuredav \
    -e DB_PASSWORD=secret \
    -e REDIS_HOST=redis \
    -e REDIS_PASSWORD=redissecret \
    -e CACHE_DRIVER=redis \
    -e QUEUE_CONNECTION=redis \
    --network=securedav-net \
    -v ./files:/var/www/html/storage/app/files \
    -v ./logs:/var/www/html/storage/logs \
    -v ./.env:/var/www/html/.env \
    mthalmann/securedav
```

::: tip NOTE
If you don't want a Redis container, you can remove the environment variables and the `-e CACHE_DRIVER=redis -e QUEUE_CONNECTION=redis` part from the `docker run` command.
:::

## Using a proxy

You can use a reverse proxy in front of the SecureDAV application to handle SSL termination, load balancing, etc. The following example shows how to use a reverse proxy through Apache2:

```apache
<VirtualHost *:443>
    ServerName securedav.example.com

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    ProxyPreserveHost On
    ProxyRequests off
    AllowEncodedSlashes NoDecode
    ProxyPass / http://localhost:8080/ nocanon
    ProxyPassReverse / http://localhost:8080/
</VirtualHost>
```

::: tip IMPORTANT
When using a proxy with SSL in front of the SecureDAV application, you have to adjust the `APP_FORCE_HTTPS` environment variable in the `.env` file to `true`
:::

## First run

Check the [Quick start after installation](../introduction.md#quick-start-after-installation) section to get started with the SecureDAV application.
