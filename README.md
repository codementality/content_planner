# Drupal 9 Skeleton

## Usage

```
composer create-project --stability=dev fruition/drupal-skeleton:9.x [project-dir]
```

## Installing in a non-empty directory

You cannot use `composer create-project` in a non-empty directory.
For example, if you already have a `.git/` subdirectory, then it will not work.

As a work-around, you can create the project in a temporary directory, copy the
files, and then finish initializing:

```
composer create-project --no-install --no-scripts --stability=dev fruition/drupal-skeleton:9.x /tmp/skeleton
mv /tmp/skeleton/* /tmp/skeleton/.??* [project-dir]
cd [project-dir]
composer create-project
```

## Troubleshooting

1. If you get the error message

   > [InvalidArgumentException]
   > Could not find package fruition/drupal-skeleton with version 9.x

   then make sure you have configured Composer to use the Fruition Packagist: see
   [Gaining access and connecting tools together](https://dev-docs.fruition.build/development/sdlc/local.html#gaining-access-and-connecting-tools-together)
   in the Dev Docs.

## Time tracking

When working on this project, you can log your time on the
[FE-1752](https://gsd2.fruition.net/browse/FE-1752) epic in Jira.
Except for very small efforts, it is best to create a new issue in that epic.

## Known opportunities for improvement

-   Code analysis/linting (both PHP and styles)
-   Different CI for skeleton project vs. shipped CI file
-   Install from configuration after create-project.

## Local development with DDev

The project contains configuration for
[DDev](https://ddev.com/).
See [Get Started with DDEV](https://ddev.com/get-started/).

### Custom DDev commands

For a list of all `ddev` commands, run `ddev help`.
For details on a specific command, run `ddev help <command>`.

- `ddev install`
- `ddev theme`

### Troubleshooting DDev

If you have previously run the site using the legacy `.docker-compose.yml`, then
some files and directories may be owned by `www-data`. If you get the message

> Could not write settings file: could not change permissions on file
> .../web/sites/default to make it writeable: chmod .../web/sites/default:
> operation not permitted 

then check ownership and permissions with

```bash
ls -la web/sites/default
```

Fix permissions with `chmod` and owners with `chown`. For example,

```
sudo chown $USER web/sites/default
```

If you have other sites running locally, then you may get this message when
running `ddev start`:

> Failed to start skeleton: Unable to listen on required ports, port 443 is already in use, ...

For example, if you are running another site using Lando, then shut it down with
`lando poseroff` and then try `ddev start` again.

&copy; Fruition Growth LLC.
