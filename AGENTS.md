# Trinity Preschool WordPress Project

This is a local DDEV WordPress project for building the Trinity Preschool site.

## Local Environment

- Project path: `/Users/mstefanko/Documents/New project/trinity-preschool`
- DDEV project: `trinity-preschool`
- Docker provider: OrbStack
- Local URL: `http://127.0.0.1:33001`
- DDEV router URL: `http://trinity-preschool.ddev.site:33000`
- DDEV HTTPS URL: `https://trinity-preschool.ddev.site`
- WP admin: `http://127.0.0.1:33001/wp-admin/`
- Local admin user: `admin`
- Local admin password: `admin`

## Common Commands

Run commands from the project root:

```bash
ddev start
ddev describe
ddev wp theme list
ddev wp plugin list
ddev wp option get home
ddev stop
```

Use `ddev wp ...` instead of a global `wp` command so WP-CLI runs inside the correct container with the correct database credentials.

## Editing Rules

- Do not edit WordPress core files in `wp-admin/`, `wp-includes/`, or root `wp-*.php` files.
- Put custom site code in `wp-content/themes/trinity-preschool/`.
- Treat uploads and database content as local runtime data unless explicitly exporting/importing them.
- Commit `.ddev/config.yaml`, this file, `.gitignore`, and custom theme/plugin code.
- Do not commit generated DDEV internals, uploads, cache files, or bundled WordPress core files.

## Current Theme

The active custom theme is `trinity-preschool`, a lightweight block theme scaffold. Build future templates, parts, patterns, and styles there.

## URL Note

Port 80 was already in use when this project was created, and Chrome could not reliably reach the `*.ddev.site` host. DDEV is pinned to router HTTP port `33000`, the web container is pinned to host port `33001`, and WordPress uses `http://127.0.0.1:33001`.
