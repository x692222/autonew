<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import axios from 'axios'
import { useQuasar } from 'quasar'
import Layout from 'bo@/Layouts/Layout.vue'
import NotesHost from 'bo@/Components/Notes/NotesHost.vue'
import AssociatedStockList from 'bo@/Components/Stock/AssociatedStockList.vue'
import DealerTabs from 'bo@/Pages/GuardBackoffice/DealerManagement/Dealers/_Tabs.vue'
import DealerConfigurationNav from 'bo@/Pages/GuardDealer/DealerConfiguration/_Nav.vue'
import { useConfirmAction } from 'bo@/Composables/useConfirmAction'
import { formatCurrency } from 'bo@/Composables/currencyFormatterService'

defineOptions({ layout: Layout })

const props = defineProps({
    publicTitle: { type: String, default: 'Quotations' },
    dealer: { type: Object, default: null },
    context: { type: Object, required: true },
    data: { type: Object, default: null },
    customerTypeOptions: { type: Array, default: () => [] },
    sectionOptions: { type: Array, default: () => [] },
    vat: { type: Object, default: () => ({ vat_enabled: false, vat_percentage: null, vat_number: null }) },
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    canShowNotes: { type: Boolean, default: false },
    canCreateCustomer: { type: Boolean, default: false },
    canConvertToInvoice: { type: Boolean, default: false },
    convertToInvoiceRoute: { type: String, default: null },
    linkedInvoices: { type: Array, default: () => [] },
    indexRoute: { type: String, required: true },
    storeRoute: { type: String, required: true },
    updateRoute: { type: String, default: null },
    destroyRoute: { type: String, default: null },
    exportRoute: { type: String, default: null },
    customerSearchRoute: { type: String, required: true },
    customerStoreRoute: { type: String, required: true },
    lineItemSuggestionRoute: { type: String, required: true },
    returnTo: { type: String, required: true },
    currencySymbol: { type: String, default: 'N$' },
    contactNoPrefix: { type: String, default: '' },
})

const page = usePage()
const notesRef = ref(null)
const loading = ref(false)
const creatingCustomer = ref(false)
const customerSearchLoading = ref(false)
const customerOptions = ref([])
const customerSearchHint = ref('Type at least 3 characters to search customer.')
const addCustomerDialog = ref(false)
const convertDialog = ref(false)
const convertForm = ref({
    has_custom_invoice_identifier: false,
    invoice_identifier: '',
})

const lineItemTimeouts = new Map()
const lineItemSuggestions = ref({})
const skuSuggestionDebounceMs = 500
const canSearchSkuSuggestions = computed(() => props.context?.mode !== 'system')
const canOpenLinkedStock = computed(() => ['dealer', 'dealer-backoffice'].includes(props.context?.mode))
const skuInputRefs = new Map()
const $q = useQuasar()

const { confirmAction } = useConfirmAction(loading)
const currentUrl = computed(() => page.url || props.returnTo)

const sanitizeContactNumber = (value) => String(value || '').replace(/\s+/g, '')
const sanitizeVatNumber = (value) => String(value || '').replace(/\s+/g, '').replace(/[^A-Za-z0-9/-]/g, '').slice(0, 35)
const sanitizeSku = (value) => String(value || '').replace(/\s+/g, '').slice(0, 35)
const defaultContactNoPrefix = sanitizeContactNumber(props.contactNoPrefix || '')

const buildEmptyLineItem = (sectionValue, defaultVatExempt) => ({
    __key: `${Date.now()}-${Math.random()}`,
    section: sectionValue,
    stock_id: null,
    sku: '',
    description: '',
    amount: 0,
    qty: 1,
    total: 0,
    is_vat_exempt: !!defaultVatExempt,
})

const setSkuInputRef = (key, componentRef) => {
    if (!componentRef) {
        skuInputRefs.delete(key)
        return
    }

    skuInputRefs.set(key, componentRef)
}

const focusSkuInput = (key) => {
    const componentRef = skuInputRefs.get(key)
    if (!componentRef) return

    if (typeof componentRef.focus === 'function') {
        componentRef.focus()
        return
    }

    const input = componentRef?.$el?.querySelector?.('input')
    input?.focus?.()
}

const initialLineItems = (() => {
    if (props.data?.line_items?.length) {
        return props.data.line_items.map((item) => ({
            __key: `${item.id || Date.now()}-${Math.random()}`,
            section: item.section,
            stock_id: item.stock_id,
            sku: item.sku || '',
            description: item.description || '',
            amount: Number(item.amount || 0),
            qty: Number(item.qty || 0),
            total: Number(item.total || 0),
            is_vat_exempt: !!item.is_vat_exempt,
        }))
    }

    return []
})()

