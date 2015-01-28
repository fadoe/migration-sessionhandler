# migration-sessionhandler
Migration Session Handler to convert Symfony session table from version 2.5 to version 2.6 and up

## Installation

Install this package with composer:

```bash
composer.phar require marktjagd/migration-sessionhandler
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
        handler_id: session.handler.pdo
```

```yaml
# services.yml

services:
    session.handler.pdo:
        class:     Marktjagd\Session\Storage\Handler\MigrationSessionHandler
        arguments:
            - @session.handler.legacypdo
            - @session.handler.sessionpdo

    session.handler.legacypdo:
        class:     Symfony\Component\HttpFoundation\Session\Storage\Handler\LegacyPdoSessionHandler
        arguments: [@pdo, %pdo.legacy_session_db_options%]

    session.handler.sessionpdo:
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
