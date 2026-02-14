<script setup>
import { Head, router, usePage } from '@inertiajs/vue3'
import {onMounted, computed, ref, watch} from 'vue'
import Layout from 'bo@/Layouts/Layout.vue'

defineOptions({layout: Layout})

// props
const props = defineProps({
    users: { type: Object, required: true },   // Laravel paginator
    filters: { type: Object, default: () => ({}) },
    publicTitle: {
        type: String,
        required: false,
        default: null
    },
});

// Table columns (use server sort fields)
const columns = [
    { name: 'firstname', label: 'Firstname', field: 'firstname', sortable: true, align: 'left' },
    { name: 'lastname', label: 'Lastname', field: 'lastname', sortable: true, align: 'left' },
    { name: 'email', label: 'Email', field: 'email', sortable: true, align: 'left' },
    { name: 'contact_no', label: 'Contact No', field: 'contact_no', sortable: true, align: 'left' },
    { name: 'city', label: 'City', field: 'city', sortable: true, align: 'left' },
    { name: 'country', label: 'Country', field: 'country', sortable: true, align: 'left' },
]

// Filters
const search = ref(props.filters?.search ?? '')
const isActive = ref(props.filters?.is_active ?? null)

const activeOptions = [
    { label: 'Active', value: '1' },
    { label: 'Inactive', value: '0' }
]

// Quasar pagination model (server-side)
const pagination = ref({
    page: props.users.current_page ?? 1,
    rowsPerPage: props.users.per_page ?? 10,
    sortBy: 'id',
    descending: true,
    rowsNumber: props.users.total ?? 0
})

const loading = ref(false)

// Keep rowsNumber + current page in sync when new server props arrive
watch(
    () => props.users,
    (u) => {
        pagination.value.page = u.current_page ?? 1
        pagination.value.rowsPerPage = u.per_page ?? pagination.value.rowsPerPage
        pagination.value.rowsNumber = u.total ?? 0
    },
    { deep: true }
)

function goFirstPage () {
    pagination.value.page = 1
    fetchServer()
}

function onRequest (req) {
    // Quasar emits the desired pagination state here
    pagination.value = {
        ...pagination.value,
        ...req.pagination
    }

    fetchServer()
}

function fetchServer () {
    loading.value = true

    const p = pagination.value

    router.get(
        route('backoffice.index'),
        {
            page: p.page,
            rowsPerPage: p.rowsPerPage,
            sortBy: p.sortBy,
            descending: p.descending,
            search: search.value || '',
            is_active: isActive.value ?? ''
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters', 'flash'],
            onFinish: () => {
                loading.value = false
            }
        }
    )
}

function onRowClick (row) {
    // Example: go to a user show/edit page without refresh
    // router.visit(route('backoffice.users.show', row.id))
    alert(`User ${row.firstname} ${row.lastname} clicked`)
}

onMounted(() => {

})

