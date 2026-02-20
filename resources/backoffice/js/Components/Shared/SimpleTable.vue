<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: { type: String, default: '' },
    rows: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    rowKey: { type: String, default: 'id' },
    bordered: { type: Boolean, default: false },
})

const computedColumns = computed(() => {
    return (props.columns || []).map((col) => ({
        ...col,
        sortable: false,
        label: col?.label ? col.label.replace(/\s*count$/i, '') : col?.label,
    }))
})
</script>

<template>
    <q-table
        flat
        :bordered="bordered"
        class="full-width"
        :title="title"
        :row-key="rowKey"
        :rows="rows"
        :columns="computedColumns"
        hide-bottom
        hide-pagination
        :rows-per-page-options="[0]"
        :pagination="{ page: 1, rowsPerPage: 0, sortBy: null, descending: false }"
    >
        <template v-if="$slots['top-right']" #top-right>
            <slot name="top-right" />
        </template>

        <template #body="t">
            <q-tr :props="t">
                <q-td
                    v-for="col in computedColumns"
                    :key="col.name"
                    :props="t"
                    :class="[
                        col.align === 'center' ? 'text-center' : '',
                        col.align === 'right' ? 'text-right' : '',
                    ]"
                >
                    <template v-if="$slots[`cell-${col.name}`]">
                        <slot :name="`cell-${col.name}`" :row="t.row" :col="col" />
                    </template>
                    <template v-else>
                        {{ t.row?.[col.field || col.name] ?? '-' }}
                    </template>
                </q-td>
            </q-tr>
        </template>
    </q-table>
</template>

