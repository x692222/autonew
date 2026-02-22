<script setup>
import { Head, router } from '@inertiajs/vue3'
import Layout from 'bo@/Layouts/Layout.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import AssociatedQuotationsTable from 'bo@/Components/Shared/AssociatedQuotationsTable.vue'
import AssociatedInvoicesTable from 'bo@/Components/Shared/AssociatedInvoicesTable.vue'
import AssociatedPaymentsTable from 'bo@/Components/Shared/AssociatedPaymentsTable.vue'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Customers' },
    context: { type: Object, required: true },
    dealer: { type: Object, default: null },
    pageTab: { type: String, default: 'customers' },
    data: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    associatedQuotations: { type: Array, default: () => [] },
    canViewAssociatedQuotations: { type: Boolean, default: false },
    associatedInvoices: { type: Array, default: () => [] },
    canViewAssociatedInvoices: { type: Boolean, default: false },
    associatedPayments: { type: Array, default: () => [] },
    canViewAssociatedPayments: { type: Boolean, default: false },
    editRoute: { type: String, default: null },
    indexRoute: { type: String, required: true },
    returnTo: { type: String, required: true },
    currencySymbol: { type: String, default: 'N$' },
})
</script>

<template>
    <Head><title>{{ $page.props.appName }}</title></Head>

    <div class="row nowrap justify-between items-center q-mb-md">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div v-if="dealer?.name" class="text-caption text-grey-7">{{ dealer.name }}</div>
        </div>
        <div class="q-gutter-sm">
            <q-btn v-if="editRoute" color="primary" unelevated label="Edit Customer" @click="router.visit(editRoute)" />
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="router.visit(returnTo)" />
        </div>
    </div>

    <DealerTabs
        v-if="context?.mode === 'dealer-backoffice' && dealer?.id"
        :page-tab="pageTab"
        :dealer-id="dealer.id"
    />

    <DealerConfigurationNav
        v-if="context?.mode === 'dealer'"
        tab="customers"
    />

    <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-lg-8">
            <q-card flat bordered class="q-mb-md">
                <q-card-section>
                    <div class="text-h6">Customer Information</div>
                </q-card-section>
                <q-separator />
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Type" :model-value="String(data.type || '-').toUpperCase()" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Title" :model-value="data.title || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Firstname" :model-value="data.firstname || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Lastname" :model-value="data.lastname || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="ID Number" :model-value="data.id_number || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Email" :model-value="data.email || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="Contact Number" :model-value="data.contact_number || '-'" />
                        </div>
                        <div class="col-12 col-md-6">
                            <q-input dense outlined readonly label="VAT Number" :model-value="data.vat_number || '-'" />
                        </div>
                        <div class="col-12">
                            <q-input
                                dense
                                outlined
                                readonly
                                autogrow
                                type="textarea"
                                :rows="5"
                                label="Address"
                                :model-value="data.address || '-'"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <AssociatedQuotationsTable
                v-if="canViewAssociatedQuotations"
                :rows="associatedQuotations"
                :currency-symbol="currencySymbol"
            />

            <AssociatedInvoicesTable
                v-if="canViewAssociatedInvoices"
                :rows="associatedInvoices"
                :currency-symbol="currencySymbol"
            />

            <AssociatedPaymentsTable
                v-if="canViewAssociatedPayments"
                :rows="associatedPayments"
                :currency-symbol="currencySymbol"
            />
        </div>

        <div class="col-12 col-lg-4">
            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6">Summary</div>
                </q-card-section>
                <q-separator />
                <q-list dense>
                    <q-item>
                        <q-item-section>Total Quotes</q-item-section>
                        <q-item-section side>{{ summary.total_quotes ?? 0 }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Total Invoices</q-item-section>
                        <q-item-section side>{{ summary.total_invoices ?? 0 }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Total Quote Value</q-item-section>
                        <q-item-section side>{{ currencySymbol }}{{ formatCurrency(summary.total_quote_value || 0, 2) }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Total Invoice Value</q-item-section>
                        <q-item-section side>{{ currencySymbol }}{{ formatCurrency(summary.total_invoice_value || 0, 2) }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Total Outstanding</q-item-section>
                        <q-item-section side>{{ currencySymbol }}{{ formatCurrency(summary.total_outstanding || 0, 2) }}</q-item-section>
                    </q-item>
                    <q-separator inset />
                    <q-item>
                        <q-item-section>Last Quotation</q-item-section>
                        <q-item-section side>{{ summary.last_quotation_date || '-' }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Last Invoice</q-item-section>
                        <q-item-section side>{{ summary.last_invoice_date || '-' }}</q-item-section>
                    </q-item>
                    <q-item>
                        <q-item-section>Last Payment</q-item-section>
                        <q-item-section side>{{ summary.last_payment_date || '-' }}</q-item-section>
                    </q-item>
                </q-list>
            </q-card>
        </div>
    </div>
</template>
