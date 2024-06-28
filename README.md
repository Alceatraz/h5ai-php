# h5ai Remaster

This is remastered H5ai-PHP

A stable, fast, security and "do things right" rewrite [H5ai-X](https://github.com/Alceatraz/H5ai-X)

# Docker

> Notice: There is no such image in docker-hub, You need build it on you own.

## Step 0: Prepare

You need those volumes:

- /app/conf - Place all config file
- /app/data - The website content
- /app/logs - Nginx access log and php/fpm log
- /app/h5ai - The h5ai itself (The software)
- /app/temp - The h5ai cache folder (Most picture thumbs)

## Step 1: Setup

```shell
docker run --rm \
--volume /foo/bar/conf:/app/conf \
--volume /foo/bar/data:/app/data \
--volume /foo/bar/logs:/app/logs \
--volume /foo/bar/h5ai:/app/h5ai \
--volume /foo/bar/temp:/app/temp \
zwischenspiell/docker-h5ai setup
```

1. Init all files. `setup` is a script, It simply copies required files to /app/*
2. Modify configs in /foo/bar/conf if you need
3. Put files which you want to share into /foo/bar/data
4. Ensure permission, For default `chown -R nobody:nogroup /foo/bar` is ok

## Step 2: Start

```shell
 docker run --name h5ai \
--detach --restart always \
--publish 80:80  \
--volume /foo/bar/conf:/app/conf \
--volume /foo/bar/data:/app/data \
--volume /foo/bar/logs:/app/logs \
--volume /foo/bar/h5ai:/app/h5ai \
--volume /foo/bar/temp:/app/temp \
zwischenspiell/docker-h5ai
```

- Add extra env like TZ as needed
- Add 443:443 if you want use ssl, Need modify nginx config.
- Enable/Disable index.php processing, Need modify nginx and h5ai config.

This will run docker as service. Enjoy
