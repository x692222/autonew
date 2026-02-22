<script setup>
import { router } from '@inertiajs/vue3'
import SimpleTable from 'bo@/Components/Shared/SimpleTable.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

const props = defineProps({
    rows: { type: Array, default: () => [] },
    title: { type: String, default: 'Associated Invoices' },
    currencySymbol: { type: String, default: 'N$' },
    showActions: { type: Boolean, default: true },
})

const columns = [
    { name: 'invoice_identifier', label: 'Invoice', field: 'invoice_identifier', align: 'left' },
    { name: 'invoice_date', label: 'Date', field: 'invoice_date', align: 'left' },
    { name: 'total_amount', label: 'Total', field: 'total_amount', align: 'right' },
    { name: 'paid_amount', label: 'Paid', field: 'paid_amount', align: 'right' },
    { name: 'due_amount', label: 'Due', field: 'due_amount', align: 'right' },
    { name: 'is_fully_paid', label: 'Fully Paid', field: 'is_fully_paid', align: 'center' },
    { name: 'is_fully_verified', label: 'Fully Verified', field: 'is_fully_verified', align: 'center' },
    { name: 'status', label: 'Status', field: 'status', align: 'center' },
    { name: 'actions', label: '', field: 'actions', align: 'right' },
]

const openInvoice = (row) => {
    if (!row?.url) return
    router.visit(row.url)
}

const dueAmount = (row) => {
    const total = Number(row?.total_amount || 0)
    const paid = Number(row?.paid_amount || 0)

    return Math.max(0, total - paid)
}
</script>

<template>
    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">{{ title }}</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div v-if="!rows?.length" class="text-caption text-grey-7">No linked invoices found.</div>
            <SimpleTable
                v-else
                :rows="rows"
                :columns="columns"
                row-key="invoice_id"
                :bordered="false"
            >
                <template #cell-total_amount="{ row }">
                    <div class="text-right">
                        {{ row.total_amount === null || row.total_amount === undefined ? '-' : `${currencySymbol}${formatCurrency(row.total_amount, 2)}` }}
                    </div>
                </template>
                <template #cell-paid_amount="{ row }">
                    <div class="text-right">
                        {{ row.paid_amount === null || row.paid_amount === undefined ? '-' : `${currencySymbol}${formatCurrency(row.paid_amount, 2)}` }}
                    </div>
                </template>
                <template #cell-due_amount="{ row }">
                    <div class="text-right">
                        {{ `${currencySymbol}${formatCurrency(dueAmount(row), 2)}` }}
                    </div>
                </template>
                <template #cell-status="{ row }">
                    <div class="text-center">
                        <q-chip square dense size="sm" :color="row.status === 'FULLY PAID' ? 'positive' : (row.status === 'PARTIAL PAYMENT' ? 'warning' : 'negative')" text-color="white">
                            {{ row.status }}
                        </q-chip>
                    </div>
                </template>
                <template #cell-is_fully_paid="{ row }">
                    <div class="text-center">
                        <q-icon :name="row.is_fully_paid ? 'check_circle' : 'cancel'" :color="row.is_fully_paid ? 'positive' : 'negative'" size="20px" />
                    </div>
                </template>
                <template #cell-is_fully_verified="{ row }">
                    <div class="text-center">
                        <q-icon :name="row.is_fully_verified ? 'check_circle' : 'cancel'" :color="row.is_fully_verified ? 'positive' : 'negative'" size="20px" />
                    </div>
                </template>
                <template #cell-actions="{ row }">
                    <div class="text-right">
                        <q-btn v-if="showActions" flat dense color="primary" label="View Invoice" @click="openInvoice(row)" />
                    </div>
                </template>
            </SimpleTable>
        </q-card-section>
    </q-card>
</template>
