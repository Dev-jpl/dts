<script setup>
import BaseButton from "@/components/ui/buttons/BaseButton.vue";

const props = defineProps({
    title: { type: String, default: "Modal Title" },
    isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "confirm"]);
function closeModal() {
    emit("close");
}
function confirmAction() {
    emit("confirm");
}
</script>
<template>
    <!-- Backdrop transition -->
    <Transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-20" leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-20" leave-to-class="opacity-0">
        <div v-if="isOpen" class="fixed inset-0 z-[900] bg-black opacity-25"></div>
    </Transition>

    <!-- Modal transition -->
    <Transition enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="-translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in transform" leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-4 opacity-0">
        <div @click="closeModal" v-if="isOpen"
            class="fixed inset-0 z-[1000] flex overflow-auto items-start justify-center">
            <div class="w-full max-w-xl mt-[3rem] bg-white rounded-lg shadow-xl dark:bg-gray-800" @click.stop>
                <!-- Header -->
                <div class="flex items-center justify-between p-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-center text-gray-900 dark:text-gray-100">
                        {{ title }}
                    </h2>
                    <button @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                        ✕
                    </button>
                </div>



                <!-- Body -->
                <!-- <div class="p-4 text-gray-700 dark:text-gray-300">
                    <div class="flex justify-center">
                        <slot />
                    </div>
                </div> -->


                <section>
                    <slot />
                </section>


                <!-- Footer -->
                <footer class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <slot name="footer" />
                </footer>


                <!-- <div class="flex justify-center p-4 mt-6 space-x-2">
                    <BaseButton backgroundClass="bg-red-600 hover:bg-red-700" @click="closeModal">
                        Cancel
                    </BaseButton>
                    <BaseButton
                        class="text-gray-800 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        @click="confirmAction">
                        Confirm
                    </BaseButton>
                </div> -->
            </div>
        </div>
    </Transition>
</template>
