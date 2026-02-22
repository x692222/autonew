<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
    bankingDetailOptions: { type: Array, default: () => [] },
    autoRefreshSeconds: { type: Number, default: 30 },
    verifyRouteName: { type: String, required: true },
    paymentShowRouteName: { type: String, required: true },
    invoiceEditRouteName: { type: String, required: true },
})

const tableRef = ref(null)
const loading = ref(false)
const { confirmAction } = useConfirmAction(loading)
let autoRefreshIntervalId = null
const remainingSeconds = ref(30)

const search = ref(props.filters?.search ?? '')
const paymentMethod = ref(props.filters?.payment_method ?? '')
const bankingDetailId = ref(props.filters?.banking_detail_id ?? '')
const verificationStatus = ref(props.filters?.verification_status ?? 'pending')
const paymentDateFrom = ref(props.filters?.payment_date_from ?? '')
const paymentDateTo = ref(props.filters?.payment_date_to ?? '')

const parseYmd = (value) => {
    if (!value) {
        return null
    }

    const parts = String(value).split(/[^0-9]/).filter(Boolean)
    const [year, month, day] = parts.map(Number)
    if (!year || !month || !day) {
        return null
    }

    return new Date(year, month - 1, day)
}

const formatYmd = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const addDays = (value, days) => {
    const date = parseYmd(value)
    if (!date) {
        return ''
    }

    date.setDate(date.getDate() + days)
    return formatYmd(date)
}

const toEpoch = (value) => {
    const parsed = parseYmd(value)
    return parsed ? parsed.getTime() : null
}

const isBefore = (left, right) => {
    const leftEpoch = toEpoch(left)
    const rightEpoch = toEpoch(right)
    return leftEpoch !== null && rightEpoch !== null && leftEpoch < rightEpoch
}

const isAfter = (left, right) => {
    const leftEpoch = toEpoch(left)
    const rightEpoch = toEpoch(right)
    return leftEpoch !== null && rightEpoch !== null && leftEpoch > rightEpoch
}

const isPaymentDateFromAllowed = (date) => !paymentDateTo.value || isBefore(date, paymentDateTo.value)
const isPaymentDateToAllowed = (date) => !paymentDateFrom.value || isAfter(date, paymentDateFrom.value)

const onPaymentDateFromChange = (value) => {
    paymentDateFrom.value = value || ''

    if (!paymentDateFrom.value) {
        paymentDateTo.value = ''
        goFirst()
        return
    }

    if (!paymentDateTo.value) {
        paymentDateTo.value = addDays(paymentDateFrom.value, 1)
    }

    goFirst()
}

const onPaymentDateToChange = (value) => {
    paymentDateTo.value = value || ''

    if (!paymentDateTo.value) {
        paymentDateFrom.value = ''
        goFirst()
        return
    }

    if (!paymentDateFrom.value) {
        paymentDateFrom.value = addDays(paymentDateTo.value, -1)
    }

    goFirst()
}

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
const isEftPaymentMethod = computed(() => String(paymentMethod.value || '').toLowerCase() === 'eft')

const onPaymentMethodChange = (value) => {
    paymentMethod.value = value || ''

    if (!isEftPaymentMethod.value) {
        bankingDetailId.value = ''
    }

    goFirst()
}

const columns = [
    { name: 'payment_date', label: 'Payment Date', sortable: true, align: 'left', field: 'payment_date' },
    { name: 'invoice_identifier', label: 'Invoice', sortable: false, align: 'left', field: 'invoice_identifier' },
    { name: 'invoice_date', label: 'Invoice Date', sortable: false, align: 'left', field: 'invoice_date' },
    { name: 'customer_name', label: 'Customer', sortable: false, align: 'left', field: 'customer_name' },
    { name: 'stock_items', label: 'Stock', sortable: false, align: 'left', field: 'stock_items' },
    { name: 'invoice_total_amount', label: 'Invoice Total', sortable: false, align: 'right', field: 'invoice_total_amount', numeric: true },
    { name: 'payment_amount', label: 'Payment Amount', sortable: false, align: 'right', field: 'payment_amount', numeric: true },
    { name: 'payment_method', label: 'Method', sortable: false, align: 'left', field: 'payment_method' },
    { name: 'is_approved', label: 'Verified', sortable: false, align: 'center', field: 'is_approved' },
    { name: 'last_verified_at', label: 'Last Verified', sortable: false, align: 'left', field: 'last_verified_at' },
    { name: 'last_verified_by', label: 'Verified By', sortable: false, align: 'left', field: 'last_verified_by' },
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions' },
]
const isDealerContext = computed(() => ['dealer', 'dealer-backoffice'].includes(props.context?.mode))
const visibleColumns = computed(() => (
    isDealerContext.value
        ? columns
        : columns.filter((column) => column.name !== 'stock_items')
))

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
const resolvedAutoRefreshSeconds = computed(() => {
    const min = 30
    const max = 7200
    const value = Number(props.autoRefreshSeconds || min)

    if (!Number.isFinite(value)) {
        return min
    }

    if (value < min) {
        return min
    }

    if (value > max) {
        return max
    }

    return Math.trunc(value)
})
const autoRefreshLabel = computed(() => {
    const seconds = resolvedAutoRefreshSeconds.value
    const countdown = Math.max(0, Math.trunc(remainingSeconds.value || 0))

    if (seconds % 3600 === 0) {
        const hours = seconds / 3600
        return `Auto-refreshing records every ${hours} hour${hours === 1 ? '' : 's'} (next refresh in ${countdown}s).`
    }

    if (seconds % 60 === 0) {
        const minutes = seconds / 60
        return `Auto-refreshing records every ${minutes} minute${minutes === 1 ? '' : 's'} (next refresh in ${countdown}s).`
    }

    return `Auto-refreshing records every ${seconds} seconds (next refresh in ${countdown}s).`
})

