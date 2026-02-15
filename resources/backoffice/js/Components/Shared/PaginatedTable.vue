<script setup>
import { computed, ref, watch, nextTick, useSlots } from 'vue'
import { router } from '@inertiajs/vue3'
import { scroll } from 'quasar'

const { getScrollTarget, setVerticalScrollPosition } = scroll;

const tableContainerRef = ref(null)

const props = defineProps({
    records: { type: Object, required: true }, // { data: [], meta: { current_page, per_page, total, ... } }
    columns: { type: Array, required: true },

    title: { type: String, default: '' },
    rowKey: { type: String, default: 'id' },

    initialSortBy: { type: String, default: 'id' },
    initialDescending: { type: Boolean, default: true },

    rowsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100] },

    /**
     * Required: page provides fetch(pagination, helpers)
     * helpers.finish() MUST be called when the request finishes.
     */
    fetch: { type: Function, required: true },

    /**
     * OPTIONAL (generic deferred support)
     * Pass ALL deferred props in one object.
     * Example:
     * :deferred="{ deferredBranchesCount, deferredUsersCount, deferredStockCount }"
     */
    deferred: { type: Object, default: () => ({}) },

    /**
     * OPTIONAL (generic "dependency mismatch" guard per deferred prop)
     * Example for stock type:
     * :deferredVersions="{ deferredStockCount: type }"
     *
     * If a deferred prop's version changes, the table will show loading
     * until that deferred prop updates and is considered "loaded" for that version.
     */
    deferredVersions: { type: Object, default: () => ({}) },

    /**
     * OPTIONAL: automatically reload deferred-only props when helpers.finish() is called.
     * Default true if deferred props exist.
     */
    autoReloadDeferred: { type: Boolean, default: true },

    /**
     * OPTIONAL: allow configuring reload behaviour
     */
    reloadDeferredOptions: {
        type: Object,
        default: () => ({
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    },

    /**
     * OPTIONAL: status column rendering conventions
     * - is_active is consistent across your DB, and filter is status
     */
    statusKey: { type: String, default: 'is_active' },
    statusColumnName: { type: String, default: 'status' },
    actionsColumnName: { type: String, default: 'actions' }
})

const paginator = computed(() => props.records || {})

const loading = ref(false)

const slots = useSlots()

const pagination = ref({
    page: paginator.value.current_page ?? 1,
    rowsPerPage: paginator.value.per_page ?? 10,
    sortBy: props.initialSortBy,
    descending: props.initialDescending,
    rowsNumber: paginator.value.total ?? 0
})

watch(
    () => props.records,
    (r) => {
        if (!r) return
        pagination.value.page = r.current_page ?? 1
        pagination.value.rowsPerPage = r.per_page ?? pagination.value.rowsPerPage
        pagination.value.rowsNumber = r.total ?? 0
    },
    { deep: true, immediate: true }
)

/**
 * numericCols (moved from page): derived from columns[].numeric
 */
const numericCols = computed(() => {
    const set = new Set()
    for (const c of props.columns || []) {
        if (c?.numeric) set.add(c.name)
    }
    return set
})

/**
 * deferredGroups (moved from page): use the deferred prop object
 * We also require the key to start with "deferred" to keep it convention-based.
 */
const deferredGroups = computed(() => {
    const out = {}
    for (const [k, v] of Object.entries(props.deferred || {})) {
        if (k.startsWith('deferred')) out[k] = v
    }
    return out
})

/**
 * deferredOwnerByCol (generic):
 * Build colName -> deferredKey mapping by scanning payload keys.
 */
const deferredOwnerByCol = computed(() => {
    const map = new Map()

    for (const [deferredKey, payload] of Object.entries(deferredGroups.value)) {
        if (!payload || typeof payload !== 'object') continue

        for (const dealerId of Object.keys(payload)) {
            const rec = payload[dealerId]
            if (!rec || typeof rec !== 'object') continue

            for (const colName of Object.keys(rec)) {
                if (!map.has(colName)) map.set(colName, deferredKey)
            }
        }
    }

    return map
})

