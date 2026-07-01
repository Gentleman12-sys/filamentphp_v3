<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## О проекте

Сервис коротких ссылок на Laravel 12 + Filament 3. Пользователь регистрируется, создаёт короткие ссылки в личном кабинете (отдельная Filament-панель `/dashboard`), переходит по ним — переход фиксируется (IP + время), статистика доступна на странице ссылки.

## Требования

- PHP 8.2+
- Расширения PHP: `intl`, `mbstring`, `pdo_mysql`, `openssl`, `curl`, `fileinfo` (обычно уже включены в XAMPP, кроме `intl` — см. ниже)
- Composer
- MySQL/MariaDB
- Node.js + npm (опционально, только если нужна сборка фронта через Vite)

## Установка (одинаково для всех ОС)

```bash
git clone <repo> filament.v3
cd filament.v3
composer install
cp .env.example .env
php artisan key:generate
```

В `.env` настроить подключение к БД:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=filament_v3
DB_USERNAME=root
DB_PASSWORD=
```

Создать базу `filament_v3` и накатить миграции:

```bash
php artisan migrate
```

Дальше — либо запустить веб-сервер (см. разделы по ОС ниже), либо для быстрой проверки без настройки Apache/Nginx:

```bash
php artisan serve
# приложение на http://127.0.0.1:8000
```

## Эндпоинты

| Метод | URL | Описание | Доступ |
|---|---|---|---|
| GET | `/` | Стартовая страница Laravel | Публично |
| GET | `/{code}` | Редирект по короткой ссылке (например `/abc123`), фиксирует переход (IP + время) | Публично |
| GET | `/admin` | Панель администратора (Filament) | Авторизация |
| GET | `/dashboard` | Личный кабинет пользователя | Авторизация |
| GET | `/dashboard/register` | Регистрация | Публично |
| GET | `/dashboard/login` | Вход | Публично |
| POST | `/dashboard/logout` | Выход | Авторизация |
| GET | `/dashboard/links` | Список своих ссылок | Авторизация |
| GET | `/dashboard/links/create` | Форма создания ссылки | Авторизация |
| GET | `/dashboard/links/{id}` | Просмотр ссылки: статистика переходов (IP, дата/время) и общее число кликов | Авторизация, только владелец |
| GET | `/up` | Health-check Laravel | Публично |

Полный список роутов (включая служебные Livewire/Filament) — `php artisan route:list`.

**Важно:** код короткой ссылки — всегда 6 символов (`[A-Za-z0-9]{6}`), а роут `/{code}` явно исключает `admin`, `dashboard`, `up`, чтобы не перехватывать системные пути.

## Настройка веб-сервера

Ключевое требование для любой ОС: **`DocumentRoot`/корень сайта должен указывать на папку `public/`**, а не на корень проекта. Иначе увидите список файлов вместо приложения, а Livewire-формы (регистрация/логин/создание ссылок) не будут работать — их JS-ассеты и AJAX-эндпоинт (`/livewire/update`) генерируются относительными путями и ломаются при доступе через `.../public` в URL.

### Windows (XAMPP)

1. `C:\xampp\apache\conf\extra\httpd-vhosts.conf` — добавить:

   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/filament.v3/public"
       ServerName filament.test
       <Directory "C:/xampp/htdocs/filament.v3/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   Если в этом файле уже есть другие проекты в `C:/xampp/htdocs`, добавьте перед своим блоком ещё один `<VirtualHost *:80>` с `DocumentRoot "C:/xampp/htdocs"` и `ServerName localhost`, чтобы не сломать доступ к ним — как только появляется хотя бы один `<VirtualHost>` для порта 80, Apache перестаёт использовать общий `DocumentRoot` из `httpd.conf`.

2. `C:\Windows\System32\drivers\etc\hosts` (нужны права администратора) — добавить строку:

   ```
   127.0.0.1 filament.test
   ```

3. `C:\xampp\php\php.ini` — раскомментировать (убрать `;`):

   ```ini
   extension=intl
   ```

4. Полностью **Stop**, затем **Start** Apache в XAMPP Control Panel (именно так, не кнопкой Restart — иначе процесс может не перечитать `httpd-vhosts.conf`).

5. `.env`:

   ```env
   APP_URL=http://filament.test
   ```

6. Открыть `http://filament.test/dashboard/register`.

### macOS

Вариант А — **Laravel Herd** или **Valet** (рекомендуется, не требует ручной настройки Apache):

```bash
valet park   # в папке с проектами
# или
valet link filament
```

Открыть `http://filament.test` (Valet сам добавляет запись в `/etc/hosts` и настраивает Nginx).

Вариант Б — свой Apache/Nginx (Homebrew):

1. Конфиг vhost, например `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf` (Apache) или `/opt/homebrew/etc/nginx/servers/filament.conf` (Nginx) — `DocumentRoot`/`root` на `.../filament.v3/public`, `ServerName filament.test`.
2. `/etc/hosts` — добавить `127.0.0.1 filament.test` (`sudo nano /etc/hosts`).
3. `php.ini` (`php --ini` покажет путь, обычно `/opt/homebrew/etc/php/8.2/php.ini`) — убедиться, что `extension=intl` включён (в сборках из Homebrew обычно уже включено).
4. Перезапустить веб-сервер: `brew services restart httpd` или `brew services restart nginx`.
5. `.env`: `APP_URL=http://filament.test`.

### Linux (Apache/Nginx из пакетного менеджера)

1. Apache — создать `/etc/apache2/sites-available/filament.conf`:

   ```apache
   <VirtualHost *:80>
       DocumentRoot /var/www/filament.v3/public
       ServerName filament.test
       <Directory /var/www/filament.v3/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   ```bash
   sudo a2ensite filament.conf
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

   Nginx — аналогично, `root` в блоке `server {}` указывает на `.../public`, плюс стандартный `try_files $uri $uri/ /index.php?$query_string;` и `fastcgi_pass` на PHP-FPM.

2. `/etc/hosts` — добавить `127.0.0.1 filament.test` (`sudo nano /etc/hosts`).
3. Расширение `intl`: `sudo apt install php8.2-intl` (Debian/Ubuntu) или `sudo dnf install php-intl` (Fedora/RHEL), затем перезапустить PHP-FPM/Apache.
4. `.env`: `APP_URL=http://filament.test`.

## Частые проблемы

- **Видно список файлов вместо приложения** — веб-сервер смотрит на корень проекта, а не на `public/`. См. настройку vhost выше.
- **Открывается заставка XAMPP / редирект на `/dashboard/`, который не имеет отношения к нашему приложению** — это встроенный дашборд самого XAMPP из `C:\xampp\htdocs\dashboard`, значит запрос всё ещё обслуживается старым `DocumentRoot`. Убедитесь, что Apache **полностью перезапущен** после правки `httpd-vhosts.conf` (Stop → Start, не Restart) и что домен из `.env` (`APP_URL`) совпадает с `ServerName` в vhost и со строкой в `hosts`.
- **Кнопка регистрации/логина ничего не делает или падает с `405 Method Not Allowed` на `/dashboard/register`** — значит браузер отправил обычный POST вместо AJAX-запроса Livewire, то есть `livewire.js` не загрузился (проверьте в консоли браузера). Обычно причина — сайт открыт через `.../public` в URL вместо чистого домена с `DocumentRoot` на `public/`.
- **Composer ругается на security advisory при `composer require пакет:"^X.Y"`** — на Windows PowerShell/cmd может "съедать" `^` при вызове `composer.bat`. Надёжнее прописать версию прямо в `composer.json` и выполнить `composer update пакет --with-all-dependencies`.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
