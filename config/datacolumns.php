<?php

return [
    'backoffice' => [
        'dealers' => [
            'name'                 => [
                'label' => 'Dealer', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'status'               => [
                'label' => 'Status', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],

            // Deferred
            'branches_count'       => [
                'label' => 'Branches', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'active_users_count'   => [
                'label' => 'Users', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'inactive_users_count' => [
                'label' => 'Inactive Users', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],

            'active_stock_count'      => [
                'label' => 'Active Stock', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'inactive_stock_count'    => [
                'label' => 'Inactive Stock', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],
            'total_stock_count'       => [
                'label' => 'Total Stock', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'published_stock_count'   => [
                'label' => 'Published', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'unpublished_stock_count' => [
                'label' => 'Unpublished', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],
        ],
        'branches' => [
            'dealer_name' => [
                'label' => 'Dealer', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'dealer_status' => [
                'label' => 'Dealer Status', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],

            'branch_name' => [
                'label' => 'Branch', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'contact_numbers' => [
                'label' => 'Contact', 'is_visible' => false, 'sortable' => true, 'align' => 'left',
            ],
            'display_address' => [
                'label' => 'Address', 'is_visible' => false, 'sortable' => true, 'align' => 'left',
            ],

            // Location columns (branch location)
            'country' => [
                'label' => 'Country', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'state' => [
                'label' => 'State', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'city' => [
                'label' => 'City', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],
            'suburb' => [
                'label' => 'Suburb', 'is_visible' => true, 'sortable' => true, 'align' => 'left',
            ],

            // Dealer users counts (constant regardless filters) - DEFERRED
            'active_users_count' => [
                'label' => 'Active Users', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'inactive_users_count' => [
                'label' => 'Inactive Users', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],

            // Stock counts (relative ONLY to stock type filter) - DEFERRED
            'active_stock_count' => [
                'label' => 'Active Stock', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'inactive_stock_count' => [
                'label' => 'Inactive Stock', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],
            'total_stock_count' => [
                'label' => 'Total Stock', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],
            'published_stock_count' => [
                'label' => 'Published', 'is_visible' => true, 'sortable' => true, 'align' => 'right',
            ],
            'unpublished_stock_count' => [
                'label' => 'Unpublished', 'is_visible' => false, 'sortable' => true, 'align' => 'right',
            ],
        ]
    ],

    'system' => [
        'siteConfiguration' => [
            'edit' => [\App\Models\System\Configuration\SiteConfiguration::class, 'edit'],
        ],
        'systemUser'        => [
            'view_any'       => [\App\Models\System\User::class, 'viewAny'],
            'create'         => [\App\Models\System\User::class, 'create'],
            'update'         => [\App\Models\System\User::class, 'canUpdate'],
            'delete'         => [\App\Models\System\User::class, 'canDelete'],
            'toggle_active'  => [\App\Models\System\User::class, 'canToggleActive'],
            'reset_password' => [\App\Models\System\User::class, 'canResetPassword'],
        ],
    ],
];
