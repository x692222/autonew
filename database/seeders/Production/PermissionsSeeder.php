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
                'indexSystemUsers',
                'createSystemUsers',
                'editSystemUsers',
                'deleteSystemUsers',
                'resetSystemUserPasswords',
                'assignPermissions',
                'assignDealerPermisssions',
                'indexSystemLocations',
                'createSystemLocations',
                'editSystemLocations',
                'deleteSystemLocations',
                'processSystemRequests',
                'canConfigureSystemSettings',
                'indexSystemQuotations',
                'createSystemQuotations',
                'editSystemQuotations',
                'deleteSystemQuotations',
                'indexSystemInvoices',
                'createSystemInvoices',
                'editSystemInvoices',
                'deleteSystemInvoices',
                'indexDealerships',
                'showDealerships',
                'createDealerships',
                'editDealerships',
                'changeDealershipStatus',
                'deleteDealerships',
                'indexDealershipBranches',
                'editDealershipBranches',
                'deleteDealershipBranches',
                'indexDealershipSalesPeople',
                'createDealershipSalesPeople',
                'editDealershipSalesPeople',
                'deleteDealershipSalesPeople',
                'indexDealershipUsers',
                'createDealershipUsers',
                'editDealershipUsers',
                'deleteDealershipUsers',
                'resetDealershipUserPasswords',
                'indexDealershipStock',
                'showDealershipStock',
                'createDealershipStock',
                'editDealershipStock',
                'deleteDealershipStock',
                'changeStockStatus',
                'manageDealershipLeads',
                'indexDealershipLeads',
                'indexDealershipPipelines',
                'createDealershipPipelines',
                'editDealershipPipelines',
                'deleteDealershipPipelines',
                'indexDealershipPipelineStages',
                'createDealershipPipelineStages',
                'editDealershipPipelineStages',
                'deleteDealershipPipelineStages',
                'indexDealershipQuotations',
                'createDealershipQuotations',
                'editDealershipQuotations',
                'deleteDealershipQuotations',
                'indexDealershipInvoices',
                'createDealershipInvoices',
                'editDealershipInvoices',
                'deleteDealershipInvoices',
                'showNotes',
                'showDealershipNotificationHistory',
                'canConfigureDealershipSettings',
                'showDealershipSettings',
                'showDealershipBillings',
                'showDealershipAuditLogs',
                'impersonateDealershipUser',
            ],
            'dealer' => [
                'assignPermissions',
                'editDealership',
                'indexDealershipBranches',
                'editDealershipBranches',
                'deleteDealershipBranches',
                'indexDealershipSalesPeople',
                'createDealershipSalesPeople',
                'editDealershipSalesPeople',
                'deleteDealershipSalesPeople',
                'indexDealershipUsers',
                'createDealershipUsers',
                'editDealershipUsers',
                'deleteDealershipUsers',
                'resetDealershipUserPasswords',
                'indexStock',
                'showStock',
                'createStock',
                'editStock',
                'deleteStock',
                'manageLeads',
                'indexLeads',
                'indexPipelines',
                'createPipelines',
                'editPipelines',
                'deletePipelines',
                'indexPipelineStages',
                'createPipelineStages',
                'editPipelineStages',
                'deletePipelineStages',
                'indexQuotations',
                'createDealershipQuotations',
                'editDealershipQuotations',
                'deleteDealershipQuotations',
                'indexInvoices',
                'createDealershipInvoices',
                'editDealershipInvoices',
                'deleteDealershipInvoices',
                'showNotes',
                'canConfigureSettings',
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

        foreach ($guardsWithPermissions as $guardName => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::query()->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guardName,
                ], [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
