<script setup>
import { computed, inject, ref, watch } from 'vue'
import axios from 'axios'
import { useQuasar } from 'quasar'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import NotesFormDialog from './NotesFormDialog.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction.js'

const $q = useQuasar()
const route = inject('route')

const props = defineProps({
    modelValue: { type: Boolean, default: false },

    // the note target
    noteableType: { type: String, required: true },
    noteableId: { type: [Number, String], required: true },
    noteableLabel: { type: String, default: '' }, // optional; will be replaced by API label anyway

    // optional ability hints
    canCreate: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const dialog = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v),
})

// --- state
const maximizedToggle = ref(true)
const tableRef = ref(null)

const loading = ref(false)
const opening = ref(false)   // specifically for "open + initial load"
const payload = ref(null)    // { noteable, filters, columns, records, authorOptions }

const filters = ref({
    search: '',
    author_guard: '',
    author_id: '',
    backoffice_only: '',
})

// used to ignore stale responses when rapidly opening different noteables
const requestKey = ref('')

// dialogs
const formDialog = ref(false)
const editingRow = ref(null)

const showNoteDialog = ref(false)
const activeNoteText = ref('')

const NOTE_PREVIEW_LIMIT = 200

const openNoteModal = (text) => {
    activeNoteText.value = (text ?? '').toString()
    showNoteDialog.value = true
}

const truncateNote = (text) => {
    const t = (text ?? '').toString()
    return t.length > NOTE_PREVIEW_LIMIT ? t.slice(0, NOTE_PREVIEW_LIMIT) + '...' : t
}

const recordsForTable = computed(() => {
    const recs = payload.value?.records
    if (!recs?.data) return recs

    return {
        ...recs,
        data: recs.data.map(r => ({
            ...r,
            note_full: r.note ?? '',
            note: truncateNote(r.note),
            note_is_truncated: ((r.note ?? '').toString().length > NOTE_PREVIEW_LIMIT),
        }))
    }
})

const canManageBackofficeOnly = computed(() => !!payload.value?.context?.can_manage_backoffice_only)

const showAuthorGuardFilter = computed(() => payload.value?.context?.guard !== 'dealer')

const guardOptions = computed(() => ([
    { label: 'All', value: '' },
    { label: 'Backoffice', value: 'backoffice' },
    { label: 'Dealer', value: 'dealer' },
]))

const authorFilterOptions = computed(() => {
    if (showAuthorGuardFilter.value) {
        return filters.value.author_guard
            ? (payload.value?.authorOptions?.[filters.value.author_guard] || [])
            : []
    }

    const out = []
    for (const [guard, list] of Object.entries(payload.value?.authorOptions || {})) {
        for (const option of list || []) {
            out.push({
                value: option.value,
                label: guard === 'backoffice' ? `${option.label} (Backoffice)` : option.label,
            })
        }
    }

    const unique = new Map()
    for (const item of out) {
        if (!unique.has(item.value)) unique.set(item.value, item)
    }

    return Array.from(unique.values()).sort((a, b) => a.label.localeCompare(b.label))
})

// confirm delete
const { confirmAction } = useConfirmAction(loading)

// --- helpers
const resetState = () => {
    // IMPORTANT: wipe all visible data so old notes never flash
    payload.value = null
    editingRow.value = null
    formDialog.value = false
    filters.value = {
        search: '',
        author_guard: '',
        author_id: '',
        backoffice_only: '',
    }
}

const buildParams = (p = null) => {
    const base = {
        search: filters.value.search || '',
        author_guard: filters.value.author_guard || '',
        author_id: filters.value.author_id || '',
        backoffice_only: filters.value.backoffice_only !== '' ? filters.value.backoffice_only : '',

        // table filters
        page: p?.page ?? 1,
        rowsPerPage: p?.rowsPerPage ?? 15,
        sortBy: p?.sortBy ?? 'created_at',
        descending: typeof p?.descending === 'boolean' ? p.descending : true,
    }

    // clean empties
    Object.keys(base).forEach(k => {
        if (base[k] === '' || base[k] === null || base[k] === undefined) delete base[k]
    })

    return base
}

