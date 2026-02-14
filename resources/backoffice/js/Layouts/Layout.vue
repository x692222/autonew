<script setup>
import {ref, watch, computed, onMounted, onUnmounted} from "vue";
import { Link, router, usePage } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

// props
const props = defineProps({
  flash: {
    default: {}
  },
});

const $q = useQuasar()
const page = usePage()

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

// watch(() => props.flash, (val, oldVal) => {
//   if(((typeof val.success !== 'undefined') && (val.success !== null)) || ((typeof val.error !== 'undefined') && (val.error !== null))) {
//     if(typeof val !== 'undefined' && val !== null && val !== null) {
//       Swal.fire({
//         position: "top-end",
//         // icon: val.success !== null ? "success":"error",
//         // title: val.success !== null ? val.success:val.error,
//         text: val.success !== null ? val.success:val.error,
//         showConfirmButton: false,
//         timer: 2500,
//         timerProgressBar: true,
//       });
//     }
//   }
// }, { deep: true });


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

                        <q-btn round flat dense icon="notifications">
                            <q-badge floating color="red" rounded>1</q-badge>
                        </q-btn>

                        <q-btn round flat dense icon="mail">
                            <q-badge floating color="red" rounded>1</q-badge>
                        </q-btn>

                        <q-btn dense flat no-wrap>
                            my account
                            <q-icon name="arrow_drop_down" size="16px" />

                            <q-menu auto-close >
                                <q-list dense>
                                    <q-item class="GL__menu-link-signed-in">
                                        <q-item-section>
                                            <div>Signed in as <strong>Mary</strong></div>
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
                                    <q-item clickable class="GL__menu-link">
                                        <q-item-section @click="router.visit(route('backoffice.dashboard'))">Sign out</q-item-section>
                                    </q-item>
                                </q-list>
                            </q-menu>
                        </q-btn>
                    </div>
                    </q-toolbar>
            </div>



            <q-tabs align="left" class="bg-grey-1 text-grey-9">
                <q-route-tab label="Dashboard" alert @click="router.visit(route('backoffice.dashboard'))" />

                <q-route-tab label="Dealership" @click="router.visit(route('backoffice.dashboard'))" />
                <q-route-tab label="Stock" @click="router.visit(route('backoffice.dashboard'))" />
                <q-route-tab label="Leads" @click="router.visit(route('backoffice.dashboard'))" />
                <q-route-tab label="Analytics">
                    <q-menu>
                        <q-list style="min-width: 100px">
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.dashboard'))">Dealers</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.dashboard'))">Branches</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.dashboard'))">Users</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.dashboard'))">Sale People</q-item-section>
                            </q-item>
                        </q-list>
                    </q-menu>
                </q-route-tab>

                <q-route-tab label="System">
                    <q-menu>
                        <q-list dense style="min-width: 100px">
                            <q-item clickable v-close-popup>
                                <q-item-section @click="router.visit(route('backoffice.dashboard'))">User Management</q-item-section>
                            </q-item>
                            <q-item clickable v-close-popup>
                                <q-item-section>New</q-item-section>
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
                                            @click="router.visit(route('backoffice.dashboard'))"
                                            dense
                                            clickable
                                        >
                                            <q-item-section>Location Management</q-item-section>
                                        </q-item>
                                        <q-item
                                            dense
                                            clickable
                                        >
                                            <q-item-section>3rd level Label</q-item-section>
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

        <q-page-container>
            <q-page class="q-pa-xl">
                <slot/>
            </q-page>
        </q-page-container>

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