const reloadRecordsOnly = () => {
    if (loading.value) {
        return
    }

    router.reload({
        only: ['records'],
        preserveState: true,
        preserveScroll: true,
    })
}

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, {
        page: pagination?.page,
        rowsPerPage: pagination?.rowsPerPage,
        sortBy: pagination?.sortBy,
        descending: pagination?.descending,
        search: search.value || '',
        payment_method: paymentMethod.value || '',
        banking_detail_id: isEftPaymentMethod.value ? (bankingDetailId.value || '') : '',
        verification_status: verificationStatus.value || 'pending',
        payment_date_from: paymentDateFrom.value || '',
        payment_date_to: paymentDateTo.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['records', 'filters', 'bankingDetailOptions', 'flash'],
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

const stockUrl = (stockId) => {
    if (!stockId) return null

    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.stock.show', [props.dealer?.id, stockId])
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.stock.show', stockId)
    }

    return null
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

onMounted(() => {
    remainingSeconds.value = resolvedAutoRefreshSeconds.value
    autoRefreshIntervalId = window.setInterval(() => {
        if (remainingSeconds.value > 1) {
            remainingSeconds.value -= 1
            return
        }

        if (loading.value) {
            remainingSeconds.value = 1
            return
        }

        reloadRecordsOnly()
        remainingSeconds.value = resolvedAutoRefreshSeconds.value
    }, 1000)
})

onBeforeUnmount(() => {
    if (autoRefreshIntervalId !== null) {
        window.clearInterval(autoRefreshIntervalId)
        autoRefreshIntervalId = null
    }
})

watch(resolvedAutoRefreshSeconds, (value) => {
    remainingSeconds.value = value
})
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
            <div class="text-caption text-grey-7">{{ autoRefreshLabel }}</div>
        </div>
    </div>

    <DealerTabs v-if="context?.mode === 'dealer-backoffice' && dealer?.id" :page-tab="pageTab" :dealer-id="dealer.id" />
    <DealerConfigurationNav v-if="context?.mode === 'dealer'" tab="verify-payments" />

    <PaginatedTable
        ref="tableRef"
        title="Verify Payments"
        row-key="id"
        :records="records"
        :columns="visibleColumns"
        :fetch="fetchRecords"
        initial-sort-by="payment_date"
        :initial-descending="true"
    >
        <template #top-right>
            <div class="row q-col-gutter-sm items-center">
                <div class="col-auto" style="min-width: 240px;">
                    <q-input
                        v-model="search"
                        dense
                        outlined
                        clearable
                        debounce="700"
                        placeholder="Search invoice no/ref, customer, description..."
                        @update:model-value="goFirst"
                    />
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
                        @update:model-value="onPaymentMethodChange"
                    />
                </div>
                <div class="col-auto" style="min-width: 260px;">
                    <q-select
                        v-model="bankingDetailId"
                        dense
                        outlined
                        clearable
                        emit-value
                        map-options
                        :disable="!isEftPaymentMethod"
                        :options="bankingDetailOptions"
                        option-label="label"
                        option-value="value"
                        label="Banking Detail"
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
                    <q-input
                        :model-value="paymentDateFrom"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Date From"
                        @update:model-value="onPaymentDateFromChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="paymentDateFrom"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onPaymentDateFromChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="paymentDateFrom"
                                        mask="YYYY-MM-DD"
                                        :options="isPaymentDateFromAllowed"
                                        @update:model-value="onPaymentDateFromChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
                </div>
                <div class="col-auto" style="min-width: 170px;">
                    <q-input
                        :model-value="paymentDateTo"
                        dense
                        outlined
                        clearable
                        readonly
                        label="Date To"
                        @update:model-value="onPaymentDateToChange"
                    >
                        <template #append>
                            <q-icon
                                v-if="paymentDateTo"
                                name="close"
                                class="cursor-pointer q-mr-xs"
                                @click.stop="onPaymentDateToChange('')"
                            />
                            <q-icon name="event" class="cursor-pointer">
                                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                    <q-date
                                        :model-value="paymentDateTo"
                                        mask="YYYY-MM-DD"
                                        :options="isPaymentDateToAllowed"
                                        @update:model-value="onPaymentDateToChange"
                                    />
                                </q-popup-proxy>
                            </q-icon>
                        </template>
                    </q-input>
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
        <template #cell-stock_items="{ row }">
            <div v-if="Array.isArray(row.stock_items) && row.stock_items.length">
                <div v-for="stock in row.stock_items" :key="stock.id">
                    <q-btn
                        v-if="stockUrl(stock.id)"
                        flat
                        dense
                        no-caps
                        class="q-px-none"
                        color="primary"
                        :label="stock.internal_reference"
                        @click="router.visit(stockUrl(stock.id))"
                    />
                    <span v-else>{{ stock.internal_reference }}</span>
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
                    unelevated
                    no-caps
                    size="sm"
                    label="Show"
                    color="primary"
                    text-color="white"
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
