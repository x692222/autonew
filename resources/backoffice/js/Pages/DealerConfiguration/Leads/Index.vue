<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, inject, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerConfigurationNav from 'bo@/Pages/DealerConfiguration/_Nav.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const page = usePage()
const route = inject('route')
const abilities = computed(() => page.props.auth?.user?.abilities || {})

const props = defineProps({
  publicTitle: { type: String, default: 'Configuration' },
  dealer: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) },
  columns: { type: Array, default: () => [] },
  records: { type: Object, required: true },
})

const tableRef = ref(null)
const notesRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const currentUrl = computed(() => page.url || route('backoffice.dealer-configuration.leads.index'))
const canManage = computed(() => !!abilities.value.manageLeads)

const search = ref(props.filters?.search ?? '')
const branchId = ref(props.filters?.branch_id ?? '')
const assignedToDealerUserId = ref(props.filters?.assigned_to_dealer_user_id ?? '')
const pipelineId = ref(props.filters?.pipeline_id ?? '')
const stageId = ref(props.filters?.stage_id ?? '')
const leadStatus = ref(props.filters?.lead_status ?? '')
const source = ref(props.filters?.source ?? '')

const stagesOptions = computed(() => {
  if (!pipelineId.value) return props.options?.stages || []
  return (props.options?.stages || []).filter((item) => !item.pipeline_id || String(item.pipeline_id) === String(pipelineId.value))
})

const tableColumns = computed(() => ([...(props.columns || []), { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }]))

const goFirst = () => tableRef.value?.goFirstPage()
const refreshCurrent = () => tableRef.value?.refresh?.()

const onPipelineChange = () => {
  stageId.value = ''
  goFirst()
}

const fetchRecords = (p, helpers) => {
  router.get(
    route('backoffice.dealer-configuration.leads.index'),
    {
      page: p?.page,
      rowsPerPage: p?.rowsPerPage,
      sortBy: p?.sortBy,
      descending: p?.descending,
      search: search.value || '',
      branch_id: branchId.value || '',
      assigned_to_dealer_user_id: assignedToDealerUserId.value || '',
      pipeline_id: pipelineId.value || '',
      stage_id: stageId.value || '',
      lead_status: leadStatus.value || '',
      source: source.value || '',
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['records', 'filters', 'columns', 'options', 'flash'],
      onFinish: () => helpers.finish(),
    }
  )
}

const confirmDelete = (row) => {
  confirmAction({
    title: 'Delete Lead',
    message: 'Are you sure you want to delete this lead?',
    okLabel: 'Delete',
    okColor: 'negative',
    cancelLabel: 'Cancel',
    method: 'delete',
    actionUrl: route('backoffice.dealer-configuration.leads.destroy', { lead: row.id, return_to: currentUrl.value }),
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
      v-if="canManage"
      color="primary"
      label="Create Lead"
      no-wrap
      unelevated
      @click="router.visit(route('backoffice.dealer-configuration.leads.create', { return_to: currentUrl }))"
    />
  </div>

  <DealerConfigurationNav tab="leads" />

  <PaginatedTable
    ref="tableRef"
    title="Leads"
    row-key="id"
    :records="records"
    :columns="tableColumns"
    :fetch="fetchRecords"
    initial-sort-by="created_date"
    :initial-descending="true"
  >
    <template #top-right>
      <div class="row q-col-gutter-sm items-center justify-end">
        <div class="col-auto" style="min-width: 220px;"><q-select v-model="branchId" dense outlined clearable emit-value map-options label="Branch" :options="options.branches || []" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 220px;"><q-select v-model="assignedToDealerUserId" dense outlined clearable emit-value map-options label="Assigned To" :options="options.dealer_users || []" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 220px;"><q-select v-model="pipelineId" dense outlined clearable emit-value map-options label="Pipeline" :options="options.pipelines || []" @update:model-value="onPipelineChange" /></div>
        <div class="col-auto" style="min-width: 220px;"><q-select v-model="stageId" dense outlined clearable emit-value map-options label="Stage" :options="stagesOptions" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 180px;"><q-select v-model="leadStatus" dense outlined clearable emit-value map-options label="Status" :options="options.statuses || []" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 180px;"><q-select v-model="source" dense outlined clearable emit-value map-options label="Source" :options="options.sources || []" @update:model-value="goFirst" /></div>
        <div class="col-auto" style="min-width: 260px;"><q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search leads..." :input-attrs="{ autocomplete: 'off' }" @update:model-value="goFirst" /></div>
      </div>
    </template>

    <template #body-cell-stock_is_live="{ value }">
      <q-td>
        <q-icon v-if="value === null" name="remove" color="grey-6" size="18px" />
        <q-icon v-else :name="value ? 'check_circle' : 'cancel'" :color="value ? 'positive' : 'negative'" size="18px" />
      </q-td>
    </template>

    <template #actions="{ row }">
      <q-btn v-if="row.can?.show_notes" round dense flat icon="sticky_note_2" @click.stop="notesRef?.open(row)">
        <q-badge v-if="row.notes_count > 0" color="red" class="text-weight-bold" floating>{{ row.notes_count }}</q-badge>
        <q-tooltip>Notes</q-tooltip>
      </q-btn>
      <q-btn v-if="row.can?.view" round dense flat icon="visibility" @click="router.visit(route('backoffice.dealer-configuration.leads.overview', { lead: row.id, return_to: currentUrl }))"><q-tooltip>Overview</q-tooltip></q-btn>
      <q-btn v-if="row.can?.edit" round dense flat icon="edit" @click="router.visit(route('backoffice.dealer-configuration.leads.edit', { lead: row.id, return_to: currentUrl }))"><q-tooltip>Edit</q-tooltip></q-btn>
      <q-btn v-if="row.can?.delete" round dense flat icon="delete" color="negative" :disable="loading" @click.stop="confirmDelete(row)"><q-tooltip>Delete</q-tooltip></q-btn>
    </template>
  </PaginatedTable>

  <NotesHost ref="notesRef" noteable-type="lead" :title-fn="row => `${row.firstname || ''} ${row.lastname || ''}`.trim() || `Lead ${row.id}`" @closed="refreshCurrent" />
</template>
