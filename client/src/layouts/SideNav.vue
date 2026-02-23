<script setup lang="ts">
import BtnNewTransaction from "../components/BtnNewTransaction.vue";
import NavLogo from "../components/NavLogo.vue";
import SideNavItems from "../components/SideNavItems.vue";
</script>
<template>
  <div class="w-[50px] sm:w-[50px] md:w-[230px]"></div>
  <aside
    id="sidebar"
    class="bg-white fixed z-50 h-[100svh] sm:w-[50px] md:w-[230px] shadow border-r  md:flex flex-col  border-gray-200"
  >
    <div class="p-1 sm:p-4">
      <!-- Logo -->
      <NavLogo />

      <!-- Transaction Button -->
      <BtnNewTransaction />
    </div>
    <!-- Side Nav Items -->
    <SideNavItems />
  </aside>
</template>