const form = useForm({
    customer_id: props.data?.customer_id || null,
    has_custom_quote_identifier: !!props.data?.has_custom_quote_identifier,
    quote_identifier: props.data?.quote_identifier || '',
    quotation_date: props.data?.quotation_date || new Date().toISOString().slice(0, 10),
    valid_for_days: props.data?.valid_for_days ?? 7,
    line_items: initialLineItems,
    return_to: props.returnTo,
})

const customerForm = useForm({
    type: 'individual',
    title: '',
    firstname: '',
    lastname: '',
    id_number: '',
    email: '',
    contact_number: defaultContactNoPrefix,
    address: '',
    vat_number: '',
})

if (props.data?.customer_id && props.data?.customer_label) {
    customerOptions.value = [
        {
            value: props.data.customer_id,
            label: props.data.customer_label,
            ...(props.data.customer || {}),
        },
    ]
}

const sectionMap = computed(() => {
    const map = {}
    for (const section of props.sectionOptions) {
        map[section.value] = section
    }
    return map
})

const groupedSections = computed(() => {
    const groups = {}

    for (const section of props.sectionOptions) {
        groups[section.value] = {
            ...section,
            lineItems: [],
        }
    }

    form.line_items.forEach((lineItem, index) => {
        if (!groups[lineItem.section]) return
        groups[lineItem.section].lineItems.push({
            index,
            item: lineItem,
        })
    })

    return Object.values(groups)
})

const sectionSubtotal = (section) => {
    const sign = section?.adds === false ? -1 : 1
    const subtotal = (section?.lineItems || []).reduce((carry, row) => {
        const value = Number(row?.item?.total || 0)
        return carry + (sign * value)
    }, 0)

    return Math.round(subtotal * 100) / 100
}

const formatAmount = (value) => formatCurrency(Number(value || 0), 2)

const signedTotal = (lineItem) => {
    const section = sectionMap.value[lineItem.section]
    const sign = section?.adds === false ? -1 : 1
    return sign * Number(lineItem.total || 0)
}

const signedVatPortionForLineItem = (lineItem) => {
    const rate = Number(props.vat?.vat_percentage || 0)
    if (!props.vat?.vat_enabled || rate <= 0) return 0

    return signedTotal(lineItem) * (rate / (100 + rate))
}

const signedPayableTotalForLineItem = (lineItem) => {
    const signed = signedTotal(lineItem)
    if (!lineItem.is_vat_exempt) return signed

    return signed - signedVatPortionForLineItem(lineItem)
}

const totals = computed(() => {
    let totalAmount = 0
    let vatAmount = 0

    for (const lineItem of form.line_items) {
        totalAmount += signedPayableTotalForLineItem(lineItem)
        if (!lineItem.is_vat_exempt) {
            vatAmount += signedVatPortionForLineItem(lineItem)
        }
    }

    totalAmount = Math.round(totalAmount * 100) / 100
    vatAmount = Math.round(vatAmount * 100) / 100
    const subtotalBeforeVat = Math.round((totalAmount - vatAmount) * 100) / 100

    return { subtotalBeforeVat, vatAmount, totalAmount }
})

const dateRules = [
    (value) => !!value || 'Quotation date is required',
    (value) => /^\d{4}-\d{2}-\d{2}$/.test(String(value || '')) || 'Date format must be YYYY-MM-DD',
]

const formatDateInput = (value) => {
    const normalized = String(value || '')
        .replace(/[^\d]/g, '')
        .slice(0, 8)

    if (normalized.length <= 4) return normalized
    if (normalized.length <= 6) return `${normalized.slice(0, 4)}-${normalized.slice(4)}`
    return `${normalized.slice(0, 4)}-${normalized.slice(4, 6)}-${normalized.slice(6, 8)}`
}

const addLineItem = (section) => {
    const newLineItem = buildEmptyLineItem(section.value, section.default_vat_exempt)
    form.line_items.push(newLineItem)

    nextTick(() => focusSkuInput(newLineItem.__key))
}

const removeLineItem = (lineItem) => {
    form.line_items = form.line_items.filter((item) => item.__key !== lineItem.__key)
    delete lineItemSuggestions.value[lineItem.__key]
}

const confirmRemoveLineItem = (lineItem) => {
    $q.dialog({
        title: 'Remove Line Item',
        message: 'Are you sure you want to remove this line item?',
        ok: {
            label: 'Remove',
            color: 'negative',
            unelevated: true,
        },
        cancel: {
            label: 'Cancel',
            flat: true,
        },
        persistent: true,
    }).onOk(() => {
        removeLineItem(lineItem)
    })
}

const recalculateLineItem = (lineItem) => {
    const amount = Number(lineItem.amount || 0)
    const qty = Number(lineItem.qty || 0)
    lineItem.total = Math.round((amount * qty) * 100) / 100
}

