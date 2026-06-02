-- Script 05 : Test / seed users
-- Run after 04_security.sql (run as root or a DDL-capable account, since it inserts directly).
--
-- Passwords are stored exactly the way the app stores them:
--   AES_ENCRYPT(<plaintext>, 'your_secret_key')   -- see src/User.php / src/Auth.php
-- so these accounts can be used to log in immediately at /auth/login.php.
--
-- Plain-text passwords for these test accounts (DO NOT use in production):
--   admin@movie.test     -> Admin@123
--   jane@movie.test      -> Creator@123
--   marco@movie.test     -> Creator@123
--   bob@movie.test       -> Viewer@123   (creator)
--   alice@movie.test     -> Viewer@123   (creator)
--   sam@movie.test       -> Viewer@123   (creator, inactive account, for testing is_active = 0)

USE movie_review;

-- Remove any previous copies of these test accounts so the script is re-runnable.
DELETE FROM dbProj_users
 WHERE email IN (
   'admin@movie.test',
   'jane@movie.test',
   'marco@movie.test',
   'bob@movie.test',
   'alice@movie.test',
   'sam@movie.test'
 );

INSERT INTO dbProj_users (username, email, password_hash, role, is_active) VALUES
  ('admin',     'admin@movie.test', AES_ENCRYPT('Admin@123',   'your_secret_key'), 'admin',   1),
  ('jane_doe',  'jane@movie.test',  AES_ENCRYPT('Creator@123', 'your_secret_key'), 'creator', 1),
  ('marco_p',   'marco@movie.test', AES_ENCRYPT('Creator@123', 'your_secret_key'), 'creator', 1),
  ('bob_v',     'bob@movie.test',   AES_ENCRYPT('Viewer@123',  'your_secret_key'), 'creator', 1),
  ('alice_v',   'alice@movie.test', AES_ENCRYPT('Viewer@123',  'your_secret_key'), 'creator', 1),
  ('sam_inact', 'sam@movie.test',   AES_ENCRYPT('Viewer@123',  'your_secret_key'), 'creator', 0);

-- Quick check
SELECT user_id, username, email, role, is_active, created_at
  FROM dbProj_users
 ORDER BY user_id;
