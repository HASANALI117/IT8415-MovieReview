

USE movie_review;


DELIMITER $$

-- lets a user rate a movie (adds a new rating, or updates it if they already rated)
CREATE PROCEDURE p_rate_movie (
    IN p_movie_id INT,
    IN p_user_id  INT,
    IN p_stars    TINYINT
)
BEGIN
    IF p_stars < 1 OR p_stars > 10 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stars must be between 1 and 10';
    END IF;

    INSERT INTO dbProj_ratings (movie_id, user_id, stars)
    VALUES (p_movie_id, p_user_id, p_stars)
    ON DUPLICATE KEY UPDATE stars = p_stars;
END$$


-- admin hides a comment (p_remove = 1) or brings it back (p_remove = 0)
CREATE PROCEDURE p_moderate_comment (
    IN p_comment_id INT,
    IN p_admin_id   INT,
    IN p_remove     TINYINT
)
BEGIN
    UPDATE dbProj_comments
       SET is_removed = p_remove,
           removed_by = IF(p_remove = 1, p_admin_id, NULL),
           removed_at = IF(p_remove = 1, CURRENT_TIMESTAMP, NULL)
     WHERE comment_id = p_comment_id;
END$$


-- publishes a movie (p_publish = 1) or puts it back to draft (p_publish = 0)
CREATE PROCEDURE p_set_movie_published (
    IN p_movie_id INT,
    IN p_publish  TINYINT
)
BEGIN
    UPDATE dbProj_movies
       SET is_published = p_publish
     WHERE movie_id = p_movie_id;
END$$


-- adds 1 to a movie's view count (only if it is published)
CREATE PROCEDURE p_increment_views (
    IN p_movie_id INT
)
BEGIN
    UPDATE dbProj_movies
       SET view_count = view_count + 1,
           updated_at = updated_at
     WHERE movie_id = p_movie_id
       AND is_published = 1;
END$$


-- report: best-rated published movies between two dates
CREATE PROCEDURE p_report_top_movies (
    IN p_from  DATE,
    IN p_to    DATE,
    IN p_limit INT
)
BEGIN
    IF p_limit IS NULL OR p_limit < 1 THEN
        SET p_limit = 10;
    END IF;

    SELECT m.movie_id,
           m.title,
           m.release_year,
           m.avg_rating,
           m.rating_count,
           m.view_count,
           DATE(m.published_at) AS published_on,
           u.username           AS creator
      FROM dbProj_movies m
      LEFT JOIN dbProj_users u ON u.user_id = m.created_by
     WHERE m.is_published = 1
       AND m.published_at >= p_from
       AND m.published_at <  p_to + INTERVAL 1 DAY
     ORDER BY m.avg_rating DESC, m.rating_count DESC, m.view_count DESC
     LIMIT p_limit;
END$$


-- report: all movies made by one creator (drafts included)
CREATE PROCEDURE p_report_movies_by_creator (
    IN p_creator_id INT
)
BEGIN
    SELECT m.movie_id,
           m.title,
           m.is_published,
           m.avg_rating,
           m.rating_count,
           m.view_count,
           DATE(m.published_at) AS published_on
      FROM dbProj_movies m
     WHERE m.created_by = p_creator_id
     ORDER BY m.is_published DESC, m.avg_rating DESC, m.view_count DESC;
END$$

DELIMITER ;
