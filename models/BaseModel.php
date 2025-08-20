<?php
declare(strict_types=1);

/**
 * Base model providing access to the PDO connection
 */

abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}


