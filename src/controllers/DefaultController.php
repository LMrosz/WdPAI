<?php

declare(strict_types=1);

namespace App\Controller;

final class DefaultController extends AppController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => 'VinoKeep — strona główna',
            'estate' => [
                'name' => 'Domaine VinoKeep',
                'label' => 'Grand Domaine Terroir • od 1887',
                'headline' => 'Tworzone przez słońce, glebę i tradycję',
                'intro' => 'Biodynamiczne roczniki, degustacje w podziemnych piwnicach i autorskie warsztaty '
                    . 'winiarskie na wapiennych zboczach naszej rodzinnej posiadłości.',
                'stats' => [
                    ['value' => '148 ha', 'label' => 'Biodynamiczne uprawy'],
                    ['value' => '1887', 'label' => 'Założenie piwnicy'],
                    ['value' => '12 Crus', 'label' => 'Parcele jednowinnicowe'],
                    ['value' => '4,9 / 5', 'label' => 'Ocena przewodnika sommelierów'],
                ],
            ],
        ]);
    }

    public function notFound(): void
    {
        $this->render('not-found', [
            'pageTitle' => 'Nie znaleziono strony',
        ]);
    }
}