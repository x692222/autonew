<script setup>
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

defineProps({
    flash: {
        default: {}
    },
})

const $q = useQuasar()
const page = usePage()

const impersonationDialog = ref(false)
const impersonationEmail = ref('')

const abilities = computed(() => page.props.auth?.user?.abilities || {})
const pendingCounts = computed(() => page.props.pending_counts || { system_requests: 0, feature_tags: 0 })
const impersonation = computed(() => page.props.impersonation || {})
const authGuard = computed(() => page.props.auth?.guard ?? '')
const authUser = computed(() => page.props.auth?.user ?? null)

const canViewUsersManagement = computed(() => !!abilities.value.indexSystemUsers)
const canViewLocationsManagement = computed(() => !!abilities.value.indexSystemLocations)
const canViewDealershipManagement = computed(() => !!abilities.value.showDealerships)
const canViewSystemQuotations = computed(() => isBackofficeGuard.value && !!abilities.value.indexSystemQuotations)
const canViewSystemInvoices = computed(() => isBackofficeGuard.value && !!abilities.value.indexSystemInvoices)
const canImpersonateUser = computed(() => !!abilities.value.impersonateDealershipUser)
const canViewDealerConfiguration = computed(() => authGuard.value === 'dealer' && (
    !!abilities.value.editDealership ||
    !!abilities.value.indexDealershipBranches ||
    !!abilities.value.indexDealershipSalesPeople ||
    !!abilities.value.indexDealershipUsers ||
    !!abilities.value.indexStock ||
    !!abilities.value.manageLeads ||
    !!abilities.value.indexPipelines ||
    !!abilities.value.indexPipelineStages ||
    !!abilities.value.indexQuotations ||
    !!abilities.value.indexInvoices ||
    !!abilities.value.canConfigureSettings
))
const canViewDealerStock = computed(() => authGuard.value === 'dealer' && !!abilities.value.indexStock)
const canViewDealerLeads = computed(() => authGuard.value === 'dealer' && !!abilities.value.manageLeads)
const canViewDealerQuotations = computed(() => authGuard.value === 'dealer' && !!abilities.value.indexQuotations)
const canViewDealerInvoices = computed(() => authGuard.value === 'dealer' && !!abilities.value.indexInvoices)
const canProcessSystemRequests = computed(() => isBackofficeGuard.value && !!abilities.value.processSystemRequests)
const canConfigureSystemSettings = computed(() => isBackofficeGuard.value && !!abilities.value.canConfigureSystemSettings)

const isImpersonating = computed(() => !!impersonation.value.active)
const isBackofficeGuard = computed(() => authGuard.value === 'backoffice')
const showStartImpersonation = computed(() => isBackofficeGuard.value && !isImpersonating.value && canImpersonateUser.value)
const showStopImpersonation = computed(() => isImpersonating.value)
const signedInName = computed(() => {
    const first = authUser.value?.firstname ?? ''
    const last = authUser.value?.lastname ?? ''
    return `${first} ${last}`.trim() || 'User'
})

const openImpersonationDialog = () => {
    impersonationEmail.value = impersonation.value.default_email ?? ''
    impersonationDialog.value = true
}

const startImpersonation = () => {
    if (!impersonationEmail.value) {
        $q.notify({
            type: 'negative',
            message: 'Please enter a dealer user email.',
            position: 'top-right',
            timeout: 3500
        })
        return
    }

    router.post(
        route('backoffice.auth.impersonations.start'),
        { email: impersonationEmail.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                impersonationDialog.value = false
            },
        }
    )
}

