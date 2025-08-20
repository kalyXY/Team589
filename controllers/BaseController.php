<?php
declare(strict_types=1);

/**
 * Base controller providing view rendering
 */

abstract class BaseController
{
    /**
     * Render a view file from views/ directory.
     * $view is relative path without extension, e.g., 'home' or 'students/list'
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!is_readable($viewPath)) {
            throw new RuntimeException('Vue introuvable: ' . $view);
        }

        // Extract variables in a limited scope
        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }

        require $viewPath;
    }
}


