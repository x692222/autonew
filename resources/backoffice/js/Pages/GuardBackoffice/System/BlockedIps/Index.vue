<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Blocked IPs' },
    filters: { type: Object, default: () => ({}) },
    guardOptions: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    records: { type: Object, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)
const search = ref(props.filters?.search ?? '')
const guardName = ref(props.filters?.guard_name ?? null)

const tableColumns = computed(() => ([
    ...(props.columns || []),
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false },
]))

const goFirst = () => tableRef.value?.goFirstPage()

const fetchRows = (p, helpers) => {
    router.get(
        route('backoffice.system.blocked-ips.index'),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            search: search.value || '',
            guard_name: guardName.value || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['records', 'filters', 'guardOptions', 'columns', 'flash'],
            onFinish: () => helpers.finish(),
        }
    )
}

const confirmUnblock = (row) => {
    confirmAction({
        title: 'Unblock IP',
        message: `Unblock ${row.ip_address} (${row.guard_name})?`,
        okLabel: 'Unblock',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: route('backoffice.system.blocked-ips.destroy', row.id),
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
        title="Blocked IP Addresses"
        row-key="id"
        :records="records"
        :columns="tableColumns"
        :fetch="fetchRows"
        initial-sort-by="blocked_at"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 320px">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search IP, guard or country..."
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 180px">
                    <q-select
                        v-model="guardName"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        label="Guard"
                        :options="guardOptions"
                        @update:model-value="goFirst"
                    />
                </div>
            </div>
        </template>

        <template #actions="{ row }">
            <q-btn
                v-if="row.can?.unblock"
                round dense flat icon="lock_open"
                color="primary"
                @click.stop="confirmUnblock(row)"
            >
                <q-tooltip>Unblock</q-tooltip>
            </q-btn>
        </template>
    </PaginatedTable>
</template>
