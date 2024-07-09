<?php

namespace App\Model;

use SQLite3;

class Comments
{

    private SQLite3 $db;

    public function __construct()
    {
        $this->db = new SQLite3(__DIR__ . '/../../storage/articles.sqlite');
        $this->createTable();
    }

    private function createTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    article_id INTEGER NOT NULL,
    name TEXT DEFAULT 'anonymous',
    comment TEXT NOT NULL,
    likes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id)
)");
    }

    public function getComments(int $articleId): array
    {
        $comment = $this->db->prepare("SELECT * FROM comments WHERE article_id = :article_id");
        $comment->bindValue(':article_id', $articleId, SQLITE3_INTEGER);
        $result = $comment->execute();
        $comments = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $comments[] = $row;
        }
        return $comments;
    }

    public function addComment(int $articleId, string $name, string $comment): void
    {
        $context = $this->db->prepare("INSERT INTO comments (article_id, name, comment, created_at)
VALUES (:article_id, :name, :comment, CURRENT_TIMESTAMP)");
        $context->bindValue(":article_id", $articleId, SQLITE3_INTEGER);
        $context->bindValue(":name", $name ?: 'anonymous');
        $context->bindValue(":comment", $comment);
        $context->execute();
    }

    public function deleteComment(int $id): void
    {
        $comment = $this->db->prepare("DELETE FROM comments WHERE id=:id");
        $comment->bindValue(":id", $id, SQLITE3_INTEGER);
        $comment->execute();
    }

    public function like(int $id): void
    {
        $comment = $this->db->prepare("UPDATE comments SET likes = likes + 1 WHERE id=:id");
        $comment->bindValue(":id" , $id, SQLITE3_INTEGER);
        $comment->execute();
    }
}