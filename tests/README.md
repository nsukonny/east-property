# East Property theme tests

Phase 1: a small integration suite that protects the listing, filter, translation, URL
and cache behaviour of the theme against regressions. The tests describe what the code
does today, not what it should do; behaviour that looks wrong is pinned in the
`known-issues` group and listed below.

## How it runs

PHPUnit 9.6 on the WordPress test library (`wp-phpunit/wp-phpunit` 7.1, the library
still needs PHPUnit 9). Every run:

1. checks that the configured database is a dedicated test database (see *Safety*);
2. drops and recreates its WordPress tables through the library's installer;
3. boots WordPress with the East Property theme, Secure Custom Fields and Polylang
   (Pro when `wp-content/plugins/polylang-pro` exists, the free plugin otherwise);
4. creates English (default, no prefix) and Russian (`/ru/`) with the live Polylang
   settings, before Polylang starts, so the theme registers its routes per language.

Not loaded: mu-plugins (S3 uploads, SMTP, importers), Yoast, Query Monitor, Postmeta In
Tables and any `object-cache.php` drop-in. The object cache is WordPress' runtime cache;
no Redis is needed. Each test runs in a transaction that is rolled back, creates only the
data it needs and passes on its own and in random order.

```
phpunit.xml.dist            suite definition
composer.json / .lock       PHPUnit, wp-phpunit, PHPUnit Polyfills
tests/bootstrap.php         loads WordPress, plugins and the theme
tests/wp-tests-config.php   settings and safety checks
tests/.env.example          local settings template
tests/includes/             TestCase with fixture builders, Polylang languages,
                            recording object cache, stopped-response exception
tests/integration/          the tests
```

## Setup

```bash
composer install
```

Create an empty database for the tests on any MySQL 8 or MariaDB server, then copy
`tests/.env.example` to `tests/.env` (git-ignored) and fill it in. Real environment
variables win over the file.

With Local by WP Engine the server listens on the site socket:

```bash
mysql -uroot -proot --socket="$HOME/Library/Application Support/Local/run/<site-id>/mysql/mysqld.sock" \
  -e 'CREATE DATABASE eastproperty_tests CHARACTER SET utf8mb4'
```

and `tests/.env` carries

```
WP_TESTS_DB_HOST="localhost:/Users/<you>/Library/Application Support/Local/run/<site-id>/mysql/mysqld.sock"
```

The site must be running in Local, since the tests use its MySQL server (not its database).

| Setting | Default | |
|---|---|---|
| `WP_TESTS_DB_NAME` | `eastproperty_tests` | must contain `test` |
| `WP_TESTS_DB_USER` / `WP_TESTS_DB_PASSWORD` | `root` / empty | |
| `WP_TESTS_DB_HOST` | `localhost` | `host`, `host:port` or `localhost:/path/to.sock` |
| `WP_CORE_DIR` | four levels above `tests/` | WordPress root to test against |
| `WP_TESTS_POLYLANG` | Pro if present | `polylang/polylang.php` forces the free plugin, as in CI |

## Commands

```bash
composer test                                        # whole suite
vendor/bin/phpunit tests/integration/QueryUnitsTest.php
vendor/bin/phpunit --filter test_developer_filter_matches_the_developer_and_its_translations
vendor/bin/phpunit --filter 'UnitFilterDevelopersTest::test_respects_the_listing_type'
vendor/bin/phpunit --order-by=random                 # what CI runs; the seed is printed
vendor/bin/phpunit --group known-issues              # only the pinned suspicious behaviour
WP_TESTS_POLYLANG=polylang/polylang.php composer test
composer lint                                        # php -l over the theme
```

## Safety

The installer drops tables, so `tests/wp-tests-config.php` stops the run before it
starts unless all of this holds:

- PHP runs from the command line (the files do nothing over HTTP);
- the database name contains `test`;
- the database is empty, or holds the `ep_tests_marker` table the config creates in an
  empty one. A copy of a real site never has it, whatever its name;
- there is no `wp-content/object-cache.php` (the installer would flush that cache, e.g.
  a shared Redis) and no `wp-content/db.php` other than Query Monitor's.

Never point the tests at the stage or production database; they refuse a database that
already holds a site, but that is the last line, not the plan.

Tables use the live `wp_` prefix inside the test database because some theme queries
name tables directly (see issue 5 below). If such a query ever runs against another
database, it can only reach that database's tables, which the checks above keep empty of
real data.

## Writing tests

- Extend `EastProperty\Tests\TestCase`. Build data with `create_developer()`,
  `create_property()`, `create_unit()`, `create_location()` and
  `link_translations()`; they save ACF fields through `update_field()` with the field
  keys from `acf-json`, so meta is stored exactly as the admin stores it.
- Request filters are read from `$_REQUEST`; set it in the test, it is reset around
  every test.
- `switch_language( 'ru' )` makes Russian the current Polylang language. In URL mode
  Polylang picks the language once per request, so a test that simulates a Russian page
  switches before building links or calling `go_to()`.
