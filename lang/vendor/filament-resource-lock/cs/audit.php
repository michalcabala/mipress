<?php

return [
    'navigation_label' => 'Audit zámků',
    'plural_label' => 'Auditní záznamy zámků',
    'locked' => 'Uzamčeno',
    'unlocked' => 'Odemčeno',
    'expired' => 'Vypršelo',
    'force_unlocked' => 'Vynuceně odemčeno',
    'columns' => [
        'action' => 'Akce',
        'lockable_type' => 'Typ záznamu',
        'lockable_id' => 'ID záznamu',
        'user_id' => 'Vlastník zámku',
        'actor_user_id' => 'Provedl',
        'created_at' => 'Čas události',
    ],
    'filters' => [
        'action' => 'Akce',
        'created_at' => 'Rozsah dat',
        'from' => 'Od',
        'until' => 'Do',
    ],
];