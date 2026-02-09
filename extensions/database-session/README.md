# Database Session

Store Flarum sessions in the database instead of files. Useful for multi-server setups or when file-based sessions are not suitable.

## Installation

The extension is included in Commently. Enable it in **Admin → Extensions**.

## Configuration

1. Add session config to `config.php` (or use `.env`):

```php
'session' => [
    'driver' => getenv('SESSION_DRIVER') ?: 'file',
    'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 120),
    'table' => getenv('SESSION_TABLE') ?: 'sessions',
    'connection' => getenv('SESSION_CONNECTION') ?: null,
],
```

2. Set `SESSION_DRIVER=database` in `.env` to use the database driver.

3. Run migrations:
```bash
php flarum migrate
```

4. Clear cache:
```bash
php flarum cache:clear
```

## Available Drivers

- `file` (default) – sessions stored in `storage/sessions/`
- `database` – sessions stored in the `sessions` table
- `cookie` – sessions stored in cookies
- `array` – in-memory (not suitable for production)
