# IT8415 — MovieReview

A PHP + MySQL movie-review web application. Visitors (logged-out) can browse and search
published movies; any registered user is a **creator** — they can rate, comment, and add
and publish their own movies; **admins** manage users, movies, comments, and reports.

## Tech stack

- **PHP** (procedural + small OOP data layer in `src/`), `mysqli`
- **MySQL / MariaDB** (InnoDB, triggers, stored procedures, fulltext search)
- Vanilla **HTML/CSS/JS** front end (no framework)
- Apache-style web root with `.htaccess`

## Project structure

```
config/db.php            Single source of DB credentials (app user: movie_app)
database/                Schema + setup scripts (run in numeric order)
  01_create_tables.sql   Tables, indexes, constraints
  02_triggers.sql        Audit + rating-aggregate triggers
  03_procedures.sql      Stored procedures (rating, moderation, reports)
  04_security.sql        Least-privilege DB users (movie_app, movie_report)
  05_seed_users.sql      Test user accounts (see below)
  erd.html               Entity-relationship diagram
src/                     OOP data objects (Database, User, Movie, Comment, Auth)
includes/                Shared header/footer/session/nav partials
public/                  Web root — point your server here
  index.php              Home page
  auth/                  Register, login, logout, profile, user list
  movie/ , search.php    Movie detail + search
  creator/               Creator dashboard (add/edit/publish movies)
  admin/                 Admin panels (users, movies, comments, reports)
  ajax/                  JSON/AJAX endpoints
  css/ js/ assets/       Static assets
```

## Setup

### 1. Database

Run the SQL scripts **in order** as a privileged MySQL account (e.g. `root`):

```bash
mysql -u root -p < database/01_create_tables.sql
mysql -u root -p < database/02_triggers.sql
mysql -u root -p < database/03_procedures.sql
mysql -u root -p < database/04_security.sql
mysql -u root -p < database/05_seed_users.sql   # optional test data
```

Script `04_security.sql` creates the least-privilege application user
`movie_app` that the app connects as. Credentials are read from
[`config/db.php`](config/db.php) — update them there if you change the password.

### 2. Web server

Point a PHP-enabled web server at the `public/` directory.

Quick local run with PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>. (For full URL rewriting / `.htaccess`
behaviour, use Apache with `mod_rewrite` and `AllowOverride All`.)

## Test accounts

Loaded by [`database/05_seed_users.sql`](database/05_seed_users.sql). Log in at
`/auth/login.php` with the **email** and password below.

| Role    | Email              | Password      | Notes                         |
|---------|--------------------|---------------|-------------------------------|
| Admin   | `admin@movie.test` | `Admin@123`   | Full admin panels             |
| Creator | `jane@movie.test`  | `Creator@123` | Can add/publish movies        |
| Creator | `marco@movie.test` | `Creator@123` | Second creator                |

> ⚠️ These are throwaway development credentials. Do not seed them in a
> production deployment.

## Notes

- Passwords are stored with `AES_ENCRYPT(<plaintext>, 'your_secret_key')` and
  verified on login (see [`src/User.php`](src/User.php) and
  [`src/Auth.php`](src/Auth.php)). The seed script uses the same scheme so the
  accounts work out of the box.
- All DB access goes through the single connection authority in
  [`src/Database.php`](src/Database.php); no other file hard-codes credentials.
