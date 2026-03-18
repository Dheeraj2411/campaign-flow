<template>
    <div class="flex h-screen overflow-hidden bg-admin-bg font-sans">

        <!-- ── Mobile overlay ─────────────────────────────── -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen && isMobile"
                class="fixed inset-0 z-[55] bg-black/40 backdrop-blur-sm"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- ── Sidebar ─────────────────────────────────────── -->
        <Transition name="slide-in-left">
            <aside
                v-show="sidebarOpen || !isMobile"
                class="fixed md:relative z-[60] flex flex-col h-full bg-white shadow-sidebar
                       transition-all duration-250 ease-out"
                :class="sidebarCollapsed && !isMobile ? 'w-16' : 'w-64'"
            >
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-slate-100 shrink-0">
                    <Transition name="fade">
                        <span v-if="!sidebarCollapsed || isMobile"
                              class="text-lg font-black tracking-tight text-admin-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">send</span>
                            CampaignFlow
                        </span>
                        <span v-else class="material-symbols-outlined text-[22px] text-admin-primary mx-auto">send</span>
                    </Transition>
                    <button
                        v-if="!isMobile"
                        class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-admin-primary transition-colors"
                        @click="sidebarCollapsed = !sidebarCollapsed"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            {{ sidebarCollapsed ? 'menu_open' : 'menu' }}
                        </span>
                    </button>
                </div>

                <!-- Nav items -->
                <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto">
                    <template v-for="item in navItems" :key="item.route">
                        <Link
                            :href="route(item.route)"
                            class="nav-item"
                            :class="{ active: isActiveRoute(item.route) }"
                        >
                            <span class="material-symbols-outlined icon"
                                  :style="isActiveRoute(item.route) ? { fontVariationSettings: '\'FILL\' 1' } : {}">
                                {{ item.icon }}
                            </span>
                            <Transition name="fade">
                                <span v-if="!sidebarCollapsed || isMobile" class="truncate">{{ item.label }}</span>
                            </Transition>
                        </Link>
                    </template>
                </nav>

                <!-- Workspace + user footer -->
                <div class="border-t border-slate-100 px-3 py-3 shrink-0">
                    <div class="flex items-center gap-1">
                        <Link :href="route('profile.edit')" class="flex-1 flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors min-w-0">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-admin-primary to-admin-highlight
                                        flex items-center justify-center text-white text-xs font-bold shrink-0">
                                {{ userInitial }}
                            </div>
                            <Transition name="fade">
                                <div v-if="!sidebarCollapsed || isMobile" class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 truncate">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </Transition>
                        </Link>
                        <Link :href="route('logout')" method="post" as="button"
                              class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-red-500 transition-colors shrink-0">
                            <span class="material-symbols-outlined text-[16px]">logout</span>
                        </Link>
                    </div>
                    <div v-if="$page.props.auth.user.is_admin" class="mt-2 text-center pb-1">
                        <Link :href="route('admin.dashboard')" class="text-[11px] font-semibold text-admin-primary/80 hover:text-admin-primary transition-colors hover:underline">
                            Switch to Admin View &rarr;
                        </Link>
                    </div>
                </div>
            </aside>
        </Transition>

        <!-- ── Main area ───────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="relative z-50 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 md:px-6 py-3 sm:py-0 min-h-[64px] shrink-0 gap-3 sm:gap-0">
                <div class="flex items-center justify-between sm:w-auto w-full gap-3">
                    <!-- Mobile hamburger -->
                    <button
                        class="md:hidden p-2 rounded-xl hover:bg-slate-100 text-slate-500"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <span class="material-symbols-outlined">menu</span>
                    </button>

                    <!-- Page heading -->
                    <div>
                        <h1 class="text-base font-semibold text-slate-900">{{ title }}</h1>
                        <p v-if="subtitle" class="text-xs text-slate-400">{{ subtitle }}</p>
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
                            class="relative p-2 rounded-xl hover:bg-slate-100 text-slate-500 transition-colors"
                        >
                            <span class="material-symbols-outlined text-[20px]">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-admin-alert opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-admin-alert ring-2 ring-white"></span>
                            </span>
                        </button>

                        <!-- Dropdown -->
                        <Transition name="fade">
                            <div v-if="notificationsOpen"
                                 class="absolute -right-2 top-full mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50 animate-in slide-in-from-top-2 duration-200">
                                <!-- Indicator pointer -->
                                <div class="absolute -top-1 right-5 w-2.5 h-2.5 bg-slate-50 rotate-45 border-t border-l border-slate-200"></div>
                                
                                <div class="relative px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 backdrop-blur-sm">
                                    <h3 class="font-bold text-slate-800 text-sm tracking-tight">Notifications</h3>
                                    <button 
                                        v-if="unreadCount > 0"
                                        @click="markAllAsRead"
                                        class="text-xs text-admin-primary hover:text-admin-highlight font-bold transition-colors"
                                    >
                                        Mark all read
                                    </button>
                                </div>
                                
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="notifications.length === 0" class="p-6 text-center text-slate-500 text-sm flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-slate-300">notifications_off</span>
                                        No unread notifications
                                    </div>
                                    
                                    <template v-else>
                                        <div v-for="notif in notifications" :key="notif.id"
                                             class="flex gap-3 p-4 border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer"
                                             :class="{ 'bg-blue-50/30': notif.read_at === null }"
                                             @click="handleNotificationClick(notif)">
                                            <!-- Icon -->
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                                                 :class="`bg-${notif.data.color || 'primary'}/10 text-${notif.data.color || 'primary'}`">
                                                <span class="material-symbols-outlined text-[16px]">{{ notif.data.icon || 'notifications' }}</span>
                                            </div>
                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span v-if="notif.data.color" 
                                                          :class="getImportanceBadgeClass(notif.data.color)"
                                                          class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider">
                                                        {{ notif.data.color }}
                                                    </span>
                                                    <p class="text-sm font-bold truncate flex-1" :class="getImportanceTextColor(notif.data.color)">
                                                        {{ notif.data.title }}
                                                    </p>
                                                </div>
                                                <p class="text-xs mt-0.5 line-clamp-2 leading-relaxed font-medium" :class="notif.data.color ? getImportanceTextColor(notif.data.color, true) : 'text-slate-500'">
                                                    {{ notif.data.message }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-2 font-bold tracking-wide uppercase opacity-70 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[12px]">schedule</span>
                                                    {{ new Date(notif.created_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                                </p>
                                            </div>
                                            <!-- Unread dot -->
                                            <div v-if="notif.read_at === null" class="w-2 h-2 rounded-full bg-admin-primary mt-2 shrink-0"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Workspace badge -->
                    <Link :href="route('settings')" class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-admin-primary/8 rounded-xl cursor-pointer hover:bg-admin-primary/12 transition-colors">
                        <span class="material-symbols-outlined text-[14px] text-admin-primary">corporate_fare</span>
                        <span class="text-xs font-semibold text-admin-primary">
                            {{ $page.props.workspace?.name ?? 'My Workspace' }}
                        </span>
                    </Link>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 animate-fade-in relative">
                <!-- Flash Messages -->
                <TransitionGroup 
                    tag="div" 
                    name="list" 
                    class="fixed top-20 right-4 z-[100] flex flex-col gap-3 w-80 pointer-events-none"
                >
                    <div v-if="$page.props.flash.success" :key="'success'" 
                         class="bg-white border-l-4 border-green-500 shadow-2xl rounded-lg p-4 flex items-center gap-3 pointer-events-auto animate-in slide-in-from-right duration-500">
                        <div class="bg-green-100 p-2 rounded-full">
                            <span class="material-symbols-outlined text-green-600 text-lg">check_circle</span>
                        </div>
                        <div class="flex-1 pr-4">
                            <p class="text-sm font-bold text-slate-800">Success</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $page.props.flash.success }}</p>
                        </div>
                        <button @click="$page.props.flash.success = null" class="text-slate-300 hover:text-slate-500 transition-colors">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>

                    <div v-if="$page.props.flash.error" :key="'error'" 
                         class="bg-white border-l-4 border-red-500 shadow-2xl rounded-lg p-4 flex items-center gap-3 pointer-events-auto animate-in slide-in-from-right duration-500">
                        <div class="bg-red-100 p-2 rounded-full">
                            <span class="material-symbols-outlined text-red-600 text-lg">error</span>
                        </div>
                        <div class="flex-1 pr-4">
                            <p class="text-sm font-bold text-slate-800">Error</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $page.props.flash.error }}</p>
                        </div>
                        <button @click="$page.props.flash.error = null" class="text-slate-300 hover:text-slate-500 transition-colors">
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

