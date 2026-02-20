<script setup>
import { computed } from 'vue'

const props = defineProps({
    items: { type: Array, default: () => [] },
    title: { type: String, default: 'Associated Stock' },
})

const enumKeys = new Set(['type', 'condition', 'gearbox_type', 'drive_type', 'fuel_type', 'color', 'is_police_clearance_ready'])

const normalizeEnum = (value) => {
    if (value === null || value === undefined || value === '' || String(value).toLowerCase() === 'undefined') {
        return '-'
    }

    return String(value).replaceAll('_', ' ').toUpperCase()
}

const stockSummary = (stock) => {
    const parts = []
    const push = (label, value, enumValue = false) => {
        if (!value || value === 'undefined') return
        const resolved = enumValue ? normalizeEnum(value) : value
        if (resolved === '-') return
        parts.push(`${label}: ${resolved}`)
    }

    push('TYPE', stock.type, true)
    push('MAKE', stock.make)
    push('MODEL', stock.model)
    push('CONDITION', stock.condition, true)
    push('GEARBOX', stock.gearbox_type, true)
    push('DRIVE', stock.drive_type, true)
    push('FUEL', stock.fuel_type, true)
    push('COLOR', stock.color, true)
    push('MILLAGE', stock.millage)
    push('IMPORT', stock.is_import === true ? 'YES' : (stock.is_import === false ? 'NO' : null))
    push('POLICE CLEARANCE', stock.is_police_clearance_ready, true)

    return parts.length > 0 ? parts.join(' | ') : '-'
}

const hasItems = computed(() => (props.items || []).length > 0)
</script>

<template>
    <q-card v-if="hasItems" flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">{{ title }}</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <q-list dense>
                <template v-for="(stock, index) in items" :key="stock.stock_id || `${stock.internal_reference}-${index}`">
                    <q-item>
                        <q-item-section>
                            <q-item-label>{{ stock.internal_reference || stock.name || '-' }}</q-item-label>
                            <q-item-label caption>
                                {{ stockSummary(stock) }}
                            </q-item-label>
                        </q-item-section>
                    </q-item>
                    <q-separator v-if="index < items.length - 1" class="q-my-md" />
                </template>
            </q-list>
        </q-card-section>
    </q-card>
</template>

