

USE movie_review;




DELIMITER $$


--  1. ratings recalculate movie popularity + audit  (AFTER INSERT)
CREATE TRIGGER trg_ratings_after_insert
AFTER INSERT ON dbProj_ratings
FOR EACH ROW
BEGIN
    UPDATE dbProj_movies
       SET rating_count = (SELECT COUNT(*)
                             FROM dbProj_ratings
                            WHERE movie_id = NEW.movie_id),
           avg_rating   = COALESCE((SELECT ROUND(AVG(stars), 2)
                                      FROM dbProj_ratings
                                     WHERE movie_id = NEW.movie_id), 0.00),
           updated_at   = updated_at
     WHERE movie_id = NEW.movie_id;

    INSERT INTO dbProj_audit_log (table_name, action, record_id, detail)
    VALUES ('dbProj_ratings', 'INSERT', NEW.rating_id,
            CONCAT('user ', NEW.user_id, ' rated movie ', NEW.movie_id,
                   ' = ', NEW.stars, ' star(s)'));
END$$


--  2. ratings recalculate + audit  (AFTER UPDATE)
CREATE TRIGGER trg_ratings_after_update
AFTER UPDATE ON dbProj_ratings
FOR EACH ROW
BEGIN
    UPDATE dbProj_movies
       SET rating_count = (SELECT COUNT(*)
                             FROM dbProj_ratings
                            WHERE movie_id = NEW.movie_id),
           avg_rating   = COALESCE((SELECT ROUND(AVG(stars), 2)
                                      FROM dbProj_ratings
                                     WHERE movie_id = NEW.movie_id), 0.00),
           updated_at   = updated_at
     WHERE movie_id = NEW.movie_id;

    IF OLD.movie_id <> NEW.movie_id THEN
        UPDATE dbProj_movies
           SET rating_count = (SELECT COUNT(*)
                                 FROM dbProj_ratings
                                WHERE movie_id = OLD.movie_id),
               avg_rating   = COALESCE((SELECT ROUND(AVG(stars), 2)
                                          FROM dbProj_ratings
                                         WHERE movie_id = OLD.movie_id), 0.00),
               updated_at   = updated_at
         WHERE movie_id = OLD.movie_id;
    END IF;

    INSERT INTO dbProj_audit_log (table_name, action, record_id, detail)
    VALUES ('dbProj_ratings', 'UPDATE', NEW.rating_id,
            CONCAT('user ', NEW.user_id, ' changed movie ', NEW.movie_id,
                   ' rating ', OLD.stars, ' -> ', NEW.stars, ' star(s)'));
END$$


--  3. ratings recalculate + audit  (AFTER DELETE)
CREATE TRIGGER trg_ratings_after_delete
AFTER DELETE ON dbProj_ratings
FOR EACH ROW
BEGIN
    UPDATE dbProj_movies
       SET rating_count = (SELECT COUNT(*)
                             FROM dbProj_ratings
                            WHERE movie_id = OLD.movie_id),
           avg_rating   = COALESCE((SELECT ROUND(AVG(stars), 2)
                                      FROM dbProj_ratings
                                     WHERE movie_id = OLD.movie_id), 0.00),
           updated_at   = updated_at
     WHERE movie_id = OLD.movie_id;

    INSERT INTO dbProj_audit_log (table_name, action, record_id, detail)
    VALUES ('dbProj_ratings', 'DELETE', OLD.rating_id,
            CONCAT('removed rating of user ', OLD.user_id,
                   ' on movie ', OLD.movie_id, ' (was ', OLD.stars, ' star(s))'));
END$$


--  4. comment moderation audit  (AFTER UPDATE)
CREATE TRIGGER trg_comments_after_update
AFTER UPDATE ON dbProj_comments
FOR EACH ROW
BEGIN
    IF OLD.is_removed = 0 AND NEW.is_removed = 1 THEN
        INSERT INTO dbProj_audit_log (table_name, action, record_id, detail)
        VALUES ('dbProj_comments', 'UPDATE', NEW.comment_id,
                CONCAT('comment removed by admin ',
                       COALESCE(NEW.removed_by, 0),
                       ' on movie ', NEW.movie_id));
    ELSEIF OLD.is_removed = 1 AND NEW.is_removed = 0 THEN
        INSERT INTO dbProj_audit_log (table_name, action, record_id, detail)
        VALUES ('dbProj_comments', 'UPDATE', NEW.comment_id,
                CONCAT('comment restored on movie ', NEW.movie_id));
    END IF;
END$$


--  5. movie row  (BEFORE INSERT)
CREATE TRIGGER trg_movies_before_insert
BEFORE INSERT ON dbProj_movies
FOR EACH ROW
BEGIN
    IF NEW.is_published = 1 AND NEW.published_at IS NULL THEN
        SET NEW.published_at = CURRENT_TIMESTAMP;
    END IF;
END$$


--  6. movie row  (BEFORE UPDATE)

CREATE TRIGGER trg_movies_before_update
BEFORE UPDATE ON dbProj_movies
FOR EACH ROW
BEGIN
    IF NEW.is_published = 1 AND OLD.is_published = 0 AND NEW.published_at IS NULL THEN
        SET NEW.published_at = CURRENT_TIMESTAMP;
    ELSEIF NEW.is_published = 0 AND OLD.is_published = 1 THEN
        SET NEW.published_at = NULL;
    END IF;
END$$

DELIMITER ;


