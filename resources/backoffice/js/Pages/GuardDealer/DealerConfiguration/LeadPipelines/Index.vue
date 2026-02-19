<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({ publicTitle: { type: String, default: 'Configuration' }, dealer: { type: Object, required: true }, filters: { type: Object, default: () => ({}) }, columns: { type: Array, default: () => [] }, records: { type: Object, required: true } })

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)
const search = ref(props.filters?.search ?? '')
const canCreate = computed(() => !!abilities.value.createPipelines)

const tableColumns = computed(() => ([...(props.columns || []), { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }]))
const goFirst = () => tableRef.value?.goFirstPage()

const fetchRecords = (p, helpers) => {
  router.get(route('backoffice.dealer-configuration.lead-pipelines.index'), { page: p?.page, rowsPerPage: p?.rowsPerPage, sortBy: p?.sortBy, descending: p?.descending, search: search.value || '' }, { preserveState: true, preserveScroll: true, replace: true, only: ['records', 'filters', 'columns', 'flash'], onFinish: () => helpers.finish() })
}
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>
  <div class="row nowrap justify-between items-center q-mb-md"><div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div><q-btn v-if="canCreate" color="primary" label="Create Pipeline" no-wrap unelevated @click="router.visit(route('backoffice.dealer-configuration.lead-pipelines.create'))" /></div>
  <DealerConfigurationNav tab="lead-pipelines" />
  <PaginatedTable ref="tableRef" title="Lead Pipelines" row-key="id" :records="records" :columns="tableColumns" :fetch="fetchRecords" initial-sort-by="name" :initial-descending="false">
    <template #top-right><div style="min-width: 320px;"><q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search pipelines..." @update:model-value="goFirst" /></div></template>
    <template #actions="{ row }">
      <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="router.visit(route('backoffice.dealer-configuration.lead-pipelines.edit', row.id))"><q-tooltip>Edit</q-tooltip></q-btn>
      <q-btn v-if="row.can?.delete" round dense flat color="negative" icon="delete" :disable="loading" @click.stop="confirmAction({ title: 'Delete Pipeline', message: 'Are you sure you want to delete this pipeline?', okLabel: 'Delete', okColor: 'negative', cancelLabel: 'Cancel', method: 'delete', actionUrl: route('backoffice.dealer-configuration.lead-pipelines.destroy', row.id) })"><q-tooltip>Delete</q-tooltip></q-btn>
    </template>
  </PaginatedTable>
</template>
