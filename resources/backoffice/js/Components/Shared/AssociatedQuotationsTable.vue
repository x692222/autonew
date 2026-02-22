<script setup>
import { router } from '@inertiajs/vue3'
import SimpleTable from 'bo@/Components/Shared/SimpleTable.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

const props = defineProps({
    rows: { type: Array, default: () => [] },
    title: { type: String, default: 'Associated Quotations' },
    currencySymbol: { type: String, default: 'N$' },
    showActions: { type: Boolean, default: true },
})

const columns = [
    { name: 'quote_identifier', label: 'Quotation', field: 'quote_identifier', align: 'left' },
    { name: 'quotation_date', label: 'Quote Date', field: 'quotation_date', align: 'left' },
    { name: 'valid_until', label: 'Valid Until', field: 'valid_until', align: 'left' },
    { name: 'total_amount', label: 'Total', field: 'total_amount', align: 'right' },
    { name: 'status', label: 'Status', field: 'status', align: 'center' },
    { name: 'actions', label: '', field: 'actions', align: 'right' },
]

const openQuotation = (row) => {
    if (!row?.url) return
    router.visit(row.url)
}
</script>

<template>
    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">{{ title }}</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div v-if="!rows?.length" class="text-caption text-grey-7">No linked quotations found.</div>
            <SimpleTable
                v-else
                :rows="rows"
                :columns="columns"
                row-key="quotation_id"
                :bordered="false"
            >
                <template #cell-total_amount="{ row }">
                    <div class="text-right">
                        {{ row.total_amount === null || row.total_amount === undefined ? '-' : `${currencySymbol}${formatCurrency(row.total_amount, 2)}` }}
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <div class="text-center">
                        <q-chip square dense size="sm" :color="row.status === 'EXPIRED' ? 'negative' : 'positive'" text-color="white">
                            {{ row.status }}
                        </q-chip>
                    </div>
                </template>
                <template #cell-actions="{ row }">
                    <div class="text-right">
                        <q-btn v-if="showActions" flat dense color="primary" label="View Quotation" @click="openQuotation(row)" />
                    </div>
                </template>
            </SimpleTable>
        </q-card-section>
    </q-card>
</template>
