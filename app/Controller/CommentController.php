<?php

namespace App\Controller;

use App\Model\Comments;
use Exception;
use Psr\Log\LoggerInterface;
use Respect\Validation\Validator as v;


class CommentController
{
    private LoggerInterface $logger;
    private Comments $comments;

    public function __construct(LoggerInterface $logger, Comments $comments)
    {
        $this->logger = $logger;
        $this->comments = $comments;
    }

    public function getComments(int $articleId): array
    {
        try {
            return $this->comments->getComments($articleId);
        } catch (Exception $e) {
            $this->logger->error('Error getting comments');
            return [];
        }
    }

    public function add(array $vars): void
    {
        $articleId = $vars['id'];
        $name = $_POST['name'] ?? 'anonymous';
        $comment = $_POST['comment'] ?? '';
        $commentValidator = v::notEmpty()->length(1, 500);
        if (!$commentValidator->validate($comment)) {
            echo "Comment cannot be empty or exceed 500 characters";
            return;
        }
        try {
            if ($comment !== '') {
                $this->comments->addComment($articleId, $name, $comment);
                $this->logger->info('Added comment to id: ' . $articleId);
            }
            header('Location: /article/' . $articleId);
        } catch (Exception $e) {
            $this->logger->error('Error adding comment: ' . $e->getMessage());
            echo "Error adding comment";
        }
    }

    public function delete(array $vars): void
    {
        try {
            $id = $vars['id'];
            $this->comments->deleteComment($id);
            $this->logger->info('Comment with id: ' . $id . 'deleted');
            header('Location: /article/' . $vars['article_id']);
        } catch (Exception $e) {
            $this->logger->error('Error deleting comment: ' . $e->getMessage());
            echo "Error deleting comment";
        }
    }

    public function like(array $vars): void
    {
        try {
            $id = $vars['id'];
            $this->comments->like($id);
            $this->logger->info('Comment with id: ' . $id . ' liked');
            header('Location: /article/' . $vars['article_id']);
        } catch (Exception $e) {
            $this->logger->error('Error liking comment: ' . $e->getMessage());
            echo "Error liking comment";
        }
    }
}
