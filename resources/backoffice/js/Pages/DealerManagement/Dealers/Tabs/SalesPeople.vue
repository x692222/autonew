<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const route = inject('route')
const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
    publicTitle: { type: String, default: 'Dealer Management' },
    dealer: { type: Object, required: true },
    pageTab: { type: String, default: 'sales-people' },
    filters: { type: Object, default: () => ({}) },
    columns: { type: Array, default: () => [] },
    records: { type: Object, required: true },
    branchOptions: { type: Array, default: () => [] },
})

const tableRef = ref(null)
const loading = ref(false)
const notesRef = ref(null)
const { confirmAction } = useConfirmAction(loading)
const selectedBranch = ref(props.filters?.branch_id ?? null)
const currentUrl = computed(() => page.url || route('backoffice.dealer-management.dealers.sales-people', props.dealer.id))
const canCreateSalesPeople = computed(() => !!abilities.value.createDealershipSalesPeople)

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const fetchRecords = (p, helpers) => {
    router.get(
        route('backoffice.dealer-management.dealers.sales-people', props.dealer.id),
        {
            page: p?.page,
            rowsPerPage: p?.rowsPerPage,
            sortBy: p?.sortBy,
            descending: p?.descending,
            branch_id: selectedBranch.value ?? null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'columns', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete Sales Person',
        message: `Are you sure you want to delete ${row.firstname} ${row.lastname}? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.dealer-management.dealers.sales-people.destroy', [props.dealer.id, row.id]),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <q-btn
            v-if="canCreateSalesPeople"
            color="primary"
            label="Create Sales Person"
            no-wrap
            unelevated
            @click="router.visit(route('backoffice.dealer-management.dealers.sales-people.create', { dealer: dealer.id, return_to: currentUrl }))"
        />
    </div>

    <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

    <PaginatedTable
        ref="tableRef"
        title="Sales People"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRecords"
        initial-sort-by="lastname"
        :initial-descending="false"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 240px;">
                    <q-select
                        v-model="selectedBranch"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :options="branchOptions"
                        label="Branch"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.show_notes"
                round
                dense
                flat
                icon="sticky_note_2"
                @click.stop="notesRef?.open(row)"
            >
                <q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>{{ row.notes_count }}</q-badge>
                <q-tooltip>Notes</q-tooltip>
            </q-btn>

            <q-btn
                v-if="row.can?.edit"
                round
                dense
                flat
                icon="edit"
                @click="router.visit(route('backoffice.dealer-management.dealers.sales-people.edit', { dealer: dealer.id, salesPerson: row.id, return_to: currentUrl }))"
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

    <NotesHost
        ref="notesRef"
        noteable-type="dealer-sale-person"
        :title-fn="row => `${row.firstname || ''} ${row.lastname || ''}`.trim() || 'Sales Person'"
        @closed="refreshCurrent"
    />
</template>