- `get_units()`, `get_properties()`, `get_search_tabs_data()` and `get_locations()` keep
  results in static variables for the whole PHP process. Give each test its own
  arguments (listing type, project or author) instead of comparing two calls with the
  same ones; `core_query_units()` and `core_build_search_tabs_data()` have no memo.
- Handlers that end with `exit` (404, redirects) are read by throwing from the
  `status_header` and `wp_redirect` filters, see `RewritesTest::request()`.
- Assert behaviour: returned IDs, fields, URLs, statuses. Not SQL text, join counts or
  private state.

## Known issues

Pinned with `@group known-issues`; when one is fixed on purpose, update its test.

1. `core_query_units()` with `draft => true` (the account lists) returns units in every
   status, trash included.
2. A published unit of a draft project stays in the public listing, while the developer
   filter skips that project.
3. The project type filter compares `meta_value = 'villa'`. Projects saved through the
   ACF checkbox store a serialized array and never match; on the local copy that is
   roughly 1 400 projects of 5 400.
4. A unit without translations loses its project segment when its link is built while
   another language is current (ACF resolves the project through `WP_Query`, which
   Polylang limits to the current language). The short link answers 301, not 404;
   linked translations are rescued by the sibling fallback.

Found while writing the suite, not pinned by a test:

5. `core_query_locations()` and `Property` (minimum price, `class-entity-property.php`)
   name `wp_posts`, `wp_postmeta`… directly instead of `$wpdb->posts`; they break on any
   prefix other than `wp_`.
6. `get_units()` passes `NULL` project IDs of units without a project to
   `_prime_post_caches()`, which raises `_doing_it_wrong` (WordPress 6.3+), and
   `get_permalink( null )` falls back to the current post for `property_url`. The card
   hides that link when there is no project name.
7. `core_query_units()` orders by `pm_boost_score.meta_value` as a string with no tie
   breaker. Most units have no score, so the order within a page, and across pages, is
   up to the database: a unit can repeat or disappear between pages.
8. `core_query_units()` puts `$current_language` into the SQL unescaped. Today it comes
   from Polylang, not from the request.
9. A page past the end reports `total = 0`, not the listing total.
10. The `7+` bedrooms option matches exactly 7 bedrooms.
11. The handover filter reads "before" or "after" from `$_REQUEST['listing_type']`, not
    from the listing type the page passes.
12. `Unit::build_gallery()` adds a `null` image with warnings when a unit has no gallery
    and the `no_image` theme option is empty.
13. `Entities\Broker::__construct( WP_User $wp_user = null )` is deprecated on PHP 8.4+
    (implicit nullable); `composer lint` prints it.

## Benchmarking the filter queries later

The prod incident with `core_unit_filter_developers()` was a query that was fast on
MySQL 8 and correlated per row on MariaDB 11.8. Timing assertions are too noisy for CI,
so correctness stays in the tests and speed is measured on purpose:

- **Query count**: define `SAVEQUERIES`, read `$wpdb->num_queries` before and after the
  call, or count `$wpdb->queries`. A `@group performance` test excluded by default can
  assert that the count stays flat while the fixture grows (no N+1).
- **Duration**: `$wpdb->queries` records the time of each query under `SAVEQUERIES`;
  take the best of several runs, never a single one.
- **Plan**: capture the SQL with the `query` filter and run `EXPLAIN ANALYZE <sql>` on
  MySQL 8 or `ANALYZE FORMAT=JSON <sql>` on MariaDB; look for dependent subqueries and
  full scans of `wp_postmeta`.
- **Scale**: the fixture builders loop cheaply: N developers × M projects × K units in
  both languages, then compare MySQL (local) with MariaDB (CI service).

## CI

`.github/workflows/tests.yml` runs on pull requests to `dev` and `main`, and the stage
deploy (`deploy-stage.yml`, push to `dev`) calls it first: the deploy job starts only
when every check below passes. The production deploy (`deploy-main.yml`) does not call it.

1. **PHP lint**: `php -l` over every theme file.
2. **PHPUnit on MariaDB 11.8**: WordPress 7.1.2, free Polylang 3.8.6 (Pro is not
   downloadable in CI) and Secure Custom Fields 6.9.5 from wordpress.org, tests in
   random order.
3. **Front-end build**: `npm ci` and `npm run build`, as the deploy script does.

PHPCS is not configured in the theme, so there is no coding standards step yet. To stop
a pull request from being merged while the checks are red, mark them as required in the
branch protection of `dev` and `main`.

The server script deploys the head of `origin/dev`, not the commit the run tested: if a
second push lands while the first run is testing, the first deploy ships it untested.
Its own run tests it right after and fails red if it breaks, but the code is already on
stage.

## Not covered yet

Projects listing (`core_query_properties()`, `get_properties()`), filter ranges and
years, the property search tabs, account forms (create, edit, delete, translations),
AJAX handlers, favourites, boosts and cron, importers, SEO and sitemaps, Yoast and Postmeta
In Tables interplay, email, JavaScript and templates.
