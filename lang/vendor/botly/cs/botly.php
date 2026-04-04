<?php

declare(strict_types=1);

return [
    'title' => 'Správa robots.txt',
    'navigation' => [
        'label' => 'Správa robots.txt',
    ],
    'form' => [
        'rules' => [
            'label' => 'Pravidla',
            'fields' => [
                'user_agent' => 'User-Agent',
                'directive' => 'Direktiva',
                'disallow' => 'Zakázat',
                'allow' => 'Povolit',
                'crawl_delay' => 'Prodleva crawlu',
                'clean_param' => 'Clean-param',
                'path' => 'Cesta',
            ],
            'add' => 'Přidat pravidlo',
        ],
        'sitemaps' => [
            'label' => 'Sitemapy',
            'field' => 'URL sitemapy',
            'add' => 'Přidat URL sitemapy',
        ],
        'ai_crawlers' => [
            'label' => 'Blokovat AI crawlery',
        ],
        'submit' => 'Uložit',
        'callout' => [
            'label' => 'Nalezen existující soubor',
            'description' => 'V adresáři public byl nalezen soubor robots.txt. Aby se změny projevily, je potřeba tento soubor smazat nebo přejmenovat.',
            'delete' => 'Smazat soubor',
            'delete_success' => 'Soubor robots.txt byl úspěšně smazán.',
            'rename' => 'Přejmenovat soubor na robots-bak.txt',
            'rename_success' => 'Soubor robots.txt byl úspěšně přejmenován.',
        ],
    ],
    'export' => [
        'label' => 'Exportovat robots.txt',
        'success' => 'Robots.txt byl úspěšně exportován do public/robots.txt.',
    ],
];
