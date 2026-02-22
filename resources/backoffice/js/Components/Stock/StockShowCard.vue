<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PublishStatusCard from 'bo@/Components/Stock/PublishStatusCard.vue'

const props = defineProps({
    publicTitle: { type: String, default: 'View Stock' },
    dealer: { type: Object, required: true },
    stock: { type: Object, required: true },
    totalImagesCount: { type: Number, default: 0 },
    minimumImagesRequiredForLive: { type: Number, default: 3 },
    backUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
})

const page = usePage()

const enumKeys = new Set(['condition', 'color', 'gearbox_type', 'drive_type', 'fuel_type', 'category', 'is_police_clearance_ready'])
const hiddenTypedKeys = new Set(['id', 'stock_id', 'make_id', 'model_id', 'created_at', 'updated_at', 'deleted_at'])

const typedRows = computed(() => {
    const typed = props.stock?.typed || {}
    return Object.entries(typed)
        .filter(([key, value]) => !hiddenTypedKeys.has(key) && value !== null && value !== '')
        .map(([key, value]) => ({
            key,
            label: key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
            value,
            isBoolean: typeof value === 'boolean',
        }))
})

const baseRows = computed(() => ([
    { key: 'name', label: 'Name', value: props.stock?.name || '—', isBoolean: false },
    { key: 'type', label: 'Type', value: props.stock?.type_label || props.stock?.type || '—', isBoolean: false },
    { key: 'price', label: 'Price', value: props.stock?.price ?? '—', isBoolean: false },
    { key: 'discounted_price', label: 'Discounted Price', value: props.stock?.discounted_price ?? '—', isBoolean: false },
    { key: 'internal_reference', label: 'Internal Reference', value: props.stock?.internal_reference || '—', isBoolean: false },
    { key: 'description', label: 'Description', value: props.stock?.description || '—', isBoolean: false },
    { key: 'branch_name', label: 'Branch', value: props.stock?.branch_name || '—', isBoolean: false },
    { key: 'published_at', label: 'Published At', value: props.stock?.published_at || '—', isBoolean: false },
    { key: 'date_acquired', label: 'Date Acquired', value: props.stock?.date_acquired || '—', isBoolean: false },
    { key: 'days_since_acquired', label: 'Days Since Acquired', value: props.stock?.days_since_acquired ?? '—', isBoolean: false },
    { key: 'server_now', label: 'Server Time', value: page.props.server_now || '—', isBoolean: false },
    { key: 'is_live', label: 'Is Live', value: !!props.stock?.is_live, isBoolean: true },
    { key: 'is_active', label: 'Is Active', value: !!props.stock?.is_active, isBoolean: true },
    { key: 'is_paid', label: 'Is Paid', value: !!props.stock?.is_paid, isBoolean: true },
    { key: 'is_sold', label: 'Is Sold', value: !!props.stock?.is_sold, isBoolean: true },
]))

const formatTypedValue = (key, value) => {
    if (key === 'make_name' || key === 'model_name') {
        return value || '—'
    }

    if (enumKeys.has(key)) {
        return String(value).replace(/_/g, ' ').toUpperCase()
    }

    return value
}

const paymentStatusLabel = computed(() => {
    if (props.stock?.payment_status === 'full') return 'FULL PAYMENT'
    if (props.stock?.payment_status === 'partial') return 'PARTIAL PAYMENT'
    return 'NO PAYMENT'
})

const paymentStatusPositive = computed(() => props.stock?.payment_status === 'full' || props.stock?.payment_status === 'partial')
</script>

