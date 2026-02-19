<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed, inject } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import StockIndexTable from 'bo@/Components/Stock/StockIndexTable.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'stock' },
    isDealerView: { type: Boolean, default: false },
    showDealerFilter: { type: Boolean, default: false },
    routeName: { type: String, required: true },
    createRouteName: { type: String, default: null },
    showRouteName: { type: String, default: null },
    editRouteName: { type: String, default: null },
    destroyRouteName: { type: String, default: null },
    canCreate: { type: Boolean, default: false },
    toggleRouteNames: { type: Object, default: () => ({ activate: null, deactivate: null }) },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
    capabilities: { type: Object, default: () => ({}) },
    dealers: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
    activeStatusOptions: { type: Array, default: () => [] },
    soldStatusOptions: { type: Array, default: () => [] },
    policeClearanceReadyOptions: { type: Array, default: () => [] },
    conditionOptions: { type: Array, default: () => [] },
    colorOptions: { type: Array, default: () => [] },
    isImportOptions: { type: Array, default: () => [] },
    gearboxTypeOptions: { type: Array, default: () => [] },
    driveTypeOptions: { type: Array, default: () => [] },
    fuelTypeOptions: { type: Array, default: () => [] },
    millageRanges: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    models: { type: Array, default: () => [] },
    currencySymbol: { type: String, default: 'N$' },
})

const route = inject('route')
const page = usePage()
const currentUrl = computed(() => page.url || route(props.routeName, [props.dealer.id]))
</script>

<template>
    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <q-btn
            v-if="canCreate && createRouteName"
            color="primary"
            label="Create Stock Record"
            no-wrap
            unelevated
            @click="router.visit(route(createRouteName, [dealer.id, { return_to: currentUrl }]))"
        />
    </div>

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <StockIndexTable
        :public-title="'Stock'"
        subtitle="Stock listing"
        :route-name="routeName"
        :create-route-name="createRouteName"
        :show-create-button="false"
        :show-route-name="showRouteName"
        :edit-route-name="editRouteName"
        :destroy-route-name="destroyRouteName"
        :can-create="canCreate"
        :toggle-route-names="toggleRouteNames"
        :dealer="dealer"
        :is-dealer-view="isDealerView"
        :show-dealer-filter="showDealerFilter"
        :filters="filters"
        :columns="columns"
        :records="records"
        :capabilities="capabilities"
        :dealers="dealers"
        :branches="branches"
        :type-options="typeOptions"
        :active-status-options="activeStatusOptions"
        :sold-status-options="soldStatusOptions"
        :police-clearance-ready-options="policeClearanceReadyOptions"
        :condition-options="conditionOptions"
        :color-options="colorOptions"
        :is-import-options="isImportOptions"
        :gearbox-type-options="gearboxTypeOptions"
        :drive-type-options="driveTypeOptions"
        :fuel-type-options="fuelTypeOptions"
        :millage-ranges="millageRanges"
        :makes="makes"
        :models="models"
        :currency-symbol="currencySymbol"
    />
</template>
