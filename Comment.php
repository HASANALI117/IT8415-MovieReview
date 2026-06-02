<?php
// Comment.php
// Data Object for dbProj_comments table
// follows course Data Object pattern

require_once 'DBconn.php';

class Comment {

    // member vars to match table columns
    private $comment_id;
    private $movie_id;
    private $user_id;
    private $body;
    private $is_removed;
    private $removed_by;
    private $removed_at;
    private $created_at;

    // getters and setters
    public function getCommentId()  { return $this->comment_id; }
    public function getMovieId()    { return $this->movie_id; }
    public function getUserId()     { return $this->user_id; }
    public function getBody()       { return $this->body; }
    public function getIsRemoved()  { return $this->is_removed; }
    public function getRemovedBy()  { return $this->removed_by; }
    public function getRemovedAt()  { return $this->removed_at; }
    public function getCreatedAt()  { return $this->created_at; }

    public function setMovieId($v)  { $this->movie_id   = (int)$v; }
    public function setUserId($v)   { $this->user_id    = (int)$v; }
    public function setBody($v)     { $this->body       = trim($v); }

    // validation checks
    public function isValid(&$errors) {
        $errors = [];
        if (empty($this->body))
            $errors[] = 'Comment cannot be empty.';
        if (strlen($this->body) > 2000)
            $errors[] = 'Comment cannot exceed 2000 characters.';
        return empty($errors);
    }

    // inserting, save a new comment and return new comment_id or false
    public function addComment() {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO dbProj_comments (movie_id, user_id, body)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $this->movie_id, $this->user_id, $this->body);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return $newId;
        }

        $stmt->close();
        $conn->close();
        return false;
    }

    // selecting all visible comments for a movie, newest first
    public static function getByMovie($movie_id) {
        $conn = getConnection();

        $stmt = $conn->prepare(
            "SELECT c.comment_id, c.body, c.is_removed, c.created_at,
                    u.username, u.user_id
               FROM dbProj_comments c
               JOIN dbProj_users u ON u.user_id = c.user_id
              WHERE c.movie_id = ?
              ORDER BY c.created_at DESC"
        );
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        $stmt->close();
        $conn->close();
        return $comments;
    }

    // selecting all comments for admin, with movie title and username
    public static function getAllComments($search = '') {
        $conn = getConnection();

        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $conn->prepare(
                "SELECT c.comment_id, c.body, c.is_removed, c.created_at,
                        c.movie_id, c.removed_at,
                        u.username, m.title AS movie_title,
                        r.username AS removed_by_name
                   FROM dbProj_comments c
                   JOIN dbProj_users u  ON u.user_id  = c.user_id
                   JOIN dbProj_movies m ON m.movie_id = c.movie_id
                   LEFT JOIN dbProj_users r ON r.user_id = c.removed_by
                  WHERE c.body LIKE ? OR u.username LIKE ? OR m.title LIKE ?
                  ORDER BY c.created_at DESC"
            );
            $stmt->bind_param("sss", $like, $like, $like);
        } else {
            $stmt = $conn->prepare(
                "SELECT c.comment_id, c.body, c.is_removed, c.created_at,
                        c.movie_id, c.removed_at,
                        u.username, m.title AS movie_title,
                        r.username AS removed_by_name
                   FROM dbProj_comments c
                   JOIN dbProj_users u  ON u.user_id  = c.user_id
                   JOIN dbProj_movies m ON m.movie_id = c.movie_id
                   LEFT JOIN dbProj_users r ON r.user_id = c.removed_by
                  ORDER BY c.created_at DESC"
            );
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        $stmt->close();
        $conn->close();
        return $comments;
    }

    // call stored procedure to remove or restore a comment
    public static function moderate($comment_id, $admin_id, $remove) {
        $conn = getConnection();

        $stmt = $conn->prepare("CALL p_moderate_comment(?, ?, ?)");
        $stmt->bind_param("iii", $comment_id, $admin_id, $remove);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

}
?>