const queueLineItemSuggestionSearch = (lineItem) => {
    if (!canSearchSkuSuggestions.value) {
        lineItemSuggestions.value[lineItem.__key] = []
        return
    }

    const key = lineItem.__key
    if (lineItemTimeouts.has(key)) {
        clearTimeout(lineItemTimeouts.get(key))
    }

    lineItem.sku = sanitizeSku(lineItem.sku)
    const sku = (lineItem.sku || '').trim()
    if (sku.length < 3) {
        lineItemSuggestions.value[key] = []
        return
    }

    lineItemTimeouts.set(key, setTimeout(async () => {
        try {
            const response = await axios.get(props.lineItemSuggestionRoute, {
                params: {
                    q: sku,
                    section: lineItem.section,
                },
            })

            const stockRows = (response?.data?.stock || []).map((item) => ({ ...item, __group: 'Stock Matches' }))
            const historyRows = (response?.data?.history || []).map((item) => ({ ...item, __group: 'Saved Items' }))
            lineItemSuggestions.value[key] = [...stockRows, ...historyRows]
        } catch {
            lineItemSuggestions.value[key] = []
        }
    }, skuSuggestionDebounceMs))
}

const applySuggestion = (lineItem, suggestion) => {
    lineItem.stock_id = suggestion.stock_id || null
    lineItem.sku = sanitizeSku(suggestion.sku || '')
    lineItem.description = suggestion.description || ''
    lineItem.amount = Number(suggestion.amount || 0)
    lineItem.qty = Number(suggestion.qty || 1)
    lineItem.total = Number(suggestion.total || lineItem.amount || 0)
    lineItemSuggestions.value[lineItem.__key] = []
}

const suggestionStockRows = (lineItem) => (lineItemSuggestions.value[lineItem.__key] || []).filter((item) => item.__group === 'Stock Matches')
const suggestionHistoryRows = (lineItem) => (lineItemSuggestions.value[lineItem.__key] || []).filter((item) => item.__group === 'Saved Items')

const linkedStockUrl = (lineItem) => {
    if (!canOpenLinkedStock.value || !lineItem?.stock_id) return null

    if (props.context?.mode === 'dealer-backoffice' && props.dealer?.id) {
        return route('backoffice.dealer-management.dealers.stock.show', [props.dealer.id, lineItem.stock_id])
    }

    if (props.context?.mode === 'dealer') {
        return route('backoffice.dealer-configuration.stock.show', lineItem.stock_id)
    }

    return null
}

const openLinkedStock = (lineItem) => {
    const url = linkedStockUrl(lineItem)
    if (!url) return

    if (typeof window !== 'undefined') {
        window.open(url, '_blank', 'noopener')
    }
}

const filterCustomers = (value, update) => {
    const query = (value || '').trim()

    if (query.length < 3) {
        update(() => {
            customerOptions.value = props.data?.customer_id && props.data?.customer_label
                ? [{ value: props.data.customer_id, label: props.data.customer_label, ...(props.data.customer || {}) }]
                : []
            customerSearchHint.value = 'Type at least 3 characters to search customer.'
        })
        return
    }

    customerSearchHint.value = 'Searching customers...'
    customerSearchLoading.value = true

    axios.get(props.customerSearchRoute, {
        params: { q: query },
    }).then((response) => {
        update(() => {
            customerOptions.value = response?.data?.options || []
            customerSearchHint.value = customerOptions.value.length === 0 ? 'No matching customers found.' : ''
        })
    }).catch(() => {
        update(() => {
            customerOptions.value = []
            customerSearchHint.value = 'Unable to search customers right now.'
        })
    }).finally(() => {
        customerSearchLoading.value = false
    })
}

const selectedCustomer = computed(() => {
    const id = form.customer_id
    if (!id) return null
    const fromOptions = (customerOptions.value || []).find((option) => String(option.value) === String(id))
    if (fromOptions) return fromOptions

    if (props.data?.customer && String(props.data.customer.id) === String(id)) {
        return {
            value: props.data.customer.id,
            label: props.data.customer_label || `${props.data.customer.firstname || ''} ${props.data.customer.lastname || ''}`.trim(),
            ...props.data.customer,
        }
    }

    return null
})

const openAddCustomer = () => {
    if (!props.canCreateCustomer) return
    customerForm.reset()
    customerForm.contact_number = defaultContactNoPrefix
    customerForm.clearErrors()
    addCustomerDialog.value = true
}

const customerDisplayName = computed(() => {
    if (!selectedCustomer.value) return '-'
    const name = `${selectedCustomer.value.firstname || ''} ${selectedCustomer.value.lastname || ''}`.trim()
    return name || selectedCustomer.value.label || '-'
})

const formattedCustomerAddress = computed(() => {
    const address = selectedCustomer.value?.address
    if (!address) return '-'

    return String(address).replace(/\r\n/g, '\n').trim() || '-'
})