const stopImpersonation = () => {
    router.post(route('backoffice.auth.impersonations.stop'), {}, { preserveScroll: true })
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return

        if (flash.success) {
            $q.notify({
                type: 'positive',
                message: flash.success,
                position: 'top-right',
                timeout: 3500
            })
        }

        if (flash.error) {
            $q.notify({
                type: 'negative',
                message: flash.error,
                position: 'top-right',
                timeout: 4500
            })
        }
    },
    { deep: true, immediate: true }
)
</script>
<template>
    <q-layout view="hHh lpR fFf" class="bg-grey-3">

        <q-header elevated class="bg-primary text-white" height-hint="98">
            <div class="row no-wrap justify-between">
                <q-toolbar>
                    <q-toolbar-title>
                        <q-avatar>
                            <img src="https://cdn.quasar.dev/logo-v2/svg/logo-mono-white.svg">
                        </q-avatar>
                        Title
                    </q-toolbar-title>

                    <div class="q-pl-sm q-gutter-sm row items-center no-wrap">

                        <q-btn
                            round
                            flat
                            dense
                            icon="notifications"
                        />

                        <q-btn
                            round
                            flat
                            dense
                            icon="mail"
                        />

                        <q-btn
                            v-if="isBackofficeGuard && canProcessSystemRequests"
                            round
                            flat
                            dense
                            icon="assignment_late"
                            @click="router.visit(route('backoffice.system.system-requests.index'))"
                        >
                            <q-badge floating color="red" rounded>{{ pendingCounts.system_requests || 0 }}</q-badge>
                        </q-btn>

                        <q-btn
                            v-if="isBackofficeGuard && canProcessSystemRequests"
                            round
                            flat
                            dense
                            icon="new_releases"
                            @click="router.visit(route('backoffice.system.pending-feature-tags.index'))"
                        >
                            <q-badge floating color="red" rounded>{{ pendingCounts.feature_tags || 0 }}</q-badge>
                        </q-btn>

                        <q-btn dense flat no-wrap>
                            my account
                            <q-icon name="arrow_drop_down" size="16px" />

                            <q-menu auto-close >
                                <q-list dense>
                                    <q-item class="GL__menu-link-signed-in">
                                        <q-item-section>
                                            <div>Signed in as <strong>{{ signedInName }}</strong></div>
                                        </q-item-section>
                                    </q-item>
                                    <q-separator />
                                    <q-item clickable class="GL__menu-link-status">
                                        <q-item-section>
                                            <div>
                                                <q-icon name="tag_faces" color="blue-9" size="18px" />
                                                Set your status
                                            </div>
                                        </q-item-section>
                                    </q-item>
                                    <q-separator />
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Your profile</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Your repositories</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Your projects</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Your stars</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Your gists</q-item-section>
                                    </q-item>
                                    <q-separator />
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Help</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section>Settings</q-item-section>
                                    </q-item>
                                    <q-item clickable class="GL__menu-link" @click="router.visit(route('backoffice.logout'))">
                                        <q-item-section>Sign out</q-item-section>
                                    </q-item>
                                    <q-item
                                        v-if="showStartImpersonation"
                                        clickable
                                        class="GL__menu-link"
                                        @click="openImpersonationDialog"
                                    >
                                        <q-item-section>Impersonate User</q-item-section>
                                    </q-item>
                                    <q-item
                                        v-if="showStopImpersonation"
                                        clickable
                                        class="GL__menu-link"
                                        @click="stopImpersonation"
                                    >
                                        <q-item-section>Stop Impersonation</q-item-section>
                                    </q-item>
                                </q-list>
                            </q-menu>
                        </q-btn>
                    </div>
                    </q-toolbar>
            </div>



            <q-tabs align="left" class="bg-grey-1 text-grey-9">
                <q-route-tab label="Dashboard" alert @click="router.visit(route('backoffice.index'))" />

                <q-route-tab
                    v-if="canViewDealershipManagement"
                    label="Dealership"
                    @click="router.visit(route('backoffice.dealer-management.dealers.index'))"
                />
                <q-route-tab
                    v-if="canViewSystemQuotations"
                    label="Quotations"
                    @click="router.visit(route('backoffice.system.quotations.index'))"
                />
                <q-route-tab
                    v-if="canViewSystemInvoices"
                    label="Invoices"
                    @click="router.visit(route('backoffice.system.invoices.index'))"
                />
                <q-route-tab v-if="canViewDealerConfiguration" label="Configuration">
                    <q-menu>
                        <q-list dense style="min-width: 220px">
                            <q-item v-if="abilities.editDealership" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.edit-dealership.show'))">
                                <q-item-section>Edit Dealership</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexDealershipBranches" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.branches.index'))">
                                <q-item-section>Branches</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexDealershipSalesPeople" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.sales-people.index'))">
                                <q-item-section>Sales People</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexDealershipUsers" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.users.index'))">
                                <q-item-section>Platform Users</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexStock" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.stock.index'))">
                                <q-item-section>Stock</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.manageLeads" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.leads.index'))">
                                <q-item-section>Leads</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexPipelines" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.lead-pipelines.index'))">
                                <q-item-section>Lead Pipelines</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexPipelineStages" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.lead-stages.index'))">
                                <q-item-section>Lead Stages</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexQuotations" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.quotations.index'))">
                                <q-item-section>Quotations</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.indexInvoices" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.invoices.index'))">
                                <q-item-section>Invoices</q-item-section>
                            </q-item>
                            <q-item v-if="abilities.canConfigureSettings" clickable v-close-popup @click="router.visit(route('backoffice.dealer-configuration.settings.index'))">
                                <q-item-section>Settings</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-route-tab>
                <q-route-tab
                    v-if="canViewDealerStock"
                    label="Stock"
                    @click="router.visit(route('backoffice.dealer-configuration.stock.index'))"
                />
                <q-route-tab
                    v-if="canViewDealerLeads"
                    label="Leads"
                    @click="router.visit(route('backoffice.dealer-configuration.leads.index'))"
                />
                <q-route-tab
                    v-if="canViewDealerQuotations"
                    label="Quotations"
                    @click="router.visit(route('backoffice.dealer-configuration.quotations.index'))"
                />
                <q-route-tab
                    v-if="canViewDealerInvoices"
                    label="Invoices"
                    @click="router.visit(route('backoffice.dealer-configuration.invoices.index'))"
                />
                <q-route-tab label="Analytics">
                    <q-menu>
                        <q-list style="min-width: 100px">
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.index'))">Dealers</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.index'))">Branches</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.index'))">Users</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.index'))">Sale People</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-route-tab>

                <q-route-tab label="System">
                    <q-menu>
                        <q-list dense style="min-width: 100px">
                            <q-item
                                v-if="canViewUsersManagement"
                                clickable
                                v-close-popup
                                @click="router.visit(route('backoffice.system.user-management.users.index'))"
                            >
                                <q-item-section>User Management</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section>New</q-item-section>
                            </q-item>
                            <q-item
                                v-if="canProcessSystemRequests"
                                clickable
                                v-close-popup
                                @click="router.visit(route('backoffice.system.system-requests.index'))"
                            >
                                <q-item-section>System Requests</q-item-section>
                            </q-item>
                            <q-item
                                v-if="canProcessSystemRequests"
                                clickable
                                v-close-popup
                                @click="router.visit(route('backoffice.system.pending-feature-tags.index'))"
                            >
                                <q-item-section>Pending Feature Tags</q-item-section>
                            </q-item>
                            <q-separator />
                            <q-item clickable>
                                <q-item-section>System Configuration</q-item-section>
                                <q-item-section side>
                                    <q-icon name="keyboard_arrow_right" />
                                </q-item-section>

                                <q-menu auto-close anchor="top end" self="top start">
                                    <q-list>
                                        <q-item
                                            v-if="canViewLocationsManagement"
                                            @click="router.visit(route('backoffice.system.locations-management.index'))"
                                            dense
                                            clickable
                                        >
                                            <q-item-section>Location Management</q-item-section>
                                        </q-item>
                                        <q-item
                                            v-if="canConfigureSystemSettings"
                                            @click="router.visit(route('backoffice.system.settings.index'))"
                                            dense
                                            clickable
                                        >
                                            <q-item-section>System Settings</q-item-section>
                                        </q-item>
                                    </q-list>
                                </q-menu>

                            </q-item>
                            <q-separator />
                            <q-item clickable v-close-popup>
                                <q-item-section>Quit</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-route-tab>

            </q-tabs>





        </q-header>

        <q-banner
            v-if="isImpersonating"
            inline-actions
            class="bg-orange-2 text-orange-10"
        >
            Impersonation active: {{ impersonation.dealer_user_email || 'Dealer user' }}
            <template #action>
                <q-btn flat color="negative" label="Stop" @click="stopImpersonation" />
            </template>
        </q-banner>

        <q-page-container>
            <q-page class="q-pa-xl">
                <slot/>
            </q-page>
        </q-page-container>

        <q-dialog v-model="impersonationDialog" persistent>
            <q-card style="min-width: 480px; max-width: 90vw;">
                <q-card-section>
                    <div class="text-h6">Impersonate Dealer User</div>
                </q-card-section>

                <q-card-section>
                    <q-input
                        v-model="impersonationEmail"
                        label="Dealer user email"
                        type="email"
                        dense
                        outlined
                        autocomplete="off"
                    />
                </q-card-section>

                <q-card-actions align="right">
                    <q-btn flat label="Cancel" @click="impersonationDialog = false" />
                    <q-btn color="primary" label="Start Impersonation" @click="startImpersonation" />
                </q-card-actions>
            </q-card>
        </q-dialog>

        <q-footer elevated class="bg-grey-8 text-white">
            <q-toolbar>
                <q-toolbar-title>
                    <q-avatar>
                        <img src="https://cdn.quasar.dev/logo-v2/svg/logo-mono-white.svg">
                    </q-avatar>
                    <div>Title</div>
                </q-toolbar-title>
            </q-toolbar>
        </q-footer>

    </q-layout>
</template>
