# Repository Guidelines

## Project Structure & Module Organization
This repository is a legacy PHP forum application with a mostly flat top-level layout. Entry points such as `index.php`, `thread.php`, `forum.php`, and `admin.php` live in the project root. Shared runtime code is in `lib/`, optional extensions in `ext/`, theme/layout variants in `schemes/` and `tlayouts/`, browser assets in `css/`, `js/`, `images/`, and `numgfx/`, and error pages in `errors/`. Docker files are under `docker/`, committed tests and test support live in `tests/`, and `init.sql` is the starting schema reference.

## Build, Test, and Development Commands
Use Composer for developer tooling and Docker for local PHP runtime.
You have to run every command with `docker compose exec jul-dx-fpm` prefix to execute commands inside the PHP container.

- `docker compose up --build`: starts the nginx and PHP-FPM services defined in `docker-compose.yml`.
- `composer install`: installs the PHP dependencies from `composer.lock`.
- `php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php <paths>`: formats PHP files using the repo rules.
- `php vendor/bin/phpunit --configuration phpunit.xml.dist`: runs the committed PHPUnit suite.
- `composer test`: runs the default PHPUnit command from `composer.json`.
- `composer test:index`: runs the focused index-page PHPUnit subset.
- `php -l path/to/file.php`: quick syntax check for an edited file before opening a PR.

There is no documented bootstrap script or seed data beyond `init.sql`, so avoid adding setup steps to the guide that are not checked into the repo.

## Coding Style & Naming Conventions
Follow `.editorconfig`: tabs for indentation, width 4, UTF-8, trim trailing whitespace, and end files with a newline. For PHP, keep changes compatible with the existing procedural style and favor small, targeted edits over large rewrites. File names are lowercase and hyphenated or plain, matching existing pages such as `admin-threads.php` and `editprofile.php`.

Run PHP CS Fixer before submitting changes. The repo currently uses `.php-cs-fixer.dist.php` with a Symfony-based ruleset plus a few local overrides, so prefer the checked-in config over describing a generic ruleset.

## Testing Guidelines
The repository includes a committed `tests/` directory and `phpunit.xml.dist`. Validate changes with focused syntax checks (`php -l`), the relevant PHPUnit subset when possible, and manual browser testing of affected flows. If you add automated tests, keep them isolated, deterministic, and runnable through the containerized PHPUnit setup.

## Commit & Pull Request Guidelines
Recent history uses short, informal, lowercase commit messages such as `added initialization sql` and `first batch of cs fixing`. Keep commits small, specific, and descriptive of the behavior changed. Pull requests should include a concise summary, note any PHP or schema compatibility impact, link related issues when available, and attach screenshots for UI/theme changes.

## Security & Configuration Tips
Do not commit production secrets, forum-specific protections, or private database dumps. Treat this codebase as legacy and security-sensitive: prefer minimal patches, call out risky areas in `lib/` or auth flows, and note environment assumptions in the PR description.
