# Configuration

There are some options to modify the behavior of the application in the `.env` file. You can copy the `.env.example` file to `.env` and adjust the settings to your needs.

:::warning
When updating the configuration you have to run the following command in order for the changes to take effect:

<CodeGroup>
  <CodeGroupItem title="Docker" active>

```bash
docker exec -it <container name> php artisan config:cache
```

  </CodeGroupItem>
  <CodeGroupItem title="Source">

```bash
php artisan config:cache
```

  </CodeGroupItem>
</CodeGroup>

When using the docker image or a process manager (like `supervisor`) for your queue worker, you have to restart the worker after updating the configuration:

<CodeGroup>
  <CodeGroupItem title="Docker" active>

```bash
docker exec -it <container name> php artisan queue:restart
```

  </CodeGroupItem>
  <CodeGroupItem title="Source">

```bash
php artisan queue:restart
```

  </CodeGroupItem>
</CodeGroup>
:::

**Legend**

|    Symbol     | Description                                                                                                       |
| :-----------: | ----------------------------------------------------------------------------------------------------------------- |
| :exclamation: | Update of the value is required for the application to work properly (when starting from the `.env.example` file) |

## General

| Key                    | Type     | Description                                                                | :exclamation: |
| ---------------------- | -------- | -------------------------------------------------------------------------- | :-----------: |
| `APP_NAME`             | `string` | The name of the application (used in the title e.g.)                       |               |
| `APP_ENV`              | `string` | The environment of the application (e.g. `local`, `production`)            |               |
| `APP_DEBUG`            | `bool`   | Whether the application is in debug mode                                   |               |
| `APP_URL`              | `string` | The URL of the application where it is deployed (used for static links)    | :exclamation: |
| `APP_DEFAULT_TIMEZONE` | `string` | The default timezone of the application (e.g. `UTC`)                       |               |
| `APP_FORCE_HTTPS`      | `bool`   | Whether to force using HTTPS for assets and absolute routes within the app |               |

## Security

| Key                              | Type     | Description                                                                  | :exclamation: |
| -------------------------------- | -------- | ---------------------------------------------------------------------------- | :-----------: |
| `APP_REGISTRATION_ENABLED`       | `bool`   | Whether users can register themselves                                        |               |
| `APP_EMAIL_VERIFICATION_ENABLED` | `bool`   | Whether users have to verify their email address after registration          |               |
| `WEBDAV_CORS_ENABLED`            | `bool`   | Whether CORS requests are allowed to the WebDAV server                       |               |
| `WEBDAV_CORS_ALLOWED_ORIGINS`    | `string` | The allowed origins for CORS requests to the WebDAV server (comma-separated) |               |

## Database

| Key             | Type     | Description                                           | :exclamation: |
| --------------- | -------- | ----------------------------------------------------- | :-----------: |
| `DB_CONNECTION` | `string` | The database connection type (e.g. `mysql`, `sqlite`) | :exclamation: |
| `DB_HOST`       | `string` | The host of the database server                       | :exclamation: |
| `DB_PORT`       | `string` | The port of the database server                       | :exclamation: |
| `DB_DATABASE`   | `string` | The name of the database                              | :exclamation: |
| `DB_USERNAME`   | `string` | The username to connect to the database               | :exclamation: |
| `DB_PASSWORD`   | `string` | The password to connect to the database               | :exclamation: |

::: tip
When using SQLite as the database connection, the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` fields are not required. You however have to create the empty `database/database.sqlite` file manually.
:::

## Mail

| Key                 | Type     | Description                                                               | :exclamation: |
| ------------------- | -------- | ------------------------------------------------------------------------- | :-----------: |
| `MAIL_MAILER`       | `string` | The mailer to use (e.g. `smtp`, `sendmail`, `mailgun`, `ses`, `postmark`) | :exclamation: |
| `MAIL_HOST`         | `string` | The host of the mail server                                               | :exclamation: |
| `MAIL_PORT`         | `string` | The port of the mail server                                               | :exclamation: |
| `MAIL_USERNAME`     | `string` | The username to connect to the mail server                                | :exclamation: |
| `MAIL_PASSWORD`     | `string` | The password to connect to the mail server                                | :exclamation: |
| `MAIL_ENCRYPTION`   | `string` | The encryption to use (e.g. `tls`, `ssl`, `null`)                         | :exclamation: |
| `MAIL_FROM_ADDRESS` | `string` | The email address to send emails from                                     | :exclamation: |
| `MAIL_FROM_NAME`    | `string` | The name to send emails from                                              | :exclamation: |
