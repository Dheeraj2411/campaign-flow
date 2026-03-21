<template>
    <div class="flex h-screen overflow-hidden bg-gray-50 font-sans">

        <!-- ── Mobile overlay ─────────────────────────────── -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen && isMobile"
                class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- ── Sidebar ─────────────────────────────────────── -->
        <aside
            class="fixed left-0 top-0 h-full z-50 bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-200 ease-out md:relative md:translate-x-0"
            :class="[
                (sidebarCollapsed && !isMobile) ? 'w-16' : 'w-64',
                (isMobile && !sidebarOpen) ? '-translate-x-full' : 'translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 shrink-0">
                <Transition name="fade">
                    <span v-if="!sidebarCollapsed || isMobile"
                          class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600 text-[24px]">send</span>
                        PingOS
                    </span>
                    <span v-else class="material-symbols-outlined text-[24px] text-indigo-600 mx-auto">send</span>
                </Transition>
                <button
                    v-if="!isMobile"
                    class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                >
                    <span class="material-symbols-outlined text-[18px]">
                        {{ sidebarCollapsed ? 'menu_open' : 'menu' }}
                    </span>
                </button>
            </div>

            <!-- Nav items -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navItems" :key="item.route">
                    <Link
                        :href="route(item.route)"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group"
                        :class="isActiveRoute(item.route) ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'font-medium'"
                    >
                        <span class="material-symbols-outlined text-[20px] group-hover:text-indigo-600 transition-colors"
                              :class="isActiveRoute(item.route) ? 'text-indigo-600' : 'text-gray-400'"
                              :style="isActiveRoute(item.route) ? { fontVariationSettings: '\'FILL\' 1' } : {}">
                            {{ item.icon }}
                        </span>
                        <Transition name="fade">
                            <span v-if="!sidebarCollapsed || isMobile" class="text-sm truncate">{{ item.label }}</span>
                        </Transition>
                    </Link>
                </template>
            </nav>

            <!-- User footer -->
            <div class="border-t border-gray-200 px-3 py-3 shrink-0">
                <div class="flex items-center gap-1">
                    <Link :href="route('profile.edit')" class="flex-1 flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700
                                    flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ userInitial }}
                        </div>
                        <Transition name="fade">
                            <div v-if="!sidebarCollapsed || isMobile" class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.email }}</p>
                            </div>
                        </Transition>
                    </Link>
                    <Link :href="route('logout')" method="post" as="button"
                          class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-red-500 transition-colors shrink-0">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                    </Link>
                </div>
                <div v-if="$page.props.auth.user.is_admin" class="mt-2 text-center pb-1">
                    <Link :href="route('admin.dashboard')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors hover:underline">
                        Switch to Admin View &rarr;
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ── Main area ───────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="relative z-30 bg-white border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 md:px-6 py-3 sm:py-0 min-h-[64px] shrink-0 gap-3 sm:gap-0">
                <div class="flex items-center justify-between sm:w-auto w-full gap-3">
                    <!-- Mobile hamburger -->
                    <button
                        class="md:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <span class="material-symbols-outlined">menu</span>
                    </button>

                    <!-- Page heading -->
                    <div id="header-content">
                        <!-- Populated via Teleport from HeadTitle.vue -->
                    </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-3">
                    <!-- Actions Slot -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-hide">
                        <slot name="actions" />
                    </div>

                    <!-- Notifications -->
                    <div ref="notificationDropdownRef" class="relative">
                        <button 
                            @click="notificationsOpen = !notificationsOpen"
                            class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors"
                        >
                            <span class="material-symbols-outlined text-[20px]">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 ring-2 ring-white"></span>
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <Transition name="fade">
                            <div v-if="notificationsOpen"
                                 class="absolute -right-2 top-full mt-3 w-80 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden z-50">
                                <!-- Indicator pointer -->
                                <div class="absolute -top-1 right-5 w-2.5 h-2.5 bg-gray-50 rotate-45 border-t border-l border-gray-200"></div>
                                
                                <div class="relative px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                                    <h3 class="font-semibold text-gray-900 text-sm">Notifications</h3>
                                    <button 
                                        v-if="unreadCount > 0"
                                        @click="markAllAsRead"
                                        class="text-xs text-indigo-600 hover:text-indigo-700 font-medium transition-colors"
                                    >
                                        Mark all read
                                    </button>
                                </div>
                                
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="notifications.length === 0" class="p-6 text-center text-gray-500 text-sm flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-gray-300">notifications_off</span>
                                        No unread notifications
                                    </div>
                                    
                                    <template v-else>
                                        <div v-for="notif in notifications" :key="notif.id"
                                             class="flex gap-3 p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                                             :class="{ 'bg-indigo-50/30': notif.read_at === null }"
                                             @click="handleNotificationClick(notif)">
                                            <!-- Icon -->
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                                                 :class="getNotifIconClass(notif.data.color)">
                                                <span class="material-symbols-outlined text-[16px]">{{ notif.data.icon || 'notifications' }}</span>
                                            </div>
                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">
                                                    {{ notif.data.title }}
                                                </p>
                                                <p class="text-xs mt-0.5 line-clamp-2 leading-relaxed text-gray-500">
                                                    {{ notif.data.message }}
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                    {{ new Date(notif.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                                </p>
                                            </div>
                                            <!-- Unread dot -->
                                            <div v-if="notif.read_at === null" class="w-2 h-2 rounded-full bg-indigo-600 mt-2 shrink-0"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Workspace badge -->
                    <Link :href="route('settings')" class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-xl cursor-pointer hover:bg-indigo-100 transition-colors duration-200">
                        <span class="material-symbols-outlined text-[14px] text-indigo-600">corporate_fare</span>
                        <span class="text-xs font-medium text-indigo-600">
                            {{ $page.props.workspace?.name ?? 'My Workspace' }}
                        </span>
                    </Link>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 relative">
                <!-- Flash Messages -->
                <TransitionGroup 
                    tag="div" 
                    name="list" 
                    class="fixed top-20 right-4 z-[100] flex flex-col gap-3 w-80 pointer-events-none"
                >
                    <div v-if="$page.props.flash.success" :key="'success'" 
                         class="bg-white border-l-4 border-emerald-500 shadow-lg rounded-xl p-4 flex items-center gap-3 pointer-events-auto">
                        <div class="bg-emerald-100 p-2 rounded-xl">
                            <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
                        </div>
                        <div class="flex-1 pr-4">
                            <p class="text-sm font-semibold text-gray-900">Success</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $page.props.flash.success }}</p>
                        </div>
                        <button @click="$page.props.flash.success = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>

                    <div v-if="$page.props.flash.error" :key="'error'" 
                         class="bg-white border-l-4 border-red-500 shadow-lg rounded-xl p-4 flex items-center gap-3 pointer-events-auto">
                        <div class="bg-red-100 p-2 rounded-xl">
                            <span class="material-symbols-outlined text-red-600 text-lg">error</span>
                        </div>
                        <div class="flex-1 pr-4">
                            <p class="text-sm font-semibold text-gray-900">Error</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $page.props.flash.error }}</p>
                        </div>
                        <button @click="$page.props.flash.error = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>
                </TransitionGroup>

                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    title:    { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: null },
})

