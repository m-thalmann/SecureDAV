# Introduction

![SecureDAV Showcase](/assets/showcase.png)

SecureDAV is a secure file storage which also acts as a WebDAV server with specific access controls. It is built as a web application using the [Laravel framework](https://laravel.com/). The application is designed to be self-hosted and can be run on a server of your choice.

<!-- DOC: create doc-pages for features mentioned in home page and link them here -->

## Installation

There are multiple ways to install SecureDAV. The easiest way is to use the [provided Docker image](./installation/docker.md). You can also install it manually by cloning the repository and [building the application from source](./installation/source.md) (or using the latest release build).

For information on requirements, see the [requirements page](./installation/requirements.md).

## Configuration

After installation, you can configure SecureDAV by editing the `.env` file. For more information, see the [configuration page](./configuration.md).

## Quick start after installation

After installation and configuration you should create your admin account by running the following command:

<CodeGroup>
  <CodeGroupItem title="Docker" active>

```bash
docker exec -it <container name> php artisan app:create-admin
```

  </CodeGroupItem>
  <CodeGroupItem title="Source">

```bash
php artisan app:create-admin
```

  </CodeGroupItem>
</CodeGroup>

This will prompt you to enter your admin credentials. After that, you can access the application at your deployed URL and log in with your admin credentials.
