<script setup>
import { router } from '@inertiajs/vue3'
import SimpleTable from 'bo@/Components/Shared/SimpleTable.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

const props = defineProps({
    rows: { type: Array, default: () => [] },
    title: { type: String, default: 'Associated Payments' },
    currencySymbol: { type: String, default: 'N$' },
    showActions: { type: Boolean, default: true },
})

const columns = [
    { name: 'payment_date', label: 'Payment Date', field: 'payment_date', align: 'left' },
    { name: 'invoice_identifier', label: 'Invoice', field: 'invoice_identifier', align: 'left' },
    { name: 'payment_method', label: 'Method', field: 'payment_method', align: 'left' },
    { name: 'amount', label: 'Amount', field: 'amount', align: 'right' },
    { name: 'status', label: 'Status', field: 'status', align: 'center' },
    { name: 'actions', label: '', field: 'actions', align: 'right' },
]

const openPayment = (row) => {
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
            <div v-if="!rows?.length" class="text-caption text-grey-7">No linked payments found.</div>
            <SimpleTable
                v-else
                :rows="rows"
                :columns="columns"
                row-key="payment_id"
                :bordered="false"
            >
                <template #cell-payment_method="{ row }">
                    {{ String(row.payment_method || '-').toUpperCase() }}
                </template>
                <template #cell-amount="{ row }">
                    <div class="text-right">
                        {{ row.amount === null || row.amount === undefined ? '-' : `${currencySymbol}${formatCurrency(row.amount, 2)}` }}
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <div class="text-center">
                        <q-chip
                            square
                            dense
                            size="sm"
                            :color="row.status === 'APPROVED' ? 'positive' : 'negative'"
                            text-color="white"
                        >
                            {{ row.status }}
                        </q-chip>
                    </div>
                </template>
                <template #cell-actions="{ row }">
                    <div class="text-right">
                        <q-btn v-if="showActions" flat dense color="primary" label="View Payment" @click="openPayment(row)" />
                    </div>
                </template>
            </SimpleTable>
        </q-card-section>
    </q-card>
</template>
