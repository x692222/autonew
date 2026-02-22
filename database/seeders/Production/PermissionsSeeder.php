<?php

namespace Database\Seeders\Production;

use App\Models\System\Permission;
use App\Models\System\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Clear pivots first
            DB::table('role_has_permissions')->truncate();
            DB::table('model_has_permissions')->truncate();
            DB::table('model_has_roles')->truncate();

            // Clear core tables
            DB::table('permissions')->truncate();
            DB::table('roles')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $now = Carbon::now();

        $guardsWithRoles = [
            'backoffice' => [
                'superadmin',
            ],
            'dealer' => [
            ],
        ];

        $guardsWithPermissions = [
            'backoffice' => [
                'system_users' => [
                    'indexSystemUsers',
                    'createSystemUsers',
                    'editSystemUsers',
                    'deleteSystemUsers',
                    'resetSystemUserPasswords',
                ],
                'permissions' => [
                    'assignPermissions',
                    'assignDealerPermisssions',
                ],
                'locations' => [
                    'indexSystemLocations',
                    'createSystemLocations',
                    'editSystemLocations',
                    'deleteSystemLocations',
                ],
                'system_requests' => [
                    'processSystemRequests',
                ],
                'security' => [
                    'manageBlockedIps',
                ],
                'system_settings' => [
                    'canConfigureSystemSettings',
                ],
                'system_quotations' => [
                    'indexSystemQuotations',
                    'createSystemQuotations',
                    'editSystemQuotations',
                    'deleteSystemQuotations',
                ],
                'dealership_quotations' => [
                    'indexDealershipQuotations',
                    'createDealershipQuotations',
                    'editDealershipQuotations',
                    'deleteDealershipQuotations',
                ],
                'system_customers' => [
                    'indexSystemCustomers',
                    'createSystemCustomers',
                    'editSystemCustomers',
                    'deleteSystemCustomers',
                ],
                'dealership_customers' => [
                    'indexDealershipCustomers',
                    'createDealershipCustomers',
                    'editDealershipCustomers',
                    'deleteDealershipCustomers',
                ],
                'system_invoices' => [
                    'indexSystemInvoices',
                    'createSystemInvoices',
                    'editSystemInvoices',
                    'deleteSystemInvoices',
                ],
                'dealership_invoices' => [
                    'indexDealershipInvoices',
                    'createDealershipInvoices',
                    'editDealershipInvoices',
                    'deleteDealershipInvoices',
                ],
                'system_payments' => [
                    'indexSystemPayments',
                    'verifySystemPayments',
                    'viewSystemPayments',
                    'createSystemPayments',
                    'editSystemPayments',
                    'deleteSystemPayments',
                ],
                'dealership_payments' => [
                    'indexDealershipPayments',
                    'verifyDealerPayments',
                    'viewDealershipPayments',
                    'createDealershipPayments',
                    'editDealershipPayments',
                    'deleteDealershipPayments',
                ],
                'system_banking_details' => [
                    'indexSystemBankingDetails',
                    'createSystemBankingDetails',
                    'editSystemBankingDetails',
                    'deleteSystemBankingDetails',
                ],
                'dealership_banking_details' => [
                    'indexDealershipBankingDetails',
                    'createDealershipBankingDetails',
                    'editDealershipBankingDetails',
                    'deleteDealershipBankingDetails',
                ],
                'dealerships' => [
                    'indexDealerships',
                    'showDealerships',
                    'createDealerships',
                    'editDealerships',
                    'changeDealershipStatus',
                    'deleteDealerships',
                    'showDealershipSettings',
                    'showDealershipBillings',
                    'showDealershipAuditLogs',
                    'showDealershipNotificationHistory',
                    'canConfigureDealershipSettings',
                    'impersonateDealershipUser',
                ],
                'branches' => [
                    'indexDealershipBranches',
                    'editDealershipBranches',
                    'deleteDealershipBranches',
                ],
                'sales_people' => [
                    'indexDealershipSalesPeople',
                    'createDealershipSalesPeople',
                    'editDealershipSalesPeople',
                    'deleteDealershipSalesPeople',
                ],
                'dealer_users' => [
                    'indexDealershipUsers',
                    'createDealershipUsers',
                    'editDealershipUsers',
                    'deleteDealershipUsers',
                    'resetDealershipUserPasswords',
                ],
                'stock' => [
                    'indexDealershipStock',
                    'showDealershipStock',
                    'createDealershipStock',
                    'editDealershipStock',
                    'deleteDealershipStock',
                    'changeStockStatus',
                ],
                'leads' => [
                    'manageDealershipLeads',
                    'indexDealershipLeads',
                ],
                'pipelines' => [
                    'indexDealershipPipelines',
                    'createDealershipPipelines',
                    'editDealershipPipelines',
                    'deleteDealershipPipelines',
                    'indexDealershipPipelineStages',
                    'createDealershipPipelineStages',
                    'editDealershipPipelineStages',
                    'deleteDealershipPipelineStages',
                ],
                'notes' => [
                    'showNotes',
                ],
            ],
            'dealer' => [
                'permissions' => [
                    'assignPermissions',
                ],
                'dealerships' => [
                    'editDealership',
                    'canConfigureSettings',
                ],
                'branches' => [
                    'indexDealershipBranches',
                    'editDealershipBranches',
                    'deleteDealershipBranches',
                ],
                'sales_people' => [
                    'indexDealershipSalesPeople',
                    'createDealershipSalesPeople',
                    'editDealershipSalesPeople',
                    'deleteDealershipSalesPeople',
                ],
                'dealer_users' => [
                    'indexDealershipUsers',
                    'createDealershipUsers',
                    'editDealershipUsers',
                    'deleteDealershipUsers',
                    'resetDealershipUserPasswords',
                ],
                'stock' => [
                    'indexStock',
                    'showStock',
                    'createStock',
                    'editStock',
                    'deleteStock',
                ],
                'leads' => [
                    'manageLeads',
                    'indexLeads',
                ],
                'pipelines' => [
                    'indexPipelines',
                    'createPipelines',
                    'editPipelines',
                    'deletePipelines',
                    'indexPipelineStages',
                    'createPipelineStages',
                    'editPipelineStages',
                    'deletePipelineStages',
                ],
                'quotations' => [
                    'indexQuotations',
                    'createDealershipQuotations',
                    'editDealershipQuotations',
                    'deleteDealershipQuotations',
                ],
                'customers' => [
                    'indexCustomers',
                    'createCustomers',
                    'editCustomers',
                    'deleteCustomers',
                ],
                'invoices' => [
                    'indexInvoices',
                    'createDealershipInvoices',
                    'editDealershipInvoices',
                    'deleteDealershipInvoices',
                ],
                'payments' => [
                    'indexPayments',
                    'verifyPayments',
                    'viewPayments',
                    'createDealershipPayments',
                    'editDealershipPayments',
                    'deleteDealershipPayments',
                ],
                'banking_details' => [
                    'indexBankingDetails',
                    'createDealershipBankingDetails',
                    'editDealershipBankingDetails',
                    'deleteDealershipBankingDetails',
                ],
                'notes' => [
                    'showNotes',
                ],
            ],
        ];

        foreach ($guardsWithRoles as $guardName => $roles) {
            foreach ($roles as $roleName) {
                Role::query()->firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guardName,
                ], [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($guardsWithPermissions as $guardName => $groupedPermissions) {
            foreach ($groupedPermissions as $group => $permissions) {
                foreach ($permissions as $permissionName) {
                    Permission::query()->firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => $guardName,
                    ], [
                        'group' => $group,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
