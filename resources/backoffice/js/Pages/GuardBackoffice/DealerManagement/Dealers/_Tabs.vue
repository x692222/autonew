<script setup>
import { computed, inject } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    pageTab: { type: String, required: true },
    dealerId: { type: String, required: true },
})

const tabs = computed(() => {
    const allTabs = [
        { name: 'overview', label: 'Overview', permission: 'showDealerships', routeName: 'backoffice.dealer-management.dealers.overview' },
        { name: 'branches', label: 'Branch Management', permission: 'indexDealershipBranches', routeName: 'backoffice.dealer-management.dealers.branches' },
        { name: 'sales-people', label: 'Sales People', permission: 'indexDealershipSalesPeople', routeName: 'backoffice.dealer-management.dealers.sales-people' },
        { name: 'users', label: 'Dealer Users', permission: 'indexDealershipUsers', routeName: 'backoffice.dealer-management.dealers.users' },
        { name: 'stock', label: 'Stock', permission: 'indexDealershipStock', routeName: 'backoffice.dealer-management.dealers.stock' },
        { name: 'leads', label: 'Leads', permission: 'manageDealershipLeads', routeName: 'backoffice.dealer-management.dealers.leads' },
        { name: 'lead-pipelines', label: 'Lead Pipelines', permission: 'indexDealershipPipelines', routeName: 'backoffice.dealer-management.dealers.lead-pipelines.index' },
        { name: 'lead-stages', label: 'Lead Stages', permission: 'indexDealershipPipelineStages', routeName: 'backoffice.dealer-management.dealers.lead-stages.index' },
        { name: 'quotations', label: 'Quotations', permission: 'indexDealershipQuotations', routeName: 'backoffice.dealer-management.dealers.quotations.index' },
        { name: 'invoices', label: 'Invoices', permission: 'indexDealershipInvoices', routeName: 'backoffice.dealer-management.dealers.invoices.index' },
        { name: 'notification-history', label: 'Notification History', permission: 'showDealershipNotificationHistory', routeName: 'backoffice.dealer-management.dealers.notification-history' },
        { name: 'settings', label: 'Settings', permission: 'canConfigureDealershipSettings', routeName: 'backoffice.dealer-management.dealers.settings' },
        { name: 'billings', label: 'Billings', permission: 'showDealershipBillings', routeName: 'backoffice.dealer-management.dealers.billings' },
        { name: 'audit-log', label: 'Audit Log', permission: 'showDealershipAuditLogs', routeName: 'backoffice.dealer-management.dealers.audit-log' },
    ]

    return allTabs.filter((item) => !!abilities.value[item.permission])
})

const onTabChange = (tabName) => {
    const tab = tabs.value.find((item) => item.name === tabName)
    if (!tab) return

    router.visit(route(tab.routeName, props.dealerId))
}
</script>

<template>
    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <q-tabs
                :model-value="pageTab"
                inline-label
                dense
                class="text-grey-8"
                active-color="primary"
                @update:model-value="onTabChange"
            >
                <q-tab v-for="item in tabs" :key="item.name" :name="item.name" :label="item.label" />
            </q-tabs>
        </q-card-section>
    </q-card>
</template>