const getImportanceTextColor = (color, isMessage = false) => {
    switch (color) {
        case 'danger':  return isMessage ? 'text-red-600/90' : 'text-red-600';
        case 'warning': return isMessage ? 'text-amber-600/90' : 'text-amber-600';
        case 'success': return isMessage ? 'text-green-600/90' : 'text-green-600';
        case 'info':
        case 'primary': return isMessage ? 'text-indigo-600/90' : 'text-indigo-600';
        default:        return isMessage ? 'text-slate-500' : 'text-slate-800';
    }
}

const getImportanceBadgeClass = (color) => {
    switch (color) {
        case 'danger':  return 'bg-red-100 text-red-700 border border-red-200';
        case 'warning': return 'bg-amber-100 text-amber-700 border border-amber-200';
        case 'success': return 'bg-green-100 text-green-700 border border-green-200';
        case 'info':
        case 'primary': return 'bg-indigo-100 text-indigo-700 border border-indigo-200';
        default:        return 'bg-slate-100 text-slate-700 border border-slate-200';
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
                // Add the new notification to the top of the list
                notifications.value.unshift({
                    id: notification.id,
                    type: notification.type,
                    data: notification,
                    read_at: null,
                    created_at: new Date().toISOString()
                });
                
                // Play notification sound
                try {
                    const audio = new Audio('/sounds/notification.mp3')
                    audio.play().catch(e => {}) // Ignore auto-play blocking errors
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
.slide-in-left-enter-active,
.slide-in-left-leave-active { transition: transform .25s ease; }
.slide-in-left-enter-from  { transform: translateX(-100%); }
.slide-in-left-leave-to    { transform: translateX(-100%); }

.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from,  .fade-leave-to     { opacity: 0; }

.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
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