</script>
<template>
    <Head>
        <title>{{ $page.props.appName }}</title>
    </Head>

    <!-- ============================================================== -->
    <!-- Start Page Content -->
    <!-- ============================================================== -->

    <div class="row nowrap justify-between">
        <div>
            <div class="text-h5 text-weight-regular text-grey-9">Dashboard</div>
        </div>
        <!--        <q-breadcrumbs>-->
        <!--            <q-breadcrumbs-el label="Home" />-->
        <!--            <q-breadcrumbs-el label="Components" />-->
        <!--            <q-breadcrumbs-el label="Breadcrumbs" />-->
        <!--        </q-breadcrumbs>-->
        <div class="q-gutter-sm">
            <q-btn color="primary" label="Primary" :loading="true" />
            <q-btn color="primary" label="Primary" no-caps no-wrap unelevated />
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-12 col-md-4">
            <div class="bg-green-9 q-pa-md">xxx</div>
        </div>

        <div class="col-12 col-md-4">
            <div class="bg-blue-9 q-pa-md">xxx</div>
        </div>

        <div class="col-12 col-md-4">
            <div class="bg-yellow-3 q-pa-md">xxx</div>
        </div>
    </div>

    <div class="row q-my-sm q-col-gutter-md">
        <div class="col-12">
            <div class="bg-white q-pa-md">
                <div>
                    <div class="text-h6">Title</div>
                    <div class="text-subtitle2">Sub title</div>
                    <div class="text-subtitle2 text-weight-light">Sub title</div>
                    <div class="text-subtitle2 text-weight-medium">Sub title</div>
                    <div class="text-subtitle2 text-weight-thin">Sub title</div>
                </div>

                <div class="q-my-lg">
                    <q-input class="q-my-md" label="Label (stacked)" stack-label dense filled />
                    <q-input class="q-my-md" label="Firstname" dense filled />
                    <q-input class="q-my-md" label="Lastname" hint="hint" dense filled clearable loading />
                    <q-input class="q-my-md" label="test" hint="hint" prefix="prefix" suffix="suffix" dense filled clearable />
                    <q-input class="q-my-md" label="counter" maxlength="3" hint="hint" dense filled counter />
                    <q-input class="q-my-md" label="error" :error="true" error-message="error message" hint="hint" dense filled />
                    <q-input class="q-my-md" type="password" label="Label (password)" standout="bg-teal text-white" filled dense />
                    <q-input class="q-my-md" type="email" label="Label (email)" standout="bg-teal text-white" filled dense />
                    <q-input class="q-my-md" type="tel" label="Label (tel)" standout="bg-teal text-white" filled stack-label dense />
                    <q-input class="q-my-md" type="date" label="Label (date)" standout="bg-teal text-white" filled dense />
                    <q-input class="q-my-md" type="time" label="Label (time)" standout="bg-teal text-white" filled stack-label dense />
                </div>

                <div class="q-my-lg">
                    <q-select class="q-my-md" filled :options="[ { label: 'Tesla', value: 'car' }, { label: 'iPhone', value: 'phone' } ]" label="dropdown select" dense />
                    <q-select class="q-my-md" filled :options="[ { label: 'Tesla', value: 'car' }, { label: 'iPhone', value: 'phone' } ]" label="dropdown select" hint="hint" :error="true" error-message="error message" prefix="prefix" suffix="suffix" dense />
                    <q-select class="q-my-md" filled :options="[ { label: 'Tesla', value: 'car' }, { label: 'iPhone', value: 'phone' } ]" label="dropdown select" hint="hint" :error="true" error-message="error message" dense />
                    <q-select class="q-my-md" filled :options="[ { label: 'Tesla', value: 'car' }, { label: 'iPhone', value: 'phone' } ]" label="dropdown select" dense options-dense />
                    <q-select class="q-my-md" filled :options="[ { label: 'Tesla', value: 'car' }, { label: 'iPhone', value: 'phone' } ]" label="dropdown select" dense options-dense multiple hide-selected />
                </div>

                <div class="q-mt-lg">
                    <div class="row nowrap justify-end">
                        <div class="q-gutter-sm">
                            <q-btn color="grey-4" text-color="standard" no-wrap unelevated label="Cancel" />
                            <q-btn color="primary" label="Save" no-wrap unelevated />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="q-my-lg">
        <div class="">
            <q-table
                flat
                bordered
                title="Users"
                row-key="id"
                :rows="users.data"
                :columns="columns"
                :loading="loading"
                v-model:pagination="pagination"
                @request="onRequest"
            >
                <!-- Top-right controls -->
                <template #top-right>
                    <div class="row q-col-gutter-sm items-center">
                        <div class="col-auto" style="min-width: 220px">
                            <q-input
                                dense
                                outlined
                                debounce="1000"
                                v-model="search"
                                placeholder="Search name/email…"
                                clearable
                                :input-attrs="{ autocomplete: 'off' }"
                                @update:model-value="goFirstPage"
                            />
                        </div>

                        <div class="col-auto" style="min-width: 160px">
                            <q-select
                                dense
                                outlined
                                emit-value
                                map-options
                                :options="activeOptions"
                                v-model="isActive"
                                label="Status"
                                @update:model-value="goFirstPage"
                                clearable
                            />
                        </div>
                    </div>
                </template>

                <!-- Body -->
                <template #body="props">
                    <q-tr :props="props" class="cursor-pointer" @click="onRowClick(props.row)">
                        <q-td key="firstname" :props="props">
                            {{ props.row.firstname }}
                        </q-td>

                        <q-td key="lastname" :props="props">
                            {{ props.row.lastname }}
                        </q-td>

                        <q-td key="email" :props="props">
                            {{ props.row.email }}
                        </q-td>

                        <q-td key="contact_no" :props="props">
                            {{ props.row.contact_no }}
                        </q-td>

                        <q-td key="city" :props="props">
                            {{ props.row.city }}
                        </q-td>

                        <q-td key="country" :props="props">
                            {{ props.row.country }}
                        </q-td>
                    </q-tr>
                </template>

                <!-- No data -->
                <template #no-data>
                    <div class="full-width row flex-center q-gutter-sm q-pa-md">
                        <q-icon name="search_off" size="24px" />
                        <span>No users found</span>
                    </div>
                </template>
            </q-table>

            <!-- Optional: show totals below -->
            <div class="q-mt-sm text-caption text-grey-7">
                Page {{ users.current_page }} of {{ users.last_page }} •
                Total: {{ users.total }} records
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- End Page Content -->
    <!-- ============================================================== -->
</template>
