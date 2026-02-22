<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import AssociatedStockList from 'bo@/Components/Stock/AssociatedStockList.vue'
import AssociatedInvoicesTable from 'bo@/Components/Shared/AssociatedInvoicesTable.vue'
import SimpleTable from 'bo@/Components/Shared/SimpleTable.vue'
import PaymentEditAction from 'bo@/Components/Shared/PaymentEditAction.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Payments' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'payments' },
    payment: { type: Object, required: true },
    associatedStock: { type: Array, default: () => [] },
    associatedInvoices: { type: Array, default: () => [] },
    verifications: { type: Array, default: () => [] },
    canViewAssociatedInvoices: { type: Boolean, default: false },
    bankingDetailOptions: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    updateRoute: { type: String, default: '' },
    canVerify: { type: Boolean, default: false },
    verifyUrl: { type: String, default: '' },
    currencySymbol: { type: String, default: 'N$' },
    returnTo: { type: String, required: true },
})

const loading = ref(false)
const editDialog = ref(false)
const { confirmAction } = useConfirmAction(loading)

const editPaymentMethodOptions = [
    { label: 'CASH', value: 'cash' },
    { label: 'EFT', value: 'eft' },
    { label: 'FINANCE HOUSE', value: 'finance_house' },
    { label: 'CARD', value: 'card' },
]

const form = useForm({
    payment_date: '',
    payment_method: 'cash',
    banking_detail_id: null,
    amount: null,
    description: '',
})

const confirmVerify = () => {
    if (!props.verifyUrl) return
    const invoiceRef = props.payment?.invoice_identifier || '-'
    const invoiceDate = props.payment?.invoice_date || '-'
    const total = props.payment?.amount == null ? '-' : `${props.currencySymbol}${formatCurrency(props.payment.amount, 2)}`

    confirmAction({
        title: 'Verify Payment',
        message: `Verify payment for invoice ${invoiceRef}?\nInvoice Date: ${invoiceDate}\nPayment Amount: ${total}`,
        okLabel: 'Verify',
        okColor: 'positive',
        cancelLabel: 'Cancel',
        method: 'post',
        actionUrl: props.verifyUrl,
        inertia: { preserveState: true },
    })
}

const verificationColumns = [
    { name: 'verified_at', label: 'Verified Date', field: 'verified_at', align: 'left' },
    { name: 'verified_by', label: 'Verified By', field: 'verified_by', align: 'left' },
    { name: 'verified_by_guard', label: 'Verified Guard', field: 'verified_by_guard', align: 'left' },
    { name: 'amount_verified', label: 'Amount Verified', field: 'amount_verified', align: 'right' },
]

const openEdit = () => {
    if (!props.canEdit || props.payment?.is_approved) {
        return
    }

    form.payment_date = props.payment?.payment_date || ''
    form.payment_method = String(props.payment?.payment_method || 'cash').toLowerCase()
    form.banking_detail_id = props.payment?.banking_detail_id || null
    form.amount = Number(props.payment?.amount || 0)
    form.description = props.payment?.description || ''
    form.clearErrors()
    editDialog.value = true
}

const submitEdit = () => {
    if (!props.updateRoute || props.payment?.is_approved) {
        return
    }

    form.patch(props.updateRoute, {
        preserveScroll: true,
        onSuccess: () => {
            editDialog.value = false
        },
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
        <div class="row items-center q-gutter-sm">
            <PaymentEditAction
                :can-edit="canEdit"
                :is-approved="!!payment?.is_approved"
                :icon-mode="false"
                label="Edit Payment"
                @click="openEdit"
            />
            <q-btn
                v-if="canVerify && !payment?.is_approved"
                color="positive"
                unelevated
                no-wrap
                label="Verify"
                @click="confirmVerify"
            />
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="router.visit(returnTo)" />
        </div>
    </div>

    <DealerTabs v-if="context?.mode === 'dealer-backoffice' && dealer?.id" :page-tab="pageTab" :dealer-id="dealer.id" />
    <DealerConfigurationNav v-if="context?.mode === 'dealer'" tab="payments" />

    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">Payment</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Invoice" :model-value="payment.invoice_identifier || '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Invoice Date" :model-value="payment.invoice_date || '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Payment Date" :model-value="payment.payment_date || '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Payment Method" :model-value="String(payment.payment_method || '-').toUpperCase()" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Amount" :model-value="payment.amount === null || payment.amount === undefined ? '-' : `${currencySymbol}${formatCurrency(payment.amount, 2)}`" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Banking Details" :model-value="payment.banking_detail_bank_account || '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Bank and Account Number" :model-value="String(payment.payment_method || '').toLowerCase() === 'eft' ? (payment.banking_detail_bank_account || '-') : '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="Recorded By" :model-value="payment.recorded_by || '-'" />
                </div>
                <div class="col-12 col-md-6">
                    <q-input dense outlined readonly label="IP Address" :model-value="payment.recorded_ip || '-'" />
                </div>
                <div class="col-12">
                    <q-input dense outlined readonly label="Description" :model-value="payment.description || '-'" />
                </div>
            </div>
        </q-card-section>
    </q-card>

    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">Payment Verification</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div v-if="!verifications?.length" class="text-caption text-grey-7">No verification recorded yet.</div>
            <SimpleTable
                v-else
                :rows="verifications"
                :columns="verificationColumns"
                row-key="id"
                :bordered="false"
            >
                <template #cell-amount_verified="{ row }">
                    <div class="text-right">
                        {{ row.amount_verified === null || row.amount_verified === undefined ? '-' : `${currencySymbol}${formatCurrency(row.amount_verified, 2)}` }}
                    </div>
                </template>
            </SimpleTable>
        </q-card-section>
    </q-card>

    <AssociatedInvoicesTable
        v-if="canViewAssociatedInvoices"
        title="Associated Invoice"
        :rows="associatedInvoices"
        :currency-symbol="currencySymbol"
    />

    <AssociatedStockList :items="associatedStock" title="Associated Stock Items" />

    <q-dialog v-model="editDialog" persistent>
        <q-card style="min-width: 560px; max-width: 90vw;">
            <q-card-section>
                <div class="text-h6">Edit Payment</div>
            </q-card-section>
            <q-separator />
            <q-card-section>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="form.payment_date"
                            dense
                            outlined
                            label="Payment Date"
                            mask="####-##-##"
                            fill-mask
                            :error="!!form.errors.payment_date"
                            :error-message="form.errors.payment_date"
                        />
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
                        <q-input
                            v-model.number="form.amount"
                            dense
                            outlined
                            type="number"
                            min="0"
                            max="999999999.99"
                            step="0.01"
                            label="Amount"
                            :error="!!form.errors.amount"
                            :error-message="form.errors.amount"
                        />
                    </div>
                    <div class="col-12">
                        <q-input
                            v-model="form.description"
                            dense
                            outlined
                            label="Description"
                            maxlength="100"
                            counter
                            :error="!!form.errors.description"
                            :error-message="form.errors.description"
                        />
                    </div>
                </div>
            </q-card-section>
            <q-separator />
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="editDialog = false" />
                <q-btn color="primary" unelevated :loading="form.processing" label="Save" @click="submitEdit" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