const page = usePage()

const sidebarOpen      = ref(false)
const sidebarCollapsed = ref(false)
const isMobile         = ref(false)

// Notifications state
const notifications     = ref([])
const notificationsOpen = ref(false)
const unreadCount       = computed(() => {
    if (!notifications.value || !Array.isArray(notifications.value)) return 0;
    return notifications.value.filter(n => n && n.read_at === null).length;
})

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/notifications')
        notifications.value = response.data.notifications
    } catch (e) {
        console.error('Failed to fetch notifications', e)
    }
}

const markAsRead = async (id) => {
    try {
        await axios.post(`/notifications/${id}/read`)
        const notif = notifications.value.find(n => n.id === id)
        if (notif) notif.read_at = new Date().toISOString()
    } catch (e) {
        console.error('Failed to mark notification as read', e)
    }
}

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/read-all')
        notifications.value.forEach(n => n.read_at = new Date().toISOString())
    } catch (e) {
        console.error('Failed to mark all as read', e)
    }
}

const handleNotificationClick = async (notif) => {
    if (notif.read_at === null) {
        await markAsRead(notif.id)
    }
    notificationsOpen.value = false
    
    if (notif.data.url) {
        router.visit(notif.data.url)
    }
}

const getNotifIconClass = (color) => {
    switch (color) {
        case 'danger':  return 'bg-red-100 text-red-600';
        case 'warning': return 'bg-amber-100 text-amber-600';
        case 'success': return 'bg-emerald-100 text-emerald-600';
        case 'info':
        case 'primary': return 'bg-indigo-100 text-indigo-600';
        default:        return 'bg-gray-100 text-gray-600';
    }
}

// Click outside handler for dropdown
const notificationDropdownRef = ref(null)
const closeDropdown = (e) => {
    if (notificationsOpen.value && notificationDropdownRef.value && !notificationDropdownRef.value.contains(e.target)) {
        notificationsOpen.value = false
    }
}

const checkMobile = () => { isMobile.value = window.innerWidth < 768 }

onMounted(() => { 
    checkMobile()
    window.addEventListener('resize', checkMobile)
    document.addEventListener('click', closeDropdown)
    fetchNotifications()

    // Listen for real-time notifications via Reverb
    if (window.Echo && page.props.auth?.user) {
        window.Echo.private(`App.Models.User.${page.props.auth.user.id}`)
            .notification((notification) => {
                notifications.value.unshift({
                    id: notification.id,
                    type: notification.type,
                    data: notification,
                    read_at: null,
                    created_at: new Date().toISOString()
                });
                
                try {
                    const audio = new Audio('/sounds/notification.mp3')
                    audio.play().catch(e => {})
                } catch (e) {}
            });
    }
})
onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
    document.removeEventListener('click', closeDropdown)
    
    if (window.Echo && page.props.auth?.user) {
        window.Echo.leave(`App.Models.User.${page.props.auth.user.id}`)
    }
})

// Auto-hide flash messages
watch(() => page.props.flash, (flash) => {
    if (flash.success || flash.error) {
        setTimeout(() => {
            if (flash.success) page.props.flash.success = null
            if (flash.error) page.props.flash.error = null
        }, 5000)
    }
}, { deep: true })

const userInitial = computed(() =>
    (page.props.auth?.user?.name ?? 'U').charAt(0).toUpperCase()
)

const navItems = [
    { route: 'dashboard',       icon: 'grid_view',        label: 'Dashboard'  },
    { route: 'contacts.index',  icon: 'contacts',         label: 'Contacts'   },
    { route: 'campaigns.index', icon: 'campaign',         label: 'Campaigns'  },
    { route: 'templates.index', icon: 'description',      label: 'Templates'  },
    { route: 'inbox',           icon: 'forum',            label: 'Inbox'      },
    { route: 'analytics',       icon: 'bar_chart_4_bars', label: 'Analytics'  },
    { route: 'billing',         icon: 'credit_card',      label: 'Billing'    },
    { route: 'settings',        icon: 'settings',         label: 'Settings'   },
]

const isActiveRoute = (routeName) => {
    try { 
        const base = routeName.split('.')[0]
        return route().current(base + '*') || route().current(routeName)
    } catch { return false }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from,  .fade-leave-to     { opacity: 0; }

.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.list-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