<template>
    <div class="row nowrap justify-between items-center">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">{{ publicTitle }}</div>
            <div class="text-caption text-grey-7">Dealer: <span class="text-weight-medium">{{ dealer?.name }}</span></div>
        </div>

        <div class="q-gutter-sm">
            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Back" @click="router.visit(backUrl)" />
            <q-btn color="primary" label="Edit" no-caps no-wrap unelevated @click="router.visit(editUrl)" />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-lg-8 col-sm-12">
            <q-banner v-if="stock.is_sold" class="bg-grey-10 text-white q-mb-md" rounded dense inline-actions>
                <div class="row items-center justify-center full-width q-gutter-sm">
                    <q-icon name="sell" size="20px" />
                    <div class="text-weight-medium">This stock item is marked as sold</div>
                </div>
            </q-banner>

            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Stock</div>

                    <q-list bordered separator>
                        <q-item v-for="row in baseRows" :key="row.key">
                            <q-item-section>
                                <q-item-label caption>{{ row.label }}</q-item-label>
                                <q-item-label v-if="!row.isBoolean">{{ row.value }}</q-item-label>
                                <q-item-label v-else>
                                    <q-icon :name="row.value ? 'check_circle' : 'cancel'" :color="row.value ? 'positive' : 'negative'" size="18px" />
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>

                    <q-separator class="q-my-md" />

                    <div class="text-h6 q-pb-sm">Type-specific details</div>
                    <div v-if="!stock.typed" class="text-grey-7">No typed details found.</div>

                    <q-list v-else bordered separator>
                        <q-item v-for="row in typedRows" :key="row.key">
                            <q-item-section>
                                <q-item-label caption>{{ row.label }}</q-item-label>
                                <q-item-label v-if="!row.isBoolean">{{ formatTypedValue(row.key, row.value) ?? '—' }}</q-item-label>
                                <q-item-label v-else>
                                    <q-icon :name="row.value ? 'check_circle' : 'cancel'" :color="row.value ? 'positive' : 'negative'" size="18px" />
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card-section>
            </q-card>
        </div>

        <div class="col-lg-4 col-sm-12">
            <PublishStatusCard
                :dealer="dealer"
                :stock="stock"
                :total-images-count="totalImagesCount"
                :images-required="minimumImagesRequiredForLive"
                class="q-mb-md"
            />

            <q-card flat bordered class="q-mb-md">
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Invoice & Payment Status</div>
                    <q-list bordered separator>
                        <q-item>
                            <q-item-section>
                                <q-item-label caption>Current Status</q-item-label>
                                <q-item-label>
                                    <q-icon :name="paymentStatusPositive ? 'check_circle' : 'cancel'" :color="paymentStatusPositive ? 'positive' : 'negative'" size="18px" />
                                    <span class="q-ml-sm">{{ paymentStatusLabel }}</span>
                                </q-item-label>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card-section>
                <q-separator />
                <q-card-section>
                    <div class="text-subtitle2 q-mb-sm">Linked Invoices</div>
                    <div v-if="!(stock?.linked_invoices || []).length" class="text-grey-7">No linked invoices.</div>
                    <q-list v-else dense>
                        <q-item
                            v-for="invoice in stock.linked_invoices"
                            :key="invoice.id"
                            clickable
                            @click="router.visit(invoice.url)"
                        >
                            <q-item-section>
                                <q-item-label>{{ invoice.invoice_identifier || '-' }}</q-item-label>
                                <q-item-label caption>{{ invoice.invoice_date || '-' }}</q-item-label>
                            </q-item-section>
                            <q-item-section side>
                                <q-icon
                                    v-if="invoice.status === 'full'"
                                    name="check_circle"
                                    color="positive"
                                    size="18px"
                                />
                                <span v-else-if="invoice.status === 'partial'" class="text-caption text-weight-medium">partial</span>
                                <span v-else class="text-caption text-grey-7">not paid</span>
                            </q-item-section>
                        </q-item>
                    </q-list>
                </q-card-section>
            </q-card>

            <q-card flat bordered>
                <q-card-section>
                    <div class="text-h6 q-pb-sm">Features / Tags</div>
                    <div v-if="!stock.feature_tags?.length" class="text-grey-7">No tags.</div>
                    <div v-else class="q-gutter-xs">
                        <q-chip v-for="t in stock.feature_tags" :key="t.id || t" outline>{{ t.name || t.label || t }}</q-chip>
                    </div>
                </q-card-section>
            </q-card>
        </div>
    </div>
</template>
