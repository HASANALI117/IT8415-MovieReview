# MovieReview

**IT8415 — Database Programming 2 · Group Project**

A movie catalogue and review web application built with PHP and MySQL. Visitors can
browse, search, and filter published movies and read reviews. Registered users can
rate movies (out of 10), write reviews, and add/publish their own movies. Admins
manage users, movies, and comments, and generate reports.

## Features

- **Browse & search** — genre filter, keyword search, year range, and sorting, all updated live with AJAX (no page reload).
- **Movie details** — poster, synopsis, director, categories, average rating (out of 10), and user reviews.
- **Ratings & reviews** — a 1–10 star rating plus a written review, posted and deleted via AJAX.
- **Creator panel** — add, edit, publish/unpublish, and delete your own movies, with poster upload and a live "title already exists" check.
- **Admin panel** — manage all movies, moderate comments, manage users, and run reports backed by stored procedures.
- **Cinematic UI** — glassmorphism theme with a full-page ambient background and an animated hero on the home page.

## Tech stack

- **PHP** (procedural pages + a small OOP data layer in `src/`) using `mysqli`
- **MySQL / MariaDB** — InnoDB tables, foreign keys, CHECK constraints, triggers, stored procedures, and a fulltext index
- **HTML / CSS / JavaScript** front end (no framework); Bootstrap is used only for its grid
- Apache-style web root (`public/`) with `.htaccess`

## Project structure

```
config/db.php          Database credentials (the only place they live)
database/              Schema + setup scripts, run in numeric order
  01_create_tables.sql   Tables, indexes, constraints (ratings 1-10)
  02_triggers.sql        Rating-aggregate + audit-log triggers
  03_procedures.sql      Stored procedures (rating, moderation, reports)
  04_security.sql        Least-privilege DB users (local/XAMPP only)
  05_seed_users.sql      Seed user accounts (see Test accounts)
  06_seed_movies.sql     Seed movies + categories
  07_seed_reviews.sql    Seed ratings + reviews
  erd.html               Entity-relationship diagram
src/                   Data classes: Database, User, Auth, Movie, Comment
includes/              Shared header / footer / session / admin-nav partials
public/                Web root — point the web server here
  index.php              Home page
  search.php             Browse / search / filter (AJAX)
  movie/detail.php       Movie detail, ratings, reviews
  auth/                  Register, login, logout, user management
  creator/               Creator dashboard (add / edit movies)
  admin/                 Admin panels (movies, comments, users, reports)
  ajax/                  JSON / AJAX endpoints
  css/ js/ assets/       Stylesheet, scripts, uploaded posters
```

## Setup

### 1. Database

Run the scripts **in numeric order**. On a local server (e.g. XAMPP) you can run them
as `root`; `01_create_tables.sql` creates the `movie_review` database for you.

```
01_create_tables.sql
02_triggers.sql
03_procedures.sql
04_security.sql        (local only — creates the app DB user)
05_seed_users.sql
06_seed_movies.sql
07_seed_reviews.sql
```

You can import each file from phpMyAdmin (SQL tab) or the command line:

```bash
mysql -u root -p < database/01_create_tables.sql
# ...repeat for 02–07
```

**On shared hosting / a lab server** (where you cannot create databases or users):
1. Select your assigned database first.
2. Remove the `DROP DATABASE` / `CREATE DATABASE` / `USE` lines at the top of `01_create_tables.sql`.
3. **Skip `04_security.sql`** (you can't create MySQL users — use your assigned account instead).
4. Put your assigned credentials in `config/db.php`.

> Note: `03_procedures.sql` and `07_seed_reviews.sql` contain stored procedures.
> If phpMyAdmin doesn't honour the `DELIMITER` keyword, create the procedure using
> the **Delimiter** field below the SQL box (set it to `$$`).

### 2. Configuration

Edit [`config/db.php`](config/db.php) with your database host, user, password, and name:

```php
return [
    'host' => 'localhost',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'name' => 'your_db_name',
];
```

### 3. Run

Point a PHP-enabled web server at the **`public/`** directory.

- **PHP built-in server:**
  ```bash
  php -S localhost:8000 -t public
  ```
  Then open <http://localhost:8000>.
- **XAMPP/Apache:** set a virtual host with `DocumentRoot` pointing at `.../public`
  and `AllowOverride All`.

The app also runs correctly from a sub-folder (e.g. a UserDir URL like
`http://host/~user/IT8415-MovieReview/public/`) — it detects its base path automatically.

## Test accounts

Created by [`database/05_seed_users.sql`](database/05_seed_users.sql). Log in at
`/auth/login.php` with the **email** and password:

| Role    | Email              | Password      |
|---------|--------------------|---------------|
| Admin   | `admin@movie.test` | `Admin@123`   |
| Creator | `jane@movie.test`  | `Creator@123` |
| Creator | `marco@movie.test` | `Creator@123` |

## Advanced features (for the report)

| Feature | Where it is used |
|---------|------------------|
| **AJAX** | Live navbar search (`js/search.js` → `ajax/search.php`), the Browse page search/filter (`search.php?ajax=1`), posting/deleting reviews (`movie/detail.php` → `ajax/process_comment.php`, `ajax/delete_comment.php`), and the creator "title exists" check (`creator/ajax_check_title.php`). |
| **Triggers** | `02_triggers.sql` — rating triggers recompute each movie's `avg_rating` and `rating_count`, and write to `dbProj_audit_log`; comment moderation is also audited. |
| **Prepared statements** | All database queries use `mysqli` prepared statements with bound parameters (see `src/Movie.php`, `src/User.php`, `src/Auth.php`), so user input never enters the SQL string. |
| **Stored procedures** | `03_procedures.sql` — `p_rate_movie`, `p_moderate_comment`, `p_set_movie_published`, and the two report procedures used by the admin Reports page. |
| **Advanced UI** | Glassmorphism design system (`css/theme.css`), animated cinematic hero, and responsive movie grid. |

## Notes

- Passwords are stored with `AES_ENCRYPT(<plaintext>, 'your_secret_key')` and verified on
  login (`src/User.php`, `src/Auth.php`).
- All database access goes through a single connection point in `src/Database.php`;
  credentials are read only from `config/db.php`.
- Poster uploads are written to `public/assets/uploads/` — that folder must be writable
  by the web server (set it to `777` on shared hosting).
