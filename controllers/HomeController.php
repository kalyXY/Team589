<?php
declare(strict_types=1);

/**
 * Default Home controller
 */

final class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home', [
            'title' => 'Bienvenue sur Scolaria',
        ]);
    }
}


