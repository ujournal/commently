# Commently - API для UJournal на основі Flarum

[Flarum](https://flarum.org/) - фреймворк для створення спільнот. Цей фреймворк пропонує готовий функціонал і API. Його
підключено, як залежність. Репозиторій містить надбудови над фреймворком. Треба сприймати цей репозиторій, як власну збірку
бекенду Flarum, з обраними та власними розширеннями. Фронтенд Flarum не використовується і може існувати лише для тестування
або швидкої зміни налаштувань. Мета - максимально використати Flarum API та готові розширеня для побудови сайта для спільноти
UJournal.

У випадку, коли потрібен новий функціонал, спочатку треба впевнитися, що немає готового розширення. Якщо є, але потребує
модифікації, краще модифікувати. Коли є впевненість, що функціонал дійсно унікальний, тільки тоді прийматися за створення
власного розширення.

Власні розширення:

- `commently/sort-by-likes` - Reddit-like сортування дискуссій
- `commently/upload-dimensions` - Збереження розмірів картинки при завантаженні через `fof/upload`
- `commently/api-docs` - Swagger документація

## Встановлення та запуск

### 1. Клонувати проєкт, встановити залежності

```sh
git clone git@github.com:ujournal/commently.git
cd ./commently
composer install
docker run --name mysql-commently \
  -p 3307:3306 \
  -e MYSQL_ROOT_PASSWORD=commently-pw \
  -e MYSQL_DATABASE=commently_db \
  -d mysql:8.0
```

### 2. Налаштувати `.env`

```sh
cp .env.example .env
```

Відредагувати `.env` щоб дати доступ до БД:

```sh
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=commently_db
DB_USERNAME=root
DB_PASSWORD=commently-pw
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=
DB_STRICT=false
DB_ENGINE=InnoDB
```

### 3. Встановити міграції

```sh
php flarum migrate
```

### 4. Запустити

```sh
php -d display_errors=0 -S localhost:8000 -t ./public
```
