INSERT INTO dbProj_users (username, email, password_hash, role, is_active) VALUES
  ('admin',     'admin@movie.test', AES_ENCRYPT('Admin@123',   'your_secret_key'), 'admin',   1),
  ('jane_doe',  'jane@movie.test',  AES_ENCRYPT('Creator@123', 'your_secret_key'), 'creator', 1),
  ('marco_p',   'marco@movie.test', AES_ENCRYPT('Creator@123', 'your_secret_key'), 'creator', 1);

