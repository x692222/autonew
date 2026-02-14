<script setup>
import { computed, inject } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})
const authGuard = computed(() => page.props.auth?.guard ?? '')
const showNav = computed(() => authGuard.value === 'backoffice')

defineProps({
    tab: { type: String, required: true },
})

const tabs = computed(() => {
    const allTabs = [
        { name: 'edit-dealership', label: 'Edit Dealership', permission: 'editDealership', routeName: 'backoffice.dealer-configuration.edit-dealership.show' },
        { name: 'branches', label: 'Branches', permission: 'indexDealershipBranches', routeName: 'backoffice.dealer-configuration.branches.index' },
        { name: 'sales-people', label: 'Sales People', permission: 'indexDealershipSalesPeople', routeName: 'backoffice.dealer-configuration.sales-people.index' },
        { name: 'users', label: 'Platform Users', permission: 'indexDealershipUsers', routeName: 'backoffice.dealer-configuration.users.index' },
    ]

    return allTabs.filter((item) => !!abilities.value[item.permission])
})

const onTabChange = (tabName) => {
    const tab = tabs.value.find((item) => item.name === tabName)
    if (!tab) return
    router.visit(route(tab.routeName))
}
</script>

<template>
    <q-card v-if="showNav" flat bordered class="q-mb-md">
        <q-card-section>
            <q-tabs
                :model-value="tab"
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
