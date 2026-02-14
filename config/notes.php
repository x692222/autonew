<?php

return [
    /*
     * Keys are what you pass in the route: /notes/{noteableType}/{noteableId}
     * Values are the actual model classes.
     */
    'noteables'  => [
        'dealer'             => \App\Models\Dealer\Dealer::class,
        'dealer-branch'      => \App\Models\Dealer\DealerBranch::class,
        'dealer-sale-person' => \App\Models\Dealer\DealerSalePerson::class,
        'dealer-user'       => \App\Models\Dealer\DealerUser::class,
        'stock'            => \App\Models\Stock\Stock::class,
        'lead'            => \App\Models\Leads\Lead::class,
    ],

    // max note length (match your style)
    'max_length' => 1500,
];
