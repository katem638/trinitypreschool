# Trinity Preschool

Local WordPress project for rebuilding the Trinity Preschool website.

## Git Tracking

This repo tracks the portable project code and local DDEV config:

- `.ddev/config.yaml`
- `AGENTS.md`, `.gitignore`, and this `README.md`
- `scripts/`
- `wp-content/themes/trinity-preschool/`
- Installed non-core plugins in `wp-content/plugins/`

This repo intentionally does not track WordPress core, `wp-config*.php`, uploads,
cache files, DDEV database snapshots, or database dumps. Those are runtime or
machine-specific files and should be recreated or transferred separately.

## Start

```bash
cd "/Users/mstefanko/Documents/New project/trinity-preschool"
ddev start
```

Visit:

- Site: `http://127.0.0.1:33001`
- Admin: `http://127.0.0.1:33001/wp-admin/`

Local admin credentials are `admin` / `admin`.

## Notes

- This project uses DDEV with OrbStack.
- WordPress core is installed locally but ignored by git.
- Custom site work belongs in `wp-content/themes/trinity-preschool/`.
- Port 80 was already in use when this project was created, so DDEV is pinned to router HTTP port `33000` and direct local web port `33001`.
- `mkcert -install` created a local CA, but trusting it in the macOS keychain needs an interactive sudo prompt. Until that is completed, the HTTPS URL may show a browser trust warning.

## Move To A New Laptop

On this laptop, create transfer files that are not committed:

```bash
mkdir -p .site-transfer
ddev export-db --file=.site-transfer/trinity-preschool-db.sql.gz
tar -czf .site-transfer/trinity-preschool-uploads.tar.gz -C wp-content uploads
```

Move the two files in `.site-transfer/` to the new laptop by AirDrop, external
drive, or another private file transfer method.

On the new laptop:

```bash
git clone git@github.com:katem638/trinitypreschool.git
cd trinitypreschool
ddev start
ddev wp core download --version=7.0 --force
ddev restart
ddev import-db --file=/path/to/trinity-preschool-db.sql.gz
tar -xzf /path/to/trinity-preschool-uploads.tar.gz -C wp-content
ddev wp option update home 'http://127.0.0.1:33001'
ddev wp option update siteurl 'http://127.0.0.1:33001'
```

If the new laptop uses different local ports or a different DDEV URL, run a
serialized-safe URL replacement after import:

```bash
ddev wp search-replace 'http://127.0.0.1:33001' 'http://NEW-LOCAL-URL' --skip-columns=guid
```