const submitAddCustomer = async () => {
    creatingCustomer.value = true
    customerForm.clearErrors()
    customerForm.contact_number = sanitizeContactNumber(customerForm.contact_number)
    customerForm.vat_number = sanitizeVatNumber(customerForm.vat_number)

    try {
        const response = await axios.post(props.customerStoreRoute, customerForm.data())
        const created = response?.data || null

        if (created?.id) {
            form.customer_id = created.id
            customerOptions.value = [
                ...customerOptions.value.filter((option) => option.value !== created.id),
                {
                    value: created.id,
                    label: created.label || created.id,
                    ...created,
                },
            ]
        }

        addCustomerDialog.value = false
    } catch (error) {
        const errors = error?.response?.data?.errors || {}
        Object.entries(errors).forEach(([key, value]) => {
            const message = Array.isArray(value) ? value[0] : value
            customerForm.setError(key, message || 'Invalid value.')
        })
    } finally {
        creatingCustomer.value = false
    }
}

const submit = () => {
    form.line_items.forEach((lineItem) => recalculateLineItem(lineItem))

    const basePayload = {
        ...form.data(),
    }

    if (isEditing.value) {
        delete basePayload.has_custom_quote_identifier
        delete basePayload.quote_identifier
    }

    const payload = {
        ...basePayload,
        line_items: form.line_items.map((lineItem) => ({
            section: lineItem.section,
            stock_id: lineItem.stock_id || null,
            sku: sanitizeSku(lineItem.sku) || null,
            description: lineItem.description || '',
            amount: Number(lineItem.amount || 0),
            qty: Number(lineItem.qty || 0),
            total: Number(lineItem.total || 0),
            is_vat_exempt: !!lineItem.is_vat_exempt,
        })),
    }

    const requestOptions = {
        preserveScroll: true,
        onError: () => {
            if (typeof window !== 'undefined') {
                window.scrollTo({ top: 0, behavior: 'smooth' })
            }
        },
    }

    if (props.data?.id && props.updateRoute) {
        form.transform(() => payload).patch(props.updateRoute, requestOptions)
        return
    }

    form.transform(() => payload).post(props.storeRoute, requestOptions)
}

const hasFormErrors = computed(() => Object.keys(form.errors || {}).length > 0)
const showUnsavedChanges = computed(() => !!props.data?.id && form.isDirty)
const isEditing = computed(() => !!props.data?.id)
const lineItemError = (index, field) => form.errors?.[`line_items.${index}.${field}`] || null
const openNotes = () => {
    if (!props.data?.id) return
    notesRef.value?.open({ id: props.data.id, quote_identifier: props.data.quote_identifier })
}

const confirmDelete = () => {
    if (!props.destroyRoute) return

    confirmAction({
        title: 'Delete Quotation',
        message: `Are you sure you want to delete quotation ${props.data?.quote_identifier || ''}?`,
        okLabel: 'Delete',
        okColor: 'negative',
        cancelLabel: 'Cancel',
        method: 'delete',
        actionUrl: props.destroyRoute,
        inertia: { preserveState: false },
    })
}

const confirmExport = () => {
    if (!props.exportRoute) return

    confirmAction({
        title: 'Export Quotation',
        message: 'Export this quotation to PDF?',
        okLabel: 'Export',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'get',
        actionUrl: props.exportRoute,
        inertia: { preserveState: true },
    })
}

const openConvertToInvoiceDialog = () => {
    convertForm.value = {
        has_custom_invoice_identifier: false,
        invoice_identifier: '',
    }
    convertDialog.value = true
}

