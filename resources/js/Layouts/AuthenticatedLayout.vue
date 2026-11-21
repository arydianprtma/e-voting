<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Users,
    UserCheck,
    Settings,
    ExternalLink,
    LogOut,
    Menu,
    X,
    Vote,
    ChevronRight,
    BarChart3,
    Clock,
    GraduationCap,
    UserCog
} from 'lucide-vue-next';

const isMobileSidebarOpen = ref(false);
const page = usePage();

const appLogo = computed(() => page.props.app_setting?.logo_url || '/images/logo-default.png');
const appOrgName = computed(() => page.props.app_setting?.organization_name || 'DEMA Polkesmar');

const navItems = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: LayoutDashboard,
    },
    {
        name: 'Pasangan Calon',
        route: 'admin.candidates',
        icon: Users,
    },
    {
        name: 'Jurusan & Prodi',
        route: 'admin.departments',
        icon: GraduationCap,
    },
    {
        name: 'Sesi Pemilihan',
        route: 'admin.sessions',
        icon: Clock,
    },
    {
        name: 'Daftar Pemilih (DPT)',
        route: 'admin.voters',
        icon: UserCheck,
    },
    {
        name: 'Pengaturan',
        route: 'admin.settings',
        icon: Settings,
    },
    {
        name: 'Akun & Profil',
        route: 'profile.edit',
        icon: UserCog,
    },
];
</script>

