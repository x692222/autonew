<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'System Requests' },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    records: { type: Object, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')
const modal = ref(false)
const selected = ref(null)
const form = ref({ status: '', send_email: false, response: '' })
const { confirmAction } = useConfirmAction(loading)

const tableColumns = computed(() => [
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
])

const goFirst = () => tableRef.value?.goFirstPage()

const fetchRows = (p, helpers) => {
    router.get(
        route('backoffice.system.system-requests.index'),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            search: search.value || '',
            status: status.value || '',
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

const openUpdate = (row) => {
    selected.value = row
    form.value = {
        status: row.status || 'SUBMITTED',
        send_email: false,
        response: row.response || '',
    }
    modal.value = true
}

const saveStatus = () => {
    if (!selected.value) return
    router.patch(route('backoffice.system.system-requests.update', selected.value.id), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            modal.value = false
            selected.value = null
        },
    })
}

const confirmDelete = (row) => {
    confirmAction({
        title: 'Delete System Request',
        message: `Delete "${row.subject}"? This cannot be undone.`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.system.system-requests.destroy', row.id),
        inertia: { preserveState: true },
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
        title="System Requests"
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
                        placeholder="Search subject, message, email..."
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 220px">
                    <q-select
                        v-model="status"
                        dense
                        outlined
                        emit-value
                        map-options
                        clearable
                        label="Status"
                        :options="statusOptions"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.update"
                round dense flat icon="edit_note"
                @click="openUpdate(row)"
            >
                <q-tooltip>Update status</q-tooltip>
            </q-btn>
            <q-btn
                v-if="row.can?.delete"
                round dense flat color="negative" icon="delete"
                @click="confirmDelete(row)"
            >
                <q-tooltip>Delete</q-tooltip>
            </q-btn>
        </template>

        <template #cell-status="{ row }">
            <q-badge
                :color="row.status === 'SUBMITTED' ? 'orange' : row.status === 'APPROVED' ? 'positive' : row.status === 'REJECTED' ? 'negative' : 'primary'"
            >
                {{ row.status }}
            </q-badge>
        </template>
    </PaginatedTable>

    <q-dialog v-model="modal" persistent>
        <q-card style="min-width: 640px; max-width: 95vw;">
            <q-card-section>
                <div class="text-h6">Update Request Status</div>
                <div class="text-caption text-grey-7">{{ selected?.subject }}</div>
            </q-card-section>

            <q-card-section class="q-gutter-md">
                <q-select
                    v-model="form.status"
                    dense
                    outlined
                    emit-value
                    map-options
                    label="Status"
                    :options="statusOptions"
                />
                <q-checkbox v-model="form.send_email" label="Send email update to requester" />
                <q-input
                    v-if="form.send_email"
                    v-model="form.response"
                    type="textarea"
                    autogrow
                    dense
                    outlined
                    label="Response"
                />
            </q-card-section>

            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="modal = false" />
                <q-btn color="primary" label="Save" @click="saveStatus" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