const confirmConvertToInvoice = () => {
    const hasCustom = !!convertForm.value.has_custom_invoice_identifier
    const invoiceIdentifier = String(convertForm.value.invoice_identifier || '').trim()

    if (hasCustom && invoiceIdentifier.length === 0) {
        $q.notify({
            type: 'negative',
            message: 'Please enter a custom invoice identifier or disable custom mode.',
            position: 'top-right',
            timeout: 3500,
        })
        return
    }

    convertDialog.value = false

    confirmAction({
        title: 'Convert To Invoice',
        message: 'Create an invoice from this quotation?',
        okLabel: 'Convert',
        okColor: 'primary',
        cancelLabel: 'Cancel',
        method: 'post',
        actionUrl: props.convertToInvoiceRoute,
        data: {
            has_custom_invoice_identifier: hasCustom,
            invoice_identifier: hasCustom ? invoiceIdentifier : null,
            return_to: currentUrl.value,
        },
        inertia: { preserveState: true, preserveScroll: true },
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
        <q-btn color="grey-7" text-color="white" label="Back" no-wrap unelevated @click="router.visit(returnTo)" />
    </div>

    <DealerTabs
        v-if="context?.mode === 'dealer-backoffice' && dealer?.id"
        page-tab="quotations"
        :dealer-id="dealer.id"
    />
    <DealerConfigurationNav
        v-if="context?.mode === 'dealer'"
        tab="quotations"
    />

    <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center justify-between">
            <div class="text-h6">{{ data?.id ? 'Edit Quotation' : 'Create Quotation' }}</div>
            <div class="row q-gutter-sm">
                <q-btn
                    v-if="canShowNotes && data?.id"
                    color="grey-8"
                    text-color="white"
                    icon="sticky_note_2"
                    label="Notes"
                    no-wrap
                    unelevated
                    @click="openNotes"
                />
                <q-btn
                    v-if="canExport && data?.id"
                    color="primary"
                    icon="picture_as_pdf"
                    label="Export"
                    no-wrap
                    unelevated
                    @click="confirmExport"
                />
                <q-btn
                    v-if="canDelete && data?.id"
                    color="negative"
                    icon="delete"
                    label="Delete"
                    no-wrap
                    unelevated
                    @click="confirmDelete"
                />
            </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
            <q-banner v-if="showUnsavedChanges" dense rounded class="bg-amber-2 text-orange-10 q-mb-md">
                You have unsaved changes on this quotation.
            </q-banner>
            <q-banner v-if="hasFormErrors" dense rounded class="bg-red-1 text-negative q-mb-md">
                Please fix the highlighted validation errors.
            </q-banner>
            <q-banner v-if="form.errors.line_items" dense rounded class="bg-red-1 text-negative q-mb-md">
                {{ form.errors.line_items }}
            </q-banner>

            <div class="row q-col-gutter-md">
                <div class="col-12 col-md-8">
                    <div class="row q-col-gutter-md">
                        <div class="col-12">
                            <q-select
                                v-model="form.customer_id"
                                dense
                                outlined
                                clearable
                                emit-value
                                map-options
                                use-input
                                hide-selected
                                fill-input
                                input-debounce="1000"
                                :loading="customerSearchLoading"
                                :options="customerOptions"
                                option-label="label"
                                option-value="value"
                                label="Customer"
                                hint="Search by name, email, or contact number"
                                :error="!!form.errors.customer_id"
                                :error-message="form.errors.customer_id"
                                @filter="filterCustomers"
                            />
                            <div class="text-caption text-grey-6 q-pt-xs">{{ customerSearchHint }}</div>
                            <q-btn
                                v-if="canCreateCustomer"
                                class="q-mt-sm"
                                flat
                                dense
                                color="primary"
                                icon="person_add"
                                label="Add Customer"
                                @click="openAddCustomer"
                            />
                        </div>
                    </div>

                    <q-card v-if="selectedCustomer" flat bordered class="q-mt-md">
                        <q-card-section>
                            <div class="text-subtitle2 text-weight-medium">Selected Customer</div>
                            <div class="row q-col-gutter-md q-mt-xs">
                                <div class="col-12 col-md-6">
                                    <div>
                                        <div class="text-caption text-grey-7">Customer</div>
                                        <div class="text-body2">{{ customerDisplayName }}</div>
                                    </div>
                                    <div class="q-mt-md">
                                        <div class="text-caption text-grey-7">Contact Number</div>
                                        <div class="text-body2">{{ selectedCustomer.contact_number || '-' }}</div>
                                    </div>
                                    <div v-if="selectedCustomer.type === 'individual'" class="q-mt-md">
                                        <div class="text-caption text-grey-7">ID Number</div>
                                        <div class="text-body2">{{ selectedCustomer.id_number || '-' }}</div>
                                    </div>
                                    <div v-if="selectedCustomer.type === 'company'" class="q-mt-md">
                                        <div class="text-caption text-grey-7">VAT Number</div>
                                        <div class="text-body2">{{ selectedCustomer.vat_number || '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div>
                                        <div class="text-caption text-grey-7">Email</div>
                                        <div class="text-body2">{{ selectedCustomer.email || '-' }}</div>
                                    </div>
                                    <div class="q-mt-md">
                                        <div class="text-caption text-grey-7">Address</div>
                                        <div class="text-body2" style="white-space: pre-line;">{{ formattedCustomerAddress }}</div>
                                    </div>
                                </div>
                            </div>
                        </q-card-section>
                    </q-card>
                </div>

                <div class="col-12 col-md-4">
                    <div class="row q-col-gutter-sm items-center">
                        <div v-if="!isEditing" class="col-12">
                            <q-checkbox v-model="form.has_custom_quote_identifier" label="Use Custom Quote Identifier" />
                        </div>
                        <div class="col-12">
                            <q-input
                                v-model="form.quote_identifier"
                                dense
                                outlined
                                :disable="isEditing || !form.has_custom_quote_identifier"
                                :hint="isEditing ? 'Identifier cannot be changed after creation.' : (form.has_custom_quote_identifier ? 'Max 15 chars. Allowed: A-Z, a-z, 0-9, /, -' : 'Automatic number will be used.')"
                                label="Quote Identifier"
                                :error="!!form.errors.quote_identifier"
                                :error-message="form.errors.quote_identifier"
                            />
                        </div>
                        <div class="col-12">
                            <q-input
                                v-model="form.quotation_date"
                                dense
                                outlined
                                label="Quotation Date"
                                placeholder="YYYY-MM-DD"
                                mask="####-##-##"
                                fill-mask
                                :rules="dateRules"
                                :error="!!form.errors.quotation_date"
                                :error-message="form.errors.quotation_date"
                                @update:model-value="(value) => (form.quotation_date = formatDateInput(value))"
                            >
                                <template #append>
                                    <q-icon name="event" class="cursor-pointer">
                                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                                            <q-date v-model="form.quotation_date" mask="YYYY-MM-DD" />
                                        </q-popup-proxy>
                                    </q-icon>
                                </template>
                            </q-input>
                        </div>
                        <div class="col-12">
                            <q-input
                                v-model.number="form.valid_for_days"
                                dense
                                outlined
                                type="number"
                                min="1"
                                label="Valid For (Days)"
                                :error="!!form.errors.valid_for_days"
                                :error-message="form.errors.valid_for_days"
                            />
                        </div>
                        <div v-if="data?.id && canConvertToInvoice && convertToInvoiceRoute" class="col-12">
                            <q-btn
                                color="primary"
                                outline
                                label="Convert To Invoice"
                                :loading="loading"
                                :disable="loading"
                                @click="openConvertToInvoiceDialog"
                            />
                        </div>
                        <div v-if="data?.id && linkedInvoices.length" class="col-12">
                            <div class="text-subtitle2 q-mt-sm q-mb-xs">Linked Invoices</div>
                            <q-list bordered separator dense>
                                <q-item
                                    v-for="invoice in linkedInvoices"
                                    :key="invoice.id"
                                    clickable
                                    @click="router.visit(invoice.url)"
                                >
                                    <q-item-section>
                                        <q-item-label>{{ invoice.invoice_identifier || '-' }}</q-item-label>
                                        <q-item-label caption>{{ invoice.invoice_date || '-' }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </div>
                    </div>
                </div>
            </div>
        </q-card-section>
    </q-card>

    <q-card
        v-for="section in groupedSections"
        :key="section.value"
        flat
        bordered
        class="q-mb-md"
    >
        <q-card-section class="row items-center justify-between">
            <div class="text-subtitle1 text-weight-medium">{{ section.label }}</div>
            <q-btn color="primary" flat dense icon="add" label="Add Item" @click="addLineItem(section)" />
        </q-card-section>

        <q-separator />

        <q-card-section>
            <div v-if="section.lineItems.length === 0" class="text-caption text-grey-7">
                No line items yet. Click "Add Item" to add one.
            </div>

            <div
                v-for="lineItemRow in section.lineItems"
                :key="lineItemRow.item.__key"
                class="q-mb-md"
            >
                <q-banner
                    v-if="lineItemError(lineItemRow.index, 'section')"
                    dense
                    rounded
                    class="bg-red-1 text-negative q-mb-sm"
                >
                    {{ lineItemError(lineItemRow.index, 'section') }}
                </q-banner>

                <div class="row q-col-gutter-sm items-start">
                    <div class="col-12 col-md-2">
                        <q-input
                            v-model="lineItemRow.item.sku"
                            :ref="(el) => setSkuInputRef(lineItemRow.item.__key, el)"
                            dense
                            outlined
                            hide-bottom-space
                            label="SKU"
                            :error="!!lineItemError(lineItemRow.index, 'sku')"
                            :error-message="lineItemError(lineItemRow.index, 'sku')"
                            @update:model-value="(value) => { lineItemRow.item.sku = sanitizeSku(value); queueLineItemSuggestionSearch(lineItemRow.item) }"
                        />
                        <div class="row items-center q-gutter-sm q-pt-xs">
                            <div v-if="canSearchSkuSuggestions" class="text-caption text-grey-6">Type at least 3 chars for suggestions.</div>
                            <q-btn
                                v-if="linkedStockUrl(lineItemRow.item)"
                                dense
                                flat
                                color="primary"
                                icon="open_in_new"
                                label="View Linked Stock"
                                no-wrap
                                @click="openLinkedStock(lineItemRow.item)"
                            />
                        </div>
                    </div>
                    <div class="col-12 col-md">
                        <q-input
                            v-model="lineItemRow.item.description"
                            dense
                            outlined
                            hide-bottom-space
                            label="Description"
                            :error="!!lineItemError(lineItemRow.index, 'description')"
                            :error-message="lineItemError(lineItemRow.index, 'description')"
                        />
                    </div>
                    <div class="col-12 col-md-2">
                        <q-input
                            v-model.number="lineItemRow.item.amount"
                            dense
                            outlined
                            hide-bottom-space
                            type="number"
                            min="0"
                            max="999999999.99"
                            step="0.01"
                            :prefix="currencySymbol"
                            label="Amount"
                            :error="!!lineItemError(lineItemRow.index, 'amount')"
                            :error-message="lineItemError(lineItemRow.index, 'amount')"
                            @update:model-value="recalculateLineItem(lineItemRow.item)"
                        />
                    </div>
                    <div class="col-12 col-md-1">
                        <q-input
                            v-model.number="lineItemRow.item.qty"
                            dense
                            outlined
                            hide-bottom-space
                            type="number"
                            min="0"
                            max="999999999.99"
                            step="0.01"
                            label="Qty"
                            :error="!!lineItemError(lineItemRow.index, 'qty')"
                            :error-message="lineItemError(lineItemRow.index, 'qty')"
                            @update:model-value="recalculateLineItem(lineItemRow.item)"
                        />
                    </div>
                    <div class="col-12 col-md-2">
                        <q-input
                            v-model.number="lineItemRow.item.total"
                            dense
                            outlined
                            hide-bottom-space
                            type="number"
                            min="0"
                            max="999999999.99"
                            step="0.01"
                            readonly
                            :prefix="currencySymbol"
                            label="Total"
                            :error="!!lineItemError(lineItemRow.index, 'total')"
                            :error-message="lineItemError(lineItemRow.index, 'total')"
                        />
                    </div>
                    <div v-if="vat?.vat_enabled" class="col-12 col-md-auto q-pt-sm">
                        <q-checkbox v-model="lineItemRow.item.is_vat_exempt" label="VAT Exempt" dense />
                    </div>
                    <div class="col-12 col-md-auto text-right q-ml-auto">
                        <q-btn
                            round
                            dense
                            flat
                            icon="delete"
                            color="negative"
                            @click="confirmRemoveLineItem(lineItemRow.item)"
                        />
                    </div>
                </div>

                <q-card
                    v-if="canSearchSkuSuggestions && (lineItemSuggestions[lineItemRow.item.__key] || []).length > 0"
                    flat
                    bordered
                    class="q-mt-sm"
                >
                    <q-list dense separator>
                        <q-item-label v-if="suggestionStockRows(lineItemRow.item).length > 0" header>Stock Matches</q-item-label>
                        <q-item
                            v-for="suggestion in suggestionStockRows(lineItemRow.item)"
                            :key="`${suggestion.__group}-${suggestion.sku}-${suggestion.stock_id || 'x'}`"
                            clickable
                            @click="applySuggestion(lineItemRow.item, suggestion)"
                        >
                            <q-item-section>
                                <q-item-label>{{ suggestion.sku }} - {{ suggestion.description }}</q-item-label>
                                <q-item-label caption>
                                    {{ suggestion.meta || '-' }}
                                </q-item-label>
                                <q-item-label caption>Amount: {{ currencySymbol }} {{ formatAmount(suggestion.amount) }}</q-item-label>
                            </q-item-section>
                        </q-item>

                        <q-item-label v-if="suggestionHistoryRows(lineItemRow.item).length > 0" header>Saved Items</q-item-label>
                        <q-item
                            v-for="suggestion in suggestionHistoryRows(lineItemRow.item)"
                            :key="`${suggestion.__group}-${suggestion.sku}-${suggestion.stock_id || 'x'}`"
                            clickable
                            @click="applySuggestion(lineItemRow.item, suggestion)"
                        >
                            <q-item-section>
                                <q-item-label>{{ suggestion.sku }} - {{ suggestion.description }}</q-item-label>
                                <q-item-label caption>Amount: {{ currencySymbol }} {{ formatAmount(suggestion.amount) }}</q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card>
            </div>

            <div v-if="section.lineItems.length > 0" class="row justify-end q-mt-sm">
                <div class="text-subtitle2 text-grey-8">
                    Subtotal: {{ currencySymbol }} {{ formatAmount(sectionSubtotal(section)) }}
                </div>
            </div>
        </q-card-section>
    </q-card>

    <AssociatedStockList :items="data?.associated_stock || []" title="Associated Stock" />

    <q-card flat bordered class="q-mb-md">
        <q-card-section>
            <div class="text-h6">Totals</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
            <div v-if="vat?.vat_enabled" class="row q-col-gutter-md">
                <div class="col-12 col-md-4">
                    <q-input :model-value="formatAmount(totals.subtotalBeforeVat)" dense outlined readonly :prefix="currencySymbol" label="Amount Before VAT" />
                </div>
                <div class="col-12 col-md-4">
                    <q-input :model-value="formatAmount(totals.vatAmount)" dense outlined readonly :prefix="currencySymbol" :label="`VAT (${vat.vat_percentage || 0}%)`" />
                </div>
                <div class="col-12 col-md-4">
                    <q-input :model-value="formatAmount(totals.totalAmount)" dense outlined readonly :prefix="currencySymbol" label="Total Amount" />
                </div>
            </div>
            <div v-else class="row q-col-gutter-md">
                <div class="col-12 col-md-4">
                    <q-input :model-value="formatAmount(totals.totalAmount)" dense outlined readonly :prefix="currencySymbol" label="Total Amount" />
                </div>
            </div>
        </q-card-section>
    </q-card>

    <div class="row justify-end q-gutter-sm">
        <q-btn
            color="primary"
            label="Save Quotation"
            no-wrap
            unelevated
            :loading="form.processing"
            @click="submit"
        />
    </div>

    <q-dialog v-model="addCustomerDialog">
        <q-card style="min-width: 560px; max-width: 90vw;">
            <q-card-section>
                <div class="text-h6">Add Customer</div>
            </q-card-section>
            <q-separator />
            <q-card-section>
                <div class="row q-col-gutter-md">
                    <div v-if="Object.keys(customerForm.errors || {}).length > 0" class="col-12">
                        <q-banner dense rounded class="bg-red-1 text-negative">
                            Please fix the customer validation errors.
                        </q-banner>
                    </div>
                    <div class="col-12 col-md-6">
                        <q-select
                            v-model="customerForm.type"
                            dense
                            outlined
                            emit-value
                            map-options
                            :options="customerTypeOptions"
                            label="Type"
                            :error="!!customerForm.errors.type"
                            :error-message="customerForm.errors.type"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.title"
                            dense
                            outlined
                            label="Title"
                            :error="!!customerForm.errors.title"
                            :error-message="customerForm.errors.title"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.firstname"
                            dense
                            outlined
                            label="Firstname"
                            :error="!!customerForm.errors.firstname"
                            :error-message="customerForm.errors.firstname"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.lastname"
                            dense
                            outlined
                            label="Lastname"
                            :error="!!customerForm.errors.lastname"
                            :error-message="customerForm.errors.lastname"
                        />
                    </div>
                    <div v-if="customerForm.type === 'individual'" class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.id_number"
                            dense
                            outlined
                            label="ID Number"
                            :error="!!customerForm.errors.id_number"
                            :error-message="customerForm.errors.id_number"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.email"
                            dense
                            outlined
                            label="Email"
                            :error="!!customerForm.errors.email"
                            :error-message="customerForm.errors.email"
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <q-input
                            v-model="customerForm.contact_number"
                            dense
                            outlined
                            label="Contact Number (E.164)"
                            :error="!!customerForm.errors.contact_number"
                            :error-message="customerForm.errors.contact_number"
                            @update:model-value="(value) => (customerForm.contact_number = sanitizeContactNumber(value))"
                        />
                    </div>
                    <div class="col-12">
                        <q-input
                            v-model="customerForm.address"
                            dense
                            outlined
                            type="textarea"
                            rows="5"
                            maxlength="150"
                            counter
                            label="Address"
                            :error="!!customerForm.errors.address"
                            :error-message="customerForm.errors.address"
                        />
                    </div>
                    <div v-if="customerForm.type === 'company'" class="col-12">
                        <q-input
                            v-model="customerForm.vat_number"
                            dense
                            outlined
                            maxlength="35"
                            hint="Allowed: A-Z, a-z, 0-9, /, -"
                            label="VAT Number"
                            :error="!!customerForm.errors.vat_number"
                            :error-message="customerForm.errors.vat_number"
                            @update:model-value="(value) => (customerForm.vat_number = sanitizeVatNumber(value))"
                        />
                    </div>
                </div>
            </q-card-section>
            <q-separator />
            <q-card-actions align="right">
                <q-btn flat label="Cancel" @click="addCustomerDialog = false" />
                <q-btn color="primary" unelevated label="Create Customer" :loading="creatingCustomer" @click="submitAddCustomer" />
            </q-card-actions>
        </q-card>
    </q-dialog>

    <NotesHost
        ref="notesRef"
        noteable-type="quotation"
        title-key="quote_identifier"
    />

    <q-dialog v-model="convertDialog" persistent>
        <q-card style="min-width: 420px; max-width: 90vw;">
            <q-card-section>
                <div class="text-h6">Convert To Invoice</div>
            </q-card-section>
            <q-separator />
            <q-card-section>
                <q-checkbox
                    v-model="convertForm.has_custom_invoice_identifier"
                    label="Use Custom Invoice Identifier"
                    class="q-mb-sm"
                />
                <q-input
                    v-model="convertForm.invoice_identifier"
                    dense
                    outlined
                    :disable="!convertForm.has_custom_invoice_identifier"
                    label="Invoice Identifier"
                    hint="Max 15 chars. Allowed: A-Z, a-z, 0-9, /, -"
                />
            </q-card-section>
            <q-separator />
            <q-card-actions align="right">
                <q-btn flat label="Cancel" v-close-popup />
                <q-btn color="primary" unelevated label="Convert" @click="confirmConvertToInvoice" />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