const loadNotes = async (p = null) => {
    const thisKey = `${props.noteableType}:${props.noteableId}:${Date.now()}`
    requestKey.value = thisKey

    // when opening fresh, show blank content until load completes
    if (!payload.value) opening.value = true
    loading.value = true

    try {
        const { data } = await axios.get(
            route('backoffice.notes.index', [props.noteableType, props.noteableId]),
            { params: buildParams(p) }
        )

        // ignore stale responses (e.g. opened another noteable quickly)
        if (requestKey.value !== thisKey) return

        payload.value = data

        // keep filters in sync with backend (validated defaults etc)
        filters.value = {
            search: data?.filters?.search ?? '',
            author_guard: data?.filters?.author_guard ?? '',
            author_id: data?.filters?.author_id ?? '',
            backoffice_only: (data?.filters?.backoffice_only ?? '') + '',
        }

        if (!data?.context?.can_manage_backoffice_only) {
            filters.value.backoffice_only = ''
            filters.value.author_guard = ''
        }

    } catch (e) {
        if (requestKey.value === thisKey) {
            $q.notify({ type: 'negative', message: e?.response?.data?.message || 'Failed to load notes.' })
        }
    } finally {
        if (requestKey.value === thisKey) {
            opening.value = false
            loading.value = false
        }
    }
}

const fetchNotes = (p, helpers) => {
    // pagination inside notes can keep existing data; not required to blank
    loadNotes(p).finally(() => helpers.finish())
}

// called when opening (or switching noteable while open)
const openAndLoad = async () => {
    resetState()
    await loadNotes()
}

const close = () => {
    dialog.value = false
}

// filter triggers
const goFirst = () => tableRef.value?.goFirstPage()

const onFilterChange = () => {
    // if you want "fresh page 1" behaviour for filters
    goFirst()
}

// actions
const openCreate = () => {
    editingRow.value = null
    formDialog.value = true
}

const openEdit = (row) => {
    editingRow.value = {
        ...row,
        note: row.note_full ?? row.note ?? '',
    }
    formDialog.value = true
}

const onSaved = (meta = null) => {
    if (meta?.action === 'created') {
        filters.value = {
            search: '',
            author_guard: '',
            author_id: '',
            backoffice_only: '',
        }

        tableRef.value?.goFirstPage?.()
        return
    }

    // edit => reload current page
    tableRef.value?.refresh?.()
}

const confirmDelete = (row) => {
    $q.dialog({
        title: 'Delete Note',
        message: 'Are you sure you want to delete this note? This cannot be undone.',
        cancel: true,
        persistent: true,
        ok: { label: 'Delete', color: 'negative' },
        cancel: { label: 'Cancel' },
    }).onOk(async () => {
        try {
            loading.value = true
            await axios.delete(route('backoffice.notes.destroy', [props.noteableType, props.noteableId, row.id]))
            $q.notify({ type: 'positive', message: 'Note deleted.' })

            // refresh current page only
            tableRef.value?.refresh?.()
        } catch (e) {
            $q.notify({ type: 'negative', message: e?.response?.data?.message || 'Failed to delete note.' })
        } finally {
            loading.value = false
        }
    })
}

// --- watchers
watch(
    () => dialog.value,
    (open) => {
        if (open) openAndLoad()
        else resetState()
    }
)

// If the dialog stays open and the noteable changes (rare but possible), reset + reload
watch(
    () => [props.noteableType, props.noteableId],
    () => {
        if (dialog.value) openAndLoad()
    }
)
</script>