/**
 * Track which version each deferred group is currently "loaded" for.
 * This solves the stock-type mismatch generically (and can solve others too).
 */
const loadedDeferredVersions = ref({})

watch(
    () => deferredGroups.value,
    (groups, prevGroups = {}) => {
        const next = { ...loadedDeferredVersions.value }

        for (const [key, payload] of Object.entries(groups)) {
            const prevPayload = prevGroups?.[key]

            // Only mark "loaded for this version" when the PAYLOAD changes,
            // not when the wrapper object changes due to re-render.
            if (payload !== null && payload !== undefined && payload !== prevPayload) {
                next[key] = props.deferredVersions?.[key] ?? ''
            }
        }

        loadedDeferredVersions.value = next
    },
    // Important: do NOT deep watch, we only care about reference change
    { deep: false, immediate: true }
)

const hasPendingDeferred = computed(() => {
    return Object.values(deferredGroups.value).some(v => v === null || v === undefined)
})

/**
 * isDeferredLoading (moved from page, now generic)
 */
const isDeferredLoading = (colName, row) => {
    const owner = deferredOwnerByCol.value.get(colName)
    if (!owner) {
        // Hard refresh: deferred props can be null initially, so we can't infer ownership yet.
        // If deferred is pending AND this looks like a deferred numeric column (not present on base row),
        // show a spinner instead of flashing 0.
        if (hasPendingDeferred.value && numericCols.value.has(colName)) {
            const hasBaseValue = Object.prototype.hasOwnProperty.call(row || {}, colName)
            if (!hasBaseValue) return true
        }

        return false
    }

    const group = deferredGroups.value[owner]
    if (!group) return true

    // dependency mismatch guard (generic)
    const currentVersion = props.deferredVersions?.[owner]
    if (currentVersion !== undefined) {
        const loadedVersion = loadedDeferredVersions.value?.[owner]
        if ((loadedVersion ?? '') !== (currentVersion ?? '')) return true
    }

    // A missing row payload in a loaded deferred group is valid (eg. zero counts).
    // Keep rendering with fallback values instead of spinning forever.
    return false
}

/**
 * getDeferredValue (moved from page, now generic)
 * - if a column belongs to some deferred group, read it from that group
 * - else fallback to row[colName]
 */
const getDeferredValue = (colName, row) => {
    const isNumeric = numericCols.value.has(colName)
    const fallback = row?.[colName] ?? (isNumeric ? 0 : '-')

    const owner = deferredOwnerByCol.value.get(colName)
    if (!owner) return fallback

    const group = deferredGroups.value[owner]
    const id = row?.[props.rowKey]

    if (!group?.[id]) return isNumeric ? 0 : '-'

    // dependency mismatch guard => treat as not loaded yet / 0
    const currentVersion = props.deferredVersions?.[owner]
    if (currentVersion !== undefined) {
        const loadedVersion = loadedDeferredVersions.value?.[owner]
        if ((loadedVersion ?? '') !== (currentVersion ?? '')) {
            return isNumeric ? 0 : '-'
        }
    }

    const rec = group[id]
    return rec?.[colName] ?? (isNumeric ? 0 : '-')
}

/**
 * Default rendering helper:
 * - status badge always based on is_active
 * - everything else uses deferred+fallback
 */
const getCellDisplay = (colName, row) => {
    if (colName === props.statusColumnName) {
        return row?.[props.statusKey] ? 'Active' : 'Inactive'
    }
    if (colName === 'backoffice_only') {
        return row?.['backoffice_only'] ? '✔' : ''
    }
    return getDeferredValue(colName, row)
}

function runFetch () {
    loading.value = true

    props.fetch(
        { ...pagination.value },
        {
            finish: () => {
                loading.value = false

                nextTick(() => scrollTableToTop())

                // auto reload deferred-only props (moved from page)
                const keys = Object.keys(deferredGroups.value)
                if (
                    props.autoReloadDeferred &&
                    keys.length > 0
                ) {
                    router.reload({
                        only: keys,
                        ...props.reloadDeferredOptions
                    })
                }
            }
        }
    )
}

