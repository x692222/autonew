<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })
const page = usePage()

const props = defineProps({
    publicTitle: { type: String, default: 'Verify Payments' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'verify-payments' },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    currencySymbol: { type: String, default: 'N$' },
    verifyRouteName: { type: String, required: true },
    paymentShowRouteName: { type: String, required: true },
    invoiceEditRouteName: { type: String, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)

const search = ref(props.filters?.search ?? '')
const invoiceIdentifier = ref(props.filters?.invoice_identifier ?? '')
const paymentMethod = ref(props.filters?.payment_method ?? '')
const verificationStatus = ref(props.filters?.verification_status ?? 'pending')
const paymentDateFrom = ref(props.filters?.payment_date_from ?? '')
const paymentDateTo = ref(props.filters?.payment_date_to ?? '')

const paymentMethodOptions = [
    { label: 'All', value: '' },
    { label: 'CASH', value: 'cash' },
    { label: 'EFT', value: 'eft' },
    { label: 'FINANCE HOUSE', value: 'finance_house' },
    { label: 'CARD', value: 'card' },
]

const verificationStatusOptions = [
    { label: 'All', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'Verified', value: 'verified' },
]

const columns = [
    { name: 'payment_date', label: 'Payment Date', sortable: true, align: 'left', field: 'payment_date' },
    { name: 'invoice_identifier', label: 'Invoice', sortable: false, align: 'left', field: 'invoice_identifier' },
    { name: 'invoice_date', label: 'Invoice Date', sortable: false, align: 'left', field: 'invoice_date' },
    { name: 'customer_name', label: 'Customer', sortable: false, align: 'left', field: 'customer_name' },
    { name: 'stock_numbers', label: 'Stock', sortable: false, align: 'left', field: 'stock_numbers' },
    { name: 'invoice_items_count', label: 'Items', sortable: false, align: 'right', field: 'invoice_items_count', numeric: true },
    { name: 'invoice_total_amount', label: 'Invoice Total', sortable: false, align: 'right', field: 'invoice_total_amount', numeric: true },
    { name: 'payment_amount', label: 'Payment Amount', sortable: false, align: 'right', field: 'payment_amount', numeric: true },
    { name: 'payment_method', label: 'Method', sortable: false, align: 'left', field: 'payment_method' },
    { name: 'is_approved', label: 'Verified', sortable: false, align: 'center', field: 'is_approved' },
    { name: 'last_verified_at', label: 'Last Verified', sortable: false, align: 'left', field: 'last_verified_at' },
    { name: 'last_verified_by', label: 'Verified By', sortable: false, align: 'left', field: 'last_verified_by' },
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions' },
]

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.verify-payments.index', props.dealer?.id)
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.verify-payments.index')
    }
    return route('backoffice.system.verify-payments.index')
})

const currentUrl = computed(() => page.url || indexRoute.value)
const goFirst = () => tableRef.value?.goFirstPage()

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, {
        page: pagination?.page,
        rowsPerPage: pagination?.rowsPerPage,
        sortBy: pagination?.sortBy,
        descending: pagination?.descending,
        search: search.value || '',
        invoice_identifier: invoiceIdentifier.value || '',
        payment_method: paymentMethod.value || '',
        verification_status: verificationStatus.value || 'pending',
        payment_date_from: paymentDateFrom.value || '',
        payment_date_to: paymentDateTo.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['records', 'filters', 'flash'],
        onFinish: () => helpers.finish(),
    })
}

const invoiceUrl = (row) => {
    if (!row?.invoice_id) return null
    if (props.context?.mode === 'dealer-backoffice') {
        return route(props.invoiceEditRouteName, {
            dealer: props.dealer?.id,
            invoice: row.invoice_id,
            return_to: currentUrl.value,
        })
    }

    return route(props.invoiceEditRouteName, {
        invoice: row.invoice_id,
        return_to: currentUrl.value,
    })
}

const openInvoice = (row) => {
    const url = invoiceUrl(row)
    if (!url) return
    router.visit(url)
}

const verifyActionUrl = (row) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route(props.verifyRouteName, { dealer: props.dealer?.id, payment: row.id })
    }
    return route(props.verifyRouteName, { payment: row.id })
}

const paymentShowUrl = (row) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route(props.paymentShowRouteName, { dealer: props.dealer?.id, payment: row.id, return_to: currentUrl.value })
    }

    return route(props.paymentShowRouteName, { payment: row.id, return_to: currentUrl.value })
}

