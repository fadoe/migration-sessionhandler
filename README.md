# migration-pdo-session-handler
Migration Session Handler to convert Symfony session table from version 2.5 to version 2.6 and up.

## Background

Upgrade to Symfony 2.6 contains a breaking change.
https://github.com/symfony/symfony/blob/v2.6.0/UPGRADE-2.6.md#httpfoundation
https://github.com/symfony/symfony/issues/12833
It is said that you would need to migrate the table manually if you want to keep session information of your users.
With this package there is no need to migrate your session information manually.

## Installation

Install this package with composer:

```bash
composer.phar require marktjagd/migration-pdo-session-handler
```

## Configuration

- Rename old session table. Example for mysql:

```sql
RENAME TABLE session TO session_legacy
```
- configure your Symfony project:

```yaml
# config.yml

framework:
    session:
        handler_id: session.handler.pdo_migration
```

```yaml
# services.yml

services:
    session.handler.pdo_migration:
        class:     Marktjagd\Session\Storage\Handler\MigrationPdoSessionHandler
        arguments:
            - @session.handler.pdo_legacy
            - @session.handler.pdo_session

    session.handler.pdo_legacy:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler
        arguments: [@pdo, %pdo.legacy_session_db_options%]

    session.handler.pdo:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments: [@pdo, %pdo.session_db_options%]

parameters:
    pdo.legacy_session_db_options:
        db_table:    session_legacy
        db_id_col:   session_id
        db_data_col: session_value
        db_time_col: session_time

    pdo.session_db_options:
        db_table:    session
        db_id_col:   session_id
        db_data_col: session_data
        db_lifetime_col: session_lifetime
        db_time_col: session_time
```