function goFirstPage () {
    pagination.value.page = 1
    runFetch()
}

function onRequest (req) {
    pagination.value = {
        ...pagination.value,
        ...req.pagination
    }
    runFetch()
}

const normalizeColumnLabel = (label) => {
    if (!label) return ''

    // Remove trailing "Count" (case-insensitive)
    return label.replace(/\s*count$/i, '')
}

const computedColumns = computed(() => {
    return (props.columns || []).map(col => ({
        ...col,
        label: col?.label
            ? col.label.replace(/\s*count$/i, '')
            : col.label,
    }))
})

function scrollTableToTop () {
    const el = tableContainerRef.value
    if (!el) return

    const target = getScrollTarget(el)
    const offset = el.offsetTop
    const duration = 200

    setVerticalScrollPosition(target, offset, duration)
}

defineExpose({
    goFirstPage,
    refresh: runFetch,
    pagination,
    loading,

    // expose these in case a page slot wants them
    numericCols,
    deferredGroups,
    deferredOwnerByCol,
    isDeferredLoading,
    getDeferredValue
})
</script>

<template>
    <div ref="tableContainerRef">
    <q-table
            flat
            bordered
            :title="title"
            :row-key="rowKey"
            :rows="records.data"
            :columns="computedColumns"
            :loading="loading"
            :rows-per-page-options="rowsPerPageOptions"
            :rows-number="pagination.rowsNumber"
            v-model:pagination="pagination"
            @request="onRequest"
        >
            <!-- Optional: allow the page to inject controls -->
            <template v-if="$slots['top-right']" #top-right>
                <slot name="top-right" :goFirstPage="goFirstPage" />
            </template>

            <!-- If page provides body slot: keep existing behavior (DO NOT REMOVE) -->
            <template v-if="$slots.body" #body="slotProps">
                <slot
                    name="body"
                    v-bind="slotProps"
                    :goFirstPage="goFirstPage"
                    :numericCols="numericCols"
                    :isDeferredLoading="isDeferredLoading"
                    :getDeferredValue="getDeferredValue"
                />
            </template>

            <!-- Default body renderer (so index page can be minimal) -->
            <template v-else #body="t">
                <q-tr :props="t">
                    <q-td
                        v-for="col in computedColumns"
                        :key="col.name"
                        :props="t"
                        :class="[
                            numericCols.has(col.name) ? 'text-right' : '',
                            col.align === 'center' ? 'text-center' : '',
                            col.align === 'right' ? 'text-right' : ''
                        ]"
                    >
                        <!-- actions column rendered via slot -->
                        <template v-if="col.name === actionsColumnName">
                            <slot name="actions" :row="t.row" />
                        </template>

                        <!-- status badge -->
                        <template v-else-if="col.name === statusColumnName">
                            <q-badge :color="t.row[statusKey] ? 'positive' : 'negative'">
                                {{ t.row[statusKey] ? 'Active' : 'Inactive' }}
                            </q-badge>
                        </template>

                        <!-- deferred-aware cell -->
                        <!-- deferred-aware cell + per-column cell slots -->
                        <template v-else>
                            <template v-if="isDeferredLoading(col.name, t.row)">
                                <q-spinner size="16px" />
                            </template>

                            <template v-else>
                                <!-- if parent provided a slot like #cell-dealer_name -->
                                <template v-if="$slots[`cell-${col.name}`]">
                                    <slot :name="`cell-${col.name}`" :row="t.row" :col="col" />
                                </template>

                                <!-- fallback: default rendering -->
                                <template v-else>
                                    {{ getCellDisplay(col.name, t.row) }}
                                </template>
                            </template>
                        </template>

                    </q-td>
                </q-tr>
            </template>

            <!-- Optional no-data slot -->
            <template v-if="$slots['no-data']" #no-data>
                <slot name="no-data" :goFirstPage="goFirstPage" />
            </template>
        </q-table>

    </div>
</template>