<template>
    <q-dialog
        v-model="dialog"
        persistent
        :maximized="maximizedToggle"
        transition-show="slide-up"
        transition-hide="slide-down"
    >
        <div>
            <q-card class="bg-white text-dark">
                <q-bar>
                    <div class="text-weight-medium">
                        Notes
                        <span v-if="payload?.noteable?.label" class="text-grey-7 q-ml-sm">— {{ payload.noteable.label }}</span>
                        <span v-else-if="noteableLabel" class="text-grey-7 q-ml-sm">— {{ noteableLabel }}</span>
                    </div>

                    <q-space />

                    <q-btn dense flat icon="minimize" @click="maximizedToggle = false" :disable="!maximizedToggle">
                        <q-tooltip v-if="maximizedToggle">Minimize</q-tooltip>
                    </q-btn>
                    <q-btn dense flat icon="crop_square" @click="maximizedToggle = true" :disable="maximizedToggle">
                        <q-tooltip v-if="!maximizedToggle">Maximize</q-tooltip>
                    </q-btn>
                    <q-btn dense flat icon="close" @click="close">
                        <q-tooltip>Close</q-tooltip>
                    </q-btn>
                </q-bar>

                <!-- IMPORTANT: Show NOTHING until payload arrives -->
                <q-card-section v-if="!payload" class="q-pa-lg">
                    <div class="text-grey-7">Loading notes…</div>
                    <q-inner-loading :showing="true" />
                </q-card-section>

                <q-card-section v-else class="q-pa-md">
                    <div class="row items-center justify-between q-mb-sm">
                        <div class="row q-col-gutter-sm items-center">
                            <div class="col-auto" style="min-width: 320px">
                                <q-input
                                    dense outlined clearable debounce="500"
                                    v-model="filters.search"
                                    label="Search notes"
                                    @update:model-value="onFilterChange"
                                />
                            </div>

                            <div v-if="showAuthorGuardFilter" class="col-auto" style="min-width: 200px">
                                <q-select
                                    dense outlined clearable
                                    emit-value map-options
                                    label="Posted by (Guard)"
                                    v-model="filters.author_guard"
                                    :options="guardOptions"
                                    @update:model-value="() => { filters.author_id = ''; onFilterChange() }"
                                />
                            </div>

                            <div class="col-auto" style="min-width: 260px">
                                <q-select
                                    dense outlined clearable
                                    emit-value map-options
                                    label="Posted by"
                                    v-model="filters.author_id"
                                    :options="authorFilterOptions"
                                    :disable="showAuthorGuardFilter && !filters.author_guard"
                                    @update:model-value="onFilterChange"
                                />
                            </div>

                            <div v-if="canManageBackofficeOnly" class="col-auto" style="min-width: 200px">
                                <q-select
                                    dense outlined clearable
                                    emit-value map-options
                                    label="Backoffice only"
                                    v-model="filters.backoffice_only"
                                    :options="[
                                        { label: 'All', value: '' },
                                        { label: 'Yes', value: '1' },
                                        { label: 'No', value: '0' },
                                    ]"
                                    @update:model-value="onFilterChange"
                                />
                            </div>
                        </div>

                        <div>
                            <q-btn
                                v-if="canCreate"
                                color="primary"
                                label="Add note"
                                no-wrap
                                unelevated
                                :disable="opening || loading"
                                @click="openCreate"
                            />
                        </div>
                    </div>

                    <PaginatedTable
                        ref="tableRef"
                        title="Notes"
                        row-key="id"
                        :records="recordsForTable"
                        :columns="[
                            ...payload.columns,
                            { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions', numeric: false }
                        ]"
                        :fetch="fetchNotes"
                        initial-sort-by="created_at"
                        :initial-descending="true"
                    >
                        <template #actions="{ row }">
                            <q-btn
                                v-if="row.note_is_truncated"
                                round dense flat
                                icon="visibility"
                                :disable="loading"
                                @click.stop="openNoteModal(row.note_full)"
                            >
                                <q-tooltip>Show note</q-tooltip>
                            </q-btn>

                            <q-btn
                                v-if="row.can?.edit"
                                round dense flat icon="edit"
                                :disable="loading"
                                @click.stop="openEdit(row)"
                            >
                                <q-tooltip>Edit</q-tooltip>
                            </q-btn>

                            <q-btn
                                v-if="row.can?.delete"
                                round dense flat icon="delete" color="negative"
                                :disable="loading"
                                @click.stop="confirmDelete(row)"
                            >
                                <q-tooltip>Delete</q-tooltip>
                            </q-btn>
                        </template>

                        <template #no-data>
                            <div class="full-width row flex-center q-gutter-sm q-pa-md">
                                <q-icon name="search_off" size="24px" />
                                <span>No notes found</span>
                            </div>
                        </template>
                    </PaginatedTable>
                </q-card-section>
            </q-card>

            <NotesFormDialog
                v-model="formDialog"
                :noteable-type="noteableType"
                :noteable-id="noteableId"
                :note="editingRow"
                :can-manage-backoffice-only="canManageBackofficeOnly"
                @saved="onSaved"
            />

            <q-dialog v-model="showNoteDialog">
                <q-card style="min-width: 600px; max-width: 90vw;">
                    <q-card-section>
                        <div class="text-h6">Note</div>
                    </q-card-section>

                    <q-separator />

                    <q-card-section style="max-height: 50vh" class="scroll">
                        <div style="white-space: pre-wrap;">{{ activeNoteText }}</div>
                    </q-card-section>

                    <q-separator />

                    <q-card-actions align="right">
                        <q-btn flat label="Close" color="primary" v-close-popup />
                    </q-card-actions>
                </q-card>
            </q-dialog>

        </div>
    </q-dialog>
</template>
