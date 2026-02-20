<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const page = usePage()

const props = defineProps({
    publicTitle: { type: String, default: 'Customers' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'customers' },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    createRoute: { type: String, required: true },
    showRouteName: { type: String, required: true },
    editRouteName: { type: String, required: true },
    deleteRouteName: { type: String, required: true },
    canCreate: { type: Boolean, default: false },
})

const loading = ref(false)
const tableRef = ref(null)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const type = ref(props.filters?.type ?? '')

const currentUrl = computed(() => page.url || props.createRoute)

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const goFirst = () => tableRef.value?.goFirstPage()

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.customers.index', props.dealer?.id)
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.customers.index')
    }

    return route('backoffice.system.customers.index')
})

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, {
        page: pagination?.page,
        rowsPerPage: pagination?.rowsPerPage,
        sortBy: pagination?.sortBy,
        descending: pagination?.descending,
        search: search.value || '',
        type: type.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['records', 'filters', 'columns', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const routeParams = (row) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return { dealer: props.dealer?.id, customer: row.id, return_to: currentUrl.value }
    }

    return { customer: row.id, return_to: currentUrl.value }
}

const showUrl = (row) => route(props.showRouteName, routeParams(row))
const editUrl = (row) => route(props.editRouteName, routeParams(row))
const deleteUrl = (row) => route(props.deleteRouteName, routeParams(row))

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Customer',
        message: `Delete ${row.full_name}?`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: deleteUrl(row),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>

        <q-btn
            v-if="canCreate"
            color="primary"
            label="Create Customer"
            no-wrap
            unelevated
            @click="router.visit(createRoute + (createRoute.includes('?') ? '&' : '?') + 'return_to=' + encodeURIComponent(currentUrl))"
        />
    </div>

    <DealerTabs
        v-if="context?.mode === 'dealer-backoffice' && dealer?.id"
        :page-tab="pageTab"
        :dealer-id="dealer.id"
    />

    <DealerConfigurationNav
        v-if="context?.mode === 'dealer'"
        tab="customers"
    />

    <PaginatedTable
        ref="tableRef"
        title="Customers"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="firstname"
        :initial-descending="false"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 320px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search customer..."
                        :input-attrs="{ autocomplete: 'off' }"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="type"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="[
                            { label: 'INDIVIDUAL', value: 'individual' },
                            { label: 'COMPANY', value: 'company' },
                        ]"
                        label="Type"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.view"
                round
                dense
                flat
                icon="visibility"
                @click="router.visit(showUrl(row))"
            >
                <q-tooltip>View</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.edit"
                round
                dense
                flat
                icon="edit"
                @click="router.visit(editUrl(row))"
            >
                <q-tooltip>Edit</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.delete"
                round
                dense
                flat
                icon="delete"
                color="negative"
                :disable="loading"
                @click.stop="confirmDelete(row)"
            >
                <q-tooltip>Delete</q-tooltip>
            </q-btn>
        </template>
    </PaginatedTable>
</template>
