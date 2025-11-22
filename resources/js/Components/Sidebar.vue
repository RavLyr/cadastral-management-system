<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Grid, Clipboard, Map, Search, Upload, LogOut, Menu } from 'lucide-vue-next';
import { LandPlot } from 'lucide-vue-next';

const page = usePage();

const currentRoute = computed(() => page.url);

const isActive = (routeName) => {
  return currentRoute.value.startsWith(routeName);
};

const username = computed(() => page.props.auth.user.username);

const menuItems = [
  {
    name: 'Dashboard',
    route: '/dashboard',
    icon: Grid,
  },
  {
    name: 'Data Tanah',
    route: '/tanah',
    icon: Clipboard,
  },
  {
    name: 'Peta Blok',
    route: '/peta',
    icon: Map,
  },
  {
    name: 'Pencarian',
    route: '/pencarian',
    icon: Search,
  },
  {
    name: 'Upload SISMIOP',
    route: '/sismiop',
    icon: Upload,
  },
];

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
<div>
  <!-- Hamburger Icon for Mobile -->
  <div class="fixed top-0 left-0 z-50 p-4 md:hidden">
      <button @click="toggleSidebar" class="p-2 rounded-md bg-gray-100 hover:bg-gray-200">
        <Menu class="w-6 h-6 text-gray-700" />
      </button>
</div>

  <!-- Sidebar -->
  <div
      :class="[
        'fixed top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col z-40 transition-transform',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'md:translate-x-0'
      ]"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
      <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
        <LandPlot class="w-6 h-6 text-blue-600" />
      </div>
      <div>
        <h2 class="text-sm font-bold text-gray-900">Land Records</h2>
        <p class="text-xs text-gray-500">{{ username }}</p>
      </div>
    </div>

    <!-- Menu Items -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <Link
        v-for="item in menuItems"
        :key="item.route"
        :href="item.route"
        :class="[
          'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
          isActive(item.route)
            ? 'bg-blue-50 text-blue-600'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
        ]"
      >
        <component :is="item.icon" class="w-5 h-5" />
        <span>{{ item.name }}</span>
      </Link>
    </nav>

    <!-- Logout Button -->
    <div class="px-3 py-4 border-t border-gray-100">
      <Link
        href="/logout"
        method="post"
        as="button"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors w-full"
      >
        <LogOut class="w-5 h-5" />
        <span>Logout</span>
      </Link>
    </div>
  </div>
  </div>
</template>
