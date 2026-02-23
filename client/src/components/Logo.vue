<script setup lang="ts">
import { BiQrScan } from "vue-icons-plus/bi";
import { MdDocumentScanner } from "vue-icons-plus/md";
</script>

<template>
  <div class="flex items-center justify-center">
    <MdDocumentScanner  class="text-teal-800 size-20 sm:size-36" />
    <!-- <BiQrScan class="text-teal-800 size-36" /> -->
    <div>
      <h1 class="text-6xl font-bold text-amber-600 sm:text-9xl">DTS</h1>
      <div class="text-xs sm:text-xl text-lime-600">Document Tracking System</div>
    </div>
  </div>
</template>
