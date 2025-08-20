<?php
declare(strict_types=1);

/**
 * Example model to demonstrate a safe prepared query
 */

final class ExampleModel extends BaseModel
{
    /**
     * Return greeting message from database table if exists, fallback otherwise
     */
    public function getWelcomeMessage(string $fallback = 'Bienvenue sur Scolaria'): string
    {
        try {
            $stmt = $this->db->prepare('SELECT message FROM greetings WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', 1, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && isset($row['message'])) {
                return (string) $row['message'];
            }
        } catch (Throwable $e) {
            // Table may not exist yet; log in dev
            if (defined('APP_ENV') && APP_ENV === 'dev') {
                error_log('ExampleModel getWelcomeMessage: ' . $e->getMessage());
            }
        }
        return $fallback;
    }
}


