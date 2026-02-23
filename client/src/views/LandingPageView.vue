<script setup lang="ts">
import { useRoute } from "vue-router";
import { onMounted, ref, watch } from "vue";
import Logo from "@/components/Logo.vue";
import OrbitNavigationMenu from "@/components/landing-page/OrbitNavigationMenu.vue";
import { useDeviceType } from "@/composables/useDeviceType";
import MobileNavigationMenu from "@/components/landing-page/MobileNavigationMenu.vue";

const route = useRoute();
const logoPosition = ref<"center" | "top">("center");

watch(
  () => route.name,
  (newName) => {
    setTimeout(() => {
      logoPosition.value = newName === "home" ? "center" : "top";
    }, 50);
  }
);

// Sync on initial load
onMounted(() => {
  logoPosition.value = route.name === "home" ? "center" : "top";
});

const { deviceType } = useDeviceType();

// console.log(deviceType.value);
</script>

<template>
  <div
    class="relative flex justify-center w-full min-h-screen px-6 py-16 overflow-hidden max-w-screen"
  >
    <!-- 🌫️ White-to-Transparent Gradient Overlay -->
    <div
      class="absolute inset-0 z-0 bg-gradient-to-r from-white/100 to-transparent"
    ></div>

    <!-- 🔮 Floating Blobs -->
    <div class="absolute inset-0 z-0 pointer-events-none">
      <div
        class="absolute top-[20%] left-[10%] w-32 h-32 bg-lime-600 rounded-full blur-[100px] opacity-80"
      ></div>
      <div
        class="absolute bottom-[15%] right-[5%] w-40 h-40 bg-amber-600 rounded-full blur-[120px] opacity-75"
      ></div>
      <div
        class="absolute top-[45%] right-[30%] w-36 h-36 bg-teal-800 rounded-full blur-[100px] opacity-60"
      ></div>
    </div>

    <!-- 🌀 Document Orbit Graphic -->
    <div
      class="relative z-50 flex flex-col items-center justify-between w-full max-w-6xl gap-12 md:flex-row"
    >
      <div class="flex flex-col justify-between w-full max-w-md">
        <!-- Logo -->

        <!-- <div
          class="absolute transition-all duration-500 ease-in-out"
          :class="
            logoPosition === 'center' ? 'top-1/2 -translate-y-1/2' : 'top-0'
          "
        >
          <Logo class="mx-auto" />
        </div> -->

        <div
          class="absolute w-full transition-all duration-500 ease-in-out sm:w-fit"
          :class="
            logoPosition === 'center' ? 'top-1/2 -translate-y-1/2 ' : 'top-0'
          "
        >
          <div class="flex w-full">
            <Logo class="mx-auto" />
          </div>
        </div>

        <div class="bottom-0 pt-[200px] sm:pt-0">
          <router-view v-slot="{ Component }">
            <transition
              name="fade-slide"
              mode="out-in"
            >
              <!-- <div> -->
              <Component
                :is="Component"
                :key="route.name"
              />
              <!-- </div> -->
            </transition>
          </router-view>
        </div>
      </div>
      <!-- 📄 Orbit Panel -->
      <OrbitNavigationMenu v-if="deviceType == 'desktop'" />

      <MobileNavigationMenu v-if="deviceType == 'mobile'" />

      <!-- <div class=""></div> -->
    </div>
    <!-- Image Background -->
    <img
      v-if="deviceType == 'desktop'"
      class="absolute top-0 sm:-right-[200px] h-screen object-cover blur-[3px] -z-50"
      src="@/assets/img/bg-landingpage.png"
      alt=""
    />
    <img
      v-if="deviceType == 'mobile'"
      class="absolute top-0 right-0 h-screen object-cover blur-[3px] -z-50"
      src="@/assets/img/dts-mobile-bg.png"
      alt=""
    />
  </div>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.beat {
  animation: beat 15s ease-in-out infinite;
}

.custom-dash {
  border-style: dashed;
  border-radius: 100rem;
  border-width: 1px;
  border-image: none;
  border-spacing: 10px;
  /* Optional: Use SVG for full control */
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes beat {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

@keyframes spin2 {
  to {
    transform: rotate(360deg);
  }
}
@keyframes beat2 {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.animate-spin-beat {
  animation: spin2 120s linear infinite, beat2 2s ease-in-out infinite;
}

/* Add this to your global CSS or scoped <style> */
.mask-gradient-r {
  mask-image: linear-gradient(to left, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0));
  -webkit-mask-image: linear-gradient(
    to left,
    rgba(0, 0, 0, 1),
    rgba(0, 0, 0, 0)
  );
}

.orbit-container {
  width: 500px;
  height: 500px;
  border: 2px dashed limegreen;
  border-radius: 50%;
  transform: rotate(30deg); /* Tilt the orbit */
  animation: spin 20s linear infinite;
  z-index: 0;
}

.orbiting-object {
  width: 10px;
  height: 10px;
  background: limegreen;
  border-radius: 50%;
  position: absolute;
  top: -5;
  left: 50%;

  transform: translateX(-50%);
}

.iActive {
  transform: perspective(800px) rotateX(8deg) rotateY(8deg) scale(1.05);
  transition: transform 0.3s ease;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.tilt {
  transition: transform 0.3s ease;
  transform-style: preserve-3d;
}

.tilt:hover {
  transform: perspective(800px) rotateX(8deg) rotateY(8deg) scale(1.05);
}

.tilt-orbit {
  transition: transform 2s ease;
  transform-style: preserve-3d;
}

.tilt-orbit:hover {
  transform: perspective(800px) rotateX(8deg) rotateY(8deg) scale(1.05);
}

/* .fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
} */

/* .fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.fade-slide-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.fade-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(20px);
} */

.logo-default {
  position: relative;
  top: 1px;
  transition: top 1s ease 0.5s, transform 1s ease 1s;
}

.logo-fixed {
  position: absolute;
  top: 50px;
  transform: translateY(-40px);
  transition: top 1s ease 0.5s, transform 1s ease 1s;
}
</style>
