-- Script 04 : Database Security (users + privileges)
-- Run after 03_procedures.sql

USE movie_review;

-- app user: can read/write + run procedures, but NOT drop/alter (least privilege)
DROP USER IF EXISTS 'movie_app'@'localhost';
CREATE USER 'movie_app'@'localhost' IDENTIFIED BY 'MovieApp#2024';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON movie_review.* TO 'movie_app'@'localhost';

-- read-only user for reports
DROP USER IF EXISTS 'movie_report'@'localhost';
CREATE USER 'movie_report'@'localhost' IDENTIFIED BY 'Report#2024';
GRANT SELECT ON movie_review.* TO 'movie_report'@'localhost';

FLUSH PRIVILEGES;
