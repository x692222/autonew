<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Pending Feature Tags' },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    records: { type: Object, required: true },
})

const tableRef = ref(null)
const modal = ref(false)
const selected = ref(null)
const form = ref({ is_approved: true })
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'pending')

const tableColumns = computed(() => [
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
])

const goFirst = () => tableRef.value?.goFirstPage()

const fetchRows = (p, helpers) => {
    router.get(
        route('backoffice.system.pending-feature-tags.index'),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            search: search.value || '',
            status: status.value || 'pending',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'statusOptions', 'columns', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const openReview = (row) => {
    selected.value = row
    form.value = { is_approved: !!row.is_approved }
    modal.value = true
}

const saveReview = () => {
    if (!selected.value) return
    router.patch(route('backoffice.system.pending-feature-tags.update', selected.value.id), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            modal.value = false
            selected.value = null
        },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
        </div>
    </div>

    <PaginatedTable
        ref="tableRef"
        title="Feature Tags"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRows"
        initial-sort-by="created_at"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 320px">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        debounce="700"
                        clearable
                        placeholder="Search tag, type, email..."
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 180px">
                    <q-select
                        v-model="status"
                        dense
                        outlined
                        emit-value
                        map-options
                        label="View"
                        :options="statusOptions"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.update"
                round dense flat icon="fact_check"
                @click="openReview(row)"
            >
                <q-tooltip>Approve / Reject</q-tooltip>
            </q-btn>
        </template>

        <template #cell-is_approved="{ row }">
            <q-badge :color="row.reviewed_at ? (row.is_approved ? 'positive' : 'negative') : 'orange'">
                {{ row.reviewed_at ? (row.is_approved ? 'Approved' : 'Rejected') : 'Pending' }}
            </q-badge>
        </template>
    </PaginatedTable>

    <q-dialog v-model="modal" persistent>
        <q-card style="min-width: 520px; max-width: 95vw;">
            <q-card-section>
                <div class="text-h6">Review Feature Tag</div>
                <div class="text-caption text-grey-7">Tag: {{ selected?.name }}</div>
            </q-card-section>
            <q-card-section>
                <q-checkbox v-model="form.is_approved" label="Approve this feature tag" />
            </q-card-section>
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="modal = false" />
                <q-btn color="primary" label="Save" @click="saveReview" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

