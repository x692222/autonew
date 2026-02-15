<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/DealerManagement/Dealers/_Tabs.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const page = usePage()
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
  publicTitle: { type: String, default: 'Dealer Management' },
  dealer: { type: Object, required: true },
  pageTab: { type: String, default: 'lead-stages' },
  filters: { type: Object, default: () => ({}) },
  pipelines: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  records: { type: Object, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const pipelineId = ref(props.filters?.pipeline_id ?? '')
const isTerminal = ref(!!props.filters?.is_terminal)
const isWon = ref(!!props.filters?.is_won)
const isLost = ref(!!props.filters?.is_lost)
const canCreate = computed(() => !!abilities.value.createDealershipPipelineStages)

const tableColumns = computed(() => ([...(props.columns || []), { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }]))

const goFirst = () => tableRef.value?.goFirstPage()

const fetchRecords = (p, helpers) => {
  router.get(route('backoffice.dealer-management.dealers.lead-stages.index', props.dealer.id), {
    page: p?.page,
    rowsPerPage: p?.rowsPerPage,
    sortBy: p?.sortBy,
    descending: p?.descending,
    search: search.value || '',
    pipeline_id: pipelineId.value || '',
    is_terminal: isTerminal.value ? 1 : '',
    is_won: isWon.value ? 1 : '',
    is_lost: isLost.value ? 1 : '',
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['records', 'filters', 'columns', 'pipelines', 'flash'],
    onFinish: () => helpers.finish(),
  })
}
</script>

<template>
  <Head><title>{{ $page.props.appName }}</title></Head>
  <div class="row nowrap justify-between items-center q-mb-md">
    <div><div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div><div class="text-caption text-grey-7">{{ dealer.name }}</div></div>
    <q-btn v-if="canCreate" color="primary" label="Create Stage" no-wrap unelevated @click="router.visit(route('backoffice.dealer-management.dealers.lead-stages.create', dealer.id))" />
  </div>

  <DealerTabs :page-tab="pageTab" :dealer-id="dealer.id" />

  <PaginatedTable ref="tableRef" title="Lead Stages" row-key="id" :records="records" :columns="tableColumns" :fetch="fetchRecords" initial-sort-by="sort_order" :initial-descending="false">
    <template #top-right>
      <div class="row q-col-gutter-sm items-center">
        <div class="col-auto" style="min-width: 220px;"><q-select v-model="pipelineId" dense outlined clearable emit-value map-options label="Pipeline" :options="pipelines || []" @update:model-value="goFirst" /></div>
        <div class="col-auto"><q-checkbox v-model="isTerminal" dense label="Is Terminal" @update:model-value="goFirst" /></div>
        <div class="col-auto"><q-checkbox v-model="isWon" dense label="Is Won" @update:model-value="goFirst" /></div>
        <div class="col-auto"><q-checkbox v-model="isLost" dense label="Is Lost" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 320px;"><q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search stages..." @update:model-value="goFirst" /></div>
      </div>
    </template>

    <template #cell-is_terminal="{ row }"><q-icon :name="row.is_terminal ? 'check_circle' : 'cancel'" :color="row.is_terminal ? 'positive' : 'negative'" size="18px" /></template>
    <template #cell-is_won="{ row }"><q-icon :name="row.is_won ? 'check_circle' : 'cancel'" :color="row.is_won ? 'positive' : 'negative'" size="18px" /></template>
    <template #cell-is_lost="{ row }"><q-icon :name="row.is_lost ? 'check_circle' : 'cancel'" :color="row.is_lost ? 'positive' : 'negative'" size="18px" /></template>

    <template #actions="{ row }">
      <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="router.visit(route('backoffice.dealer-management.dealers.lead-stages.edit', { dealer: dealer.id, leadStage: row.id }))"><q-tooltip>Edit</q-tooltip></q-btn>
      <q-btn v-if="row.can?.delete" round dense flat color="negative" icon="delete" :disable="loading" @click.stop="confirmAction({ title: 'Delete Stage', message: 'Are you sure you want to delete this stage?', okLabel: 'Delete', okColor: 'negative', cancelLabel: 'Cancel', method: 'delete', actionUrl: route('backoffice.dealer-management.dealers.lead-stages.destroy', { dealer: dealer.id, leadStage: row.id }) })"><q-tooltip>Delete</q-tooltip></q-btn>
    </template>
  </PaginatedTable>
</template>
