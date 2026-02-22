<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import PaginatedTable from 'bo@/Components/Shared/PaginatedTable.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'
import PaymentEditAction from 'bo@/Components/Shared/PaymentEditAction.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })
const page = usePage()

const props = defineProps({
    publicTitle: { type: String, default: 'Payments' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'payments' },
    records: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    canCreate: { type: Boolean, default: false },
    bankingDetailOptions: { type: Array, default: () => [] },
    verificationStatusOptions: { type: Array, default: () => [] },
})

const tableRef = ref(null)
const loading = ref(false)
const dialog = ref(false)
const editingId = ref(null)
const { confirmAction } = useConfirmAction(loading)
const search = ref(props.filters?.search ?? '')
const paymentMethod = ref(props.filters?.payment_method ?? '')
const verificationStatus = ref(props.filters?.verification_status ?? 'all')
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

const editPaymentMethodOptions = paymentMethodOptions.filter((item) => item.value !== '')

const form = useForm({
    description: '',
    amount: null,
    payment_date: '',
    payment_method: 'cash',
    banking_detail_id: null,
})

const columns = [
    { name: 'payment_date', label: 'Payment Date', sortable: true, align: 'left', field: 'payment_date' },
    { name: 'invoice_identifier', label: 'Invoice', sortable: true, align: 'left', field: 'invoice_identifier' },
    { name: 'payment_method', label: 'Method', sortable: true, align: 'left', field: 'payment_method' },
    { name: 'verification_status', label: 'Verified', sortable: false, align: 'center', field: 'verification_status' },
    { name: 'last_verified_at', label: 'Verified Date', sortable: false, align: 'left', field: 'last_verified_at' },
    { name: 'description', label: 'Description', sortable: false, align: 'left', field: 'description' },
    { name: 'linked_stock_items_count', label: 'Stock Linked', sortable: false, align: 'right', field: 'linked_stock_items_count', numeric: true },
    { name: 'recorded_by', label: 'Recorded By', sortable: false, align: 'left', field: 'recorded_by' },
    { name: 'recorded_ip', label: 'IP Address', sortable: false, align: 'left', field: 'recorded_ip' },
    { name: 'amount', label: 'Amount', sortable: true, align: 'right', field: 'amount', numeric: true },
    { name: 'actions', label: '', sortable: false, align: 'right', field: 'actions' },
]

const visibleColumns = computed(() => {
    if (props.context?.mode === 'system') {
        return columns.filter((column) => column.name !== 'linked_stock_items_count')
    }

    return columns
})

const indexRoute = computed(() => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.payments.index', props.dealer?.id)
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.payments.index')
    }
    return route('backoffice.system.payments.index')
})

const currentUrl = computed(() => page.url || indexRoute.value)

const fetchRecords = (pagination, helpers) => {
    router.get(indexRoute.value, {
        page: pagination?.page,
        rowsPerPage: pagination?.rowsPerPage,
        sortBy: pagination?.sortBy,
        descending: pagination?.descending,
        search: search.value || '',
        payment_method: paymentMethod.value || '',
        verification_status: verificationStatus.value || 'all',
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

const goFirst = () => tableRef.value?.goFirstPage()

const updateUrl = (paymentId) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.payments.update', { dealer: props.dealer?.id, payment: paymentId })
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.payments.update', { payment: paymentId })
    }
    return route('backoffice.system.payments.update', { payment: paymentId })
}

const showUrl = (paymentId) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.payments.show', { dealer: props.dealer?.id, payment: paymentId, return_to: currentUrl.value })
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.payments.show', { payment: paymentId, return_to: currentUrl.value })
    }
    return route('backoffice.system.payments.show', { payment: paymentId, return_to: currentUrl.value })
}

const deleteUrl = (paymentId) => {
    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.payments.destroy', { dealer: props.dealer?.id, payment: paymentId })
    }
    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.payments.destroy', { payment: paymentId })
    }
    return route('backoffice.system.payments.destroy', { payment: paymentId })
}

const openEdit = (row) => {
    if (row?.is_approved) {
        return
    }

    editingId.value = row.id
    form.description = row.description || ''
    form.amount = Number(row.amount || 0)
    form.payment_date = row.payment_date || ''
    form.payment_method = String(row.payment_method_value || row.payment_method || 'cash').toLowerCase()
    form.banking_detail_id = row.banking_detail_id || null
    form.clearErrors()
    dialog.value = true
}

const submit = () => {
    form.patch(updateUrl(editingId.value), {
        preserveScroll: true,
        onSuccess: () => { dialog.value = false },
    })
}