<template>
    <div class="h-screen w-screen overflow-hidden bg-slate-50 text-slate-900 flex selection:bg-slate-900 selection:text-white font-sans antialiased">
        <!-- Sidebar Desktop (Bersih, Terintegrasi, Bebas Pola AI Slop) -->
        <aside class="hidden lg:flex lg:flex-col w-64 h-full bg-white border-r border-slate-200 shrink-0 select-none">
            <!-- Brand & Identitas Kelembagaan -->
            <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-100 shrink-0">
                <img
                    :src="appLogo"
                    :alt="appOrgName"
                    class="w-10 h-10 object-contain shrink-0"
                />
                <div class="overflow-hidden">
                    <div class="font-bold text-sm text-slate-950 tracking-tight leading-tight truncate">
                        {{ appOrgName }}
                    </div>
                    <div class="text-xs text-slate-800 font-semibold leading-tight truncate">
                        Panel Kontrol Pemira
                    </div>
                </div>
            </div>

            <!-- Navigasi Menu -->
            <div class="flex-1 px-3.5 py-5 space-y-6 overflow-y-auto">
                <div class="space-y-1">
                    <div class="px-2 mb-2.5 text-[11px] font-bold tracking-wider uppercase text-slate-700">
                        Menu Utama
                    </div>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="group flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition-colors"
                            :class="route().current(item.route) ? 'bg-slate-900 text-white font-semibold shadow-2xs' : 'text-slate-800 hover:text-slate-950 hover:bg-slate-100 font-semibold'"
                        >
                            <component
                                :is="item.icon"
                                class="w-[18px] h-[18px] shrink-0 transition-colors"
                                :class="route().current(item.route) ? 'text-white' : 'text-slate-600 group-hover:text-slate-900'"
                            />
                            <span class="truncate">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="space-y-1 pt-3 border-t border-slate-100">
                    <div class="px-2 mb-2.5 text-[11px] font-bold tracking-wider uppercase text-slate-700">
                        Portal Publik
                    </div>
                    <div class="space-y-1">
                        <Link
                            :href="route('home')"
                            target="_blank"
                            class="group flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm text-slate-800 hover:text-slate-950 hover:bg-slate-100 font-semibold transition-colors"
                        >
                            <span class="truncate">Bilik Suara</span>
                            <ExternalLink class="w-4 h-4 text-slate-600 group-hover:text-slate-900" />
                        </Link>

                        <Link
                            :href="route('quick_count.show')"
                            target="_blank"
                            class="group flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm text-slate-800 hover:text-slate-950 hover:bg-slate-100 font-semibold transition-colors"
                        >
                            <span class="truncate">Quick Count</span>
                            <ExternalLink class="w-4 h-4 text-slate-600 group-hover:text-slate-900" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer Sidebar: Akun Panitia (Terintegrasi Alami) -->
            <div class="p-3.5 border-t border-slate-100 bg-white shrink-0">
                <div class="p-1.5 rounded-lg flex items-center justify-between gap-2.5">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-2.5 overflow-hidden flex-1 p-1 -m-1 rounded-md hover:bg-slate-50 transition-colors"
                        title="Pengaturan akun admin"
                    >
                        <div class="w-8 h-8 rounded-full bg-slate-900 border border-slate-900 flex items-center justify-center font-bold text-xs text-white shrink-0">
                            {{ $page.props.auth.user.name.charAt(0) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-semibold text-slate-900 truncate">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-slate-700 font-medium truncate">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>
                    </Link>

                    <div class="flex items-center gap-1 shrink-0">
                        <Link
                            :href="route('profile.edit')"
                            class="p-1.5 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                            title="Pengaturan profil & kata sandi"
                        >
                            <UserCog class="w-4 h-4" />
                        </Link>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-1.5 rounded-md text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                            title="Keluar dari panel admin"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Sidebar Drawer Mobile dengan Animasi Halus (Slide & Fade) -->
        <div
            class="fixed inset-0 z-50 lg:hidden pointer-events-none"
            :class="{ 'pointer-events-auto': isMobileSidebarOpen }"
        >
            <!-- Backdrop Gelap dengan Transisi Fade -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isMobileSidebarOpen"
                    class="fixed inset-0 bg-slate-950/50 backdrop-blur-xs"
                    @click="isMobileSidebarOpen = false"
                />
            </Transition>

            <!-- Drawer Panel dengan Transisi Slide dari Kiri -->
            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-250 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div
                    v-if="isMobileSidebarOpen"
                    class="relative w-72 max-w-[82%] h-full bg-white text-slate-900 flex flex-col z-10 border-r border-slate-200 shadow-2xl"
                >
                    <!-- Header Drawer Mobile -->
                    <div class="h-16 px-5 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <img
                                :src="appLogo"
                                :alt="appOrgName"
                                class="w-8 h-8 object-contain shrink-0"
                            />
                            <div class="overflow-hidden">
                                <span class="font-bold text-sm text-slate-950 leading-tight block truncate">{{ appOrgName }}</span>
                                <span class="text-xs text-slate-800 font-semibold leading-tight block truncate">Panel Kontrol</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="isMobileSidebarOpen = false"
                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                            aria-label="Tutup menu navigasi"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Daftar Menu Navigasi -->
                    <div class="flex-1 px-3.5 py-4 space-y-4 overflow-y-auto">
                        <div class="space-y-1">
                            <div class="px-2 mb-2 text-[11px] font-bold tracking-wider uppercase text-slate-700">
                                Menu Utama
                            </div>
                            <nav class="space-y-1">
                                <Link
                                    v-for="item in navItems"
                                    :key="item.route"
                                    :href="route(item.route)"
                                    @click="isMobileSidebarOpen = false"
                                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                                    :class="route().current(item.route) ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-800 hover:bg-slate-100 hover:text-slate-950'"
                                >
                                    <component
                                        :is="item.icon"
                                        class="w-[18px] h-[18px] shrink-0"
                                        :class="route().current(item.route) ? 'text-white' : 'text-slate-600'"
                                    />
                                    <span class="truncate">{{ item.name }}</span>
                                </Link>
                            </nav>
                        </div>

                        <div class="pt-3 border-t border-slate-100 space-y-1">
                            <div class="px-2 mb-2 text-[11px] font-bold tracking-wider uppercase text-slate-700">
                                Portal Publik
                            </div>
                            <Link
                                :href="route('home')"
                                target="_blank"
                                class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm text-slate-800 hover:bg-slate-100 font-semibold transition-colors"
                            >
                                <span class="truncate">Bilik Suara</span>
                                <ExternalLink class="w-4 h-4 text-slate-600" />
                            </Link>
                            <Link
                                :href="route('quick_count.show')"
                                target="_blank"
                                class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm text-slate-800 hover:bg-slate-100 font-semibold transition-colors"
                            >
                                <span class="truncate">Quick Count</span>
                                <ExternalLink class="w-4 h-4 text-slate-600" />
                            </Link>
                        </div>
                    </div>

                    <!-- Footer User Info & Logout -->
                    <div class="p-3.5 border-t border-slate-100 bg-white shrink-0 space-y-2">
                        <Link
                            :href="route('profile.edit')"
                            @click="isMobileSidebarOpen = false"
                            class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-50 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                            <div class="overflow-hidden flex-1">
                                <div class="text-xs font-semibold text-slate-900 truncate">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-[11px] text-slate-700 font-medium truncate">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </Link>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="w-full flex items-center justify-center gap-2 py-2 px-3 border border-slate-300 text-slate-900 hover:bg-slate-50 hover:text-rose-600 text-xs font-semibold rounded-lg transition-colors"
                        >
                            <LogOut class="w-3.5 h-3.5" />
                            <span>Keluar Sistem</span>
                        </Link>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Area Konten Utama -->
        <div class="flex-1 flex flex-col h-full min-w-0 overflow-hidden">
            <!-- Topbar Header (Terkunci & Menjangkau Penuh Ujung ke Ujung) -->
            <header class="h-16 shrink-0 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 flex items-center justify-between z-10 w-full">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="isMobileSidebarOpen = true"
                        class="lg:hidden p-2 rounded-md text-slate-600 hover:bg-slate-100 focus:outline-none"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-2 text-xs text-slate-700 font-medium">
                        <span class="hidden sm:inline">Panitia Pemira</span>
                        <ChevronRight class="hidden sm:inline w-3 h-3 text-slate-600" />
                        <span class="font-bold text-slate-950">
                            <slot name="title">Dashboard</slot>
                        </span>
                    </div>
                </div>

                <!-- Bagian Kanan Topbar: Status / Aksi Cepat -->
                <div class="flex items-center gap-3">
                    <slot name="actions" />
                </div>
            </header>

            <!-- Main Content Container (Bergulir Rapi Penuh) -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="w-full">
                    <!-- Notifikasi Flash Sukses -->
                    <div
                        v-if="$page.props.flash?.success"
                        class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-xs animate-in fade-in"
                    >
                        <span>{{ $page.props.flash.success }}</span>
                    </div>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
