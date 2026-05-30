
DROP DATABASE IF EXISTS movie_review;
CREATE DATABASE movie_review
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE movie_review;


CREATE TABLE dbProj_users (
    user_id        INT             NOT NULL AUTO_INCREMENT,
    username       VARCHAR(50)     NOT NULL,
    email          VARCHAR(255)    NOT NULL,
    password_hash  VARCHAR(255)    NOT NULL,
    role           ENUM('viewer','creator','admin') NOT NULL DEFAULT 'viewer',
    is_active      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_users          PRIMARY KEY (user_id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email    UNIQUE (email)
) ENGINE=InnoDB;



CREATE TABLE dbProj_categories (
    category_id  INT          NOT NULL AUTO_INCREMENT,
    name         VARCHAR(50)  NOT NULL,

    CONSTRAINT pk_categories      PRIMARY KEY (category_id),
    CONSTRAINT uq_categories_name UNIQUE (name)
) ENGINE=InnoDB;



CREATE TABLE dbProj_persons (
    person_id   INT          NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(255) NOT NULL,
    birth_year  SMALLINT     NULL,
    bio         TEXT         NULL,

    CONSTRAINT pk_persons PRIMARY KEY (person_id)
) ENGINE=InnoDB;

CREATE INDEX idx_persons_name ON dbProj_persons (full_name);



CREATE TABLE dbProj_movies (
    movie_id          INT             NOT NULL AUTO_INCREMENT,
    title             VARCHAR(255)    NOT NULL,
    tmdb_id           INT             NULL,                     -- external TMDB id (M3 seeder)
    short_description VARCHAR(500)    NULL,                     -- home-page blurb (NULL-able: M3 seeder fix)
    synopsis          TEXT            NULL,                     -- full "View More" text
    release_year      SMALLINT        NULL,
    duration_min      INT             NULL,
    image_url         VARCHAR(500)    NOT NULL,                 -- >=1 image 
    media_url         VARCHAR(500)    NULL,                     -- trailer audio/video
    download_url      VARCHAR(500)    NULL,                     -- optional download
    fanart_bg         VARCHAR(500)    NULL,                     -- cinematic backdrop (M3 seeder)
    fanart_logo       VARCHAR(500)    NULL,                     -- transparent title logo (M3 seeder)
    color_tl          CHAR(6)         NULL,                     -- gradient hex: top-left    (M3 seeder)
    color_tr          CHAR(6)         NULL,                     -- gradient hex: top-right   (M3 seeder)
    color_br          CHAR(6)         NULL,                     -- gradient hex: bottom-right (M3 seeder)
    color_bl          CHAR(6)         NULL,                     -- gradient hex: bottom-left  (M3 seeder)
    created_by        INT             NULL,                     -- owning creator (NULL-able: M3 seeder fix)
    is_published      TINYINT(1)      NOT NULL DEFAULT 0,       -- publish workflow
    published_at      TIMESTAMP       NULL,
    view_count        INT             NOT NULL DEFAULT 0,       -- popularity metric
    avg_rating        DECIMAL(3,2)    NOT NULL DEFAULT 0.00,    -- maintained by trigger
    rating_count      INT             NOT NULL DEFAULT 0,       -- maintained by trigger
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_movies PRIMARY KEY (movie_id),
    CONSTRAINT fk_movies_creator
        FOREIGN KEY (created_by) REFERENCES dbProj_users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_movies_year
        CHECK (release_year IS NULL OR release_year BETWEEN 1888 AND 2100)
) ENGINE=InnoDB;

-- Search

CREATE FULLTEXT INDEX ft_movies_text
    ON dbProj_movies (title, short_description, synopsis);
CREATE INDEX idx_movies_published ON dbProj_movies (is_published, published_at);
CREATE INDEX idx_movies_creator   ON dbProj_movies (created_by);
CREATE INDEX idx_movies_views     ON dbProj_movies (view_count);
CREATE INDEX idx_movies_rating    ON dbProj_movies (avg_rating);
CREATE INDEX idx_movies_year      ON dbProj_movies (release_year);


CREATE TABLE dbProj_movie_categories (
    movie_id    INT NOT NULL,
    category_id INT NOT NULL,

    CONSTRAINT pk_movie_categories PRIMARY KEY (movie_id, category_id),
    CONSTRAINT fk_mcat_movie
        FOREIGN KEY (movie_id) REFERENCES dbProj_movies (movie_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mcat_category
        FOREIGN KEY (category_id) REFERENCES dbProj_categories (category_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;



CREATE TABLE dbProj_movie_cast (
    movie_id        INT NOT NULL,
    person_id       INT NOT NULL,
    role            ENUM('director','actor','writer','producer') NOT NULL,
    character_name  VARCHAR(255) NULL,
    credit_order    INT          NULL,

    CONSTRAINT pk_movie_cast PRIMARY KEY (movie_id, person_id, role),
    CONSTRAINT fk_mc_movie
        FOREIGN KEY (movie_id) REFERENCES dbProj_movies (movie_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mc_person
        FOREIGN KEY (person_id) REFERENCES dbProj_persons (person_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_mc_person ON dbProj_movie_cast (person_id);
CREATE INDEX idx_mc_role   ON dbProj_movie_cast (role);



CREATE TABLE dbProj_ratings (
    rating_id   INT       NOT NULL AUTO_INCREMENT,
    movie_id    INT       NOT NULL,
    user_id     INT       NOT NULL,
    stars       TINYINT   NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT pk_ratings PRIMARY KEY (rating_id),
    CONSTRAINT fk_ratings_movie
        FOREIGN KEY (movie_id) REFERENCES dbProj_movies (movie_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ratings_user
        FOREIGN KEY (user_id) REFERENCES dbProj_users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_ratings_user_movie UNIQUE (movie_id, user_id),
    CONSTRAINT chk_ratings_stars CHECK (stars BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE INDEX idx_ratings_movie ON dbProj_ratings (movie_id);



CREATE TABLE dbProj_comments (
    comment_id  INT          NOT NULL AUTO_INCREMENT,
    movie_id    INT          NOT NULL,
    user_id     INT          NOT NULL,
    body        TEXT         NOT NULL,
    is_removed  TINYINT(1)   NOT NULL DEFAULT 0,   -- admin moderation
    removed_by  INT          NULL,                 -- which admin removed it
    removed_at  TIMESTAMP    NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_comments PRIMARY KEY (comment_id),
    CONSTRAINT fk_comments_movie
        FOREIGN KEY (movie_id) REFERENCES dbProj_movies (movie_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES dbProj_users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comments_remover
        FOREIGN KEY (removed_by) REFERENCES dbProj_users (user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_comments_movie ON dbProj_comments (movie_id);



CREATE TABLE dbProj_audit_log (
    log_id      BIGINT       NOT NULL AUTO_INCREMENT,
    table_name  VARCHAR(64)  NOT NULL,
    action      ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    record_id   INT          NULL,
    detail      VARCHAR(255) NULL,
    changed_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_audit_log PRIMARY KEY (log_id)
) ENGINE=InnoDB;