const confirmDelete = (row) => {
    if (row?.is_approved) {
        return
    }

    confirmAction({
        title: 'Delete Payment',
        message: 'Are you sure you want to delete this payment?',
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: deleteUrl(row.id),
        inertia: { preserveState: true },
    })
}

const goShow = (row) => {
    router.visit(showUrl(row.id))
}

const invoiceUrl = (row) => {
    if (!row?.invoice_id) return null

    if (props.context?.mode === 'dealer-backoffice') {
        return route('backoffice.dealer-management.dealers.invoices.edit', {
            dealer: props.dealer?.id,
            invoice: row.invoice_id,
            return_to: currentUrl.value,
        })
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.invoices.edit', {
            invoice: row.invoice_id,
            return_to: currentUrl.value,
        })
    }

    return route('backoffice.system.invoices.edit', {
        invoice: row.invoice_id,
        return_to: currentUrl.value,
    })
}

const openInvoice = (row) => {
    const url = invoiceUrl(row)
    if (!url) return
    router.visit(url)
}

const truncateText = (value, limit = 50) => {
    const text = String(value || '')
    if (text.length <= limit) return text || '-'
    return `${text.slice(0, limit)}...`
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
    <DealerConfigurationNav v-if="context?.mode === 'dealer'" tab="payments" />

    <PaginatedTable
        ref="tableRef"
        title="Payments"
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
                        placeholder="Search invoice ref, description, method, amount, IP"
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

        <template #cell-amount="{ row }">
            <span>{{ row.amount === null || row.amount === undefined ? '-' : `N$${formatCurrency(row.amount, 2)}` }}</span>
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

        <template #cell-description="{ row }">
            <span>{{ truncateText(row.description, 50) }}</span>
        </template>

        <template #cell-verification_status="{ row }">
            <q-chip
                square
                dense
                size="sm"
                :color="row.is_approved ? 'positive' : 'negative'"
                text-color="white"
            >
                {{ row.is_approved ? 'VERIFIED' : 'UNVERIFIED' }}
            </q-chip>
        </template>

        <template #cell-last_verified_at="{ row }">
            <span>{{ row.last_verified_at || '-' }}</span>
        </template>

        <template #actions="{ row }">
            <q-btn v-if="row.can?.view" round dense flat icon="visibility" @click="goShow(row)" />
            <PaymentEditAction
                :can-edit="!!row.can?.edit"
                :is-approved="!!row.is_approved"
                @click="openEdit(row)"
            />
            <q-btn v-if="row.can?.delete && !row.is_approved" round dense flat icon="delete" color="negative" @click.stop="confirmDelete(row)" />
        </template>
    </PaginatedTable>

    <q-dialog v-model="dialog" persistent>
        <q-card style="min-width: 560px; max-width: 90vw;">
            <q-card-section><div class="text-h6">Edit Payment</div></q-card-section>
            <q-separator />
            <q-card-section>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-input v-model="form.payment_date" dense outlined label="Payment Date" mask="####-##-##" fill-mask :error="!!form.errors.payment_date" :error-message="form.errors.payment_date" />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-select
                            v-model="form.payment_method"
                            dense
                            outlined
                            emit-value
                            map-options
                            :options="editPaymentMethodOptions"
                            option-label="label"
                            option-value="value"
                            label="Payment Method"
                            :error="!!form.errors.payment_method"
                            :error-message="form.errors.payment_method"
                        />
                    </div>
                    <div class="col-12" v-if="form.payment_method === 'eft'">
                        <q-select
                            v-model="form.banking_detail_id"
                            dense
                            outlined
                            emit-value
                            map-options
                            :options="bankingDetailOptions"
                            option-label="label"
                            option-value="value"
                            label="Banking Detail"
                            :error="!!form.errors.banking_detail_id"
                            :error-message="form.errors.banking_detail_id"
                        />
                    </div>
                    <div class="col-12">
                        <q-input v-model.number="form.amount" dense outlined type="number" min="0" max="999999999.99" step="0.01" label="Amount" :error="!!form.errors.amount" :error-message="form.errors.amount" />
                    </div>
                    <div class="col-12">
                        <q-input v-model="form.description" dense outlined label="Description" maxlength="100" counter :error="!!form.errors.description" :error-message="form.errors.description" />
                    </div>
                </div>
            </q-card-section>
            <q-separator />
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="dialog = false" />
                <q-btn color="primary" unelevated :loading="form.processing" label="Save" @click="submit" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