const openPayment = (row) => {
    const url = paymentShowUrl(row)
    if (!url) return
    router.visit(url)
}

const confirmVerify = (row) => {
    const invoiceRef = row.invoice_identifier || '-'
    const invoiceDate = row.invoice_date || '-'
    const total = row.invoice_total_amount == null ? '-' : `${props.currencySymbol}${formatCurrency(row.invoice_total_amount, 2)}`
    confirmAction({
        title: 'Verify Payment',
        message: `Verify payment for invoice ${invoiceRef}?\nInvoice Date: ${invoiceDate}\nInvoice Total: ${total}`,
        okLabel: 'Verify',
        okColor: 'positive',
        cancelLabel: 'Cancel',
        method: 'post',
        actionUrl: verifyActionUrl(row),
        inertia: { preserveState: true },
    })
}
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
    </div>

    <DealerTabs v-if="context?.mode === 'dealer-backoffice' && dealer?.id" :page-tab="pageTab" :dealer-id="dealer.id" />
    <DealerConfigurationNav v-if="context?.mode === 'dealer'" tab="verify-payments" />

    <PaginatedTable
        ref="tableRef"
        title="Verify Payments"
        row-key="id"
        :records="records"
        :columns="columns"
        :fetch="fetchRecords"
        initial-sort-by="payment_date"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 240px;">
                    <q-input v-model="search" dense outlined clearable debounce="700" placeholder="Search..." @update:model-value="goFirst" />
                </div>
                <div class="col-auto" style="min-width: 200px;">
                    <q-input v-model="invoiceIdentifier" dense outlined clearable debounce="700" placeholder="Invoice..." @update:model-value="goFirst" />
                </div>
                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="paymentMethod"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="paymentMethodOptions"
                        option-label="label"
                        option-value="value"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 180px;">
                    <q-select
                        v-model="verificationStatus"
                        dense
                        outlined
                        emit-value
                        map-options
                        :options="verificationStatusOptions"
                        option-label="label"
                        option-value="value"
                        @update:model-value="goFirst"
                    />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input v-model="paymentDateFrom" dense outlined clearable label="Date From" placeholder="YYYY-MM-DD" @update:model-value="goFirst" />
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input v-model="paymentDateTo" dense outlined clearable label="Date To" placeholder="YYYY-MM-DD" @update:model-value="goFirst" />
                </div>
            </div>
        </template>

        <template #cell-invoice_identifier="{ row }">
            <q-btn
                v-if="row.invoice_identifier && row.invoice_id"
                flat
                dense
                no-caps
                class="q-px-none"
                color="primary"
                :label="row.invoice_identifier"
                @click="openInvoice(row)"
            />
            <span v-else>{{ row.invoice_identifier || '-' }}</span>
        </template>

        <template #cell-invoice_total_amount="{ row }">
            <span>{{ row.invoice_total_amount == null ? '-' : `${currencySymbol}${formatCurrency(row.invoice_total_amount, 2)}` }}</span>
        </template>
        <template #cell-stock_numbers="{ row }">
            <div v-if="Array.isArray(row.stock_numbers) && row.stock_numbers.length">
                <div v-for="stockNumber in row.stock_numbers" :key="stockNumber">
                    {{ stockNumber }}
                </div>
            </div>
            <span v-else>-</span>
        </template>
        <template #cell-payment_amount="{ row }">
            <span>{{ row.payment_amount == null ? '-' : `${currencySymbol}${formatCurrency(row.payment_amount, 2)}` }}</span>
        </template>

        <template #cell-is_approved="{ row }">
            <q-chip
                square
                dense
                size="sm"
                :color="row.is_approved ? 'positive' : 'negative'"
                text-color="white"
            >
                {{ row.is_approved ? 'VERIFIED' : 'PENDING' }}
            </q-chip>
        </template>

        <template #actions="{ row }">
            <div class="row items-center justify-end q-gutter-sm">
                <q-btn
                    v-if="row.can?.view"
                    dense
                    flat
                    no-caps
                    size="sm"
                    label="Show"
                    color="primary"
                    @click="openPayment(row)"
                />
                <q-btn
                    v-if="row.can?.verify"
                    dense
                    unelevated
                    no-caps
                    size="sm"
                    label="Verify"
                    color="positive"
                    @click="confirmVerify(row)"
                />
            </div>
        </template>
    </PaginatedTable>
</template>
