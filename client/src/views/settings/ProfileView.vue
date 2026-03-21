<template>
    <div class="flex-1 w-full h-full">
        <div class="flex items-center justify-between p-4">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Profile Information</h1>
                <p class="mt-1 text-xs text-gray-500">Update your account's profile information and email address.</p>
            </div>
            <BaseButton @click="handleSubmit" :disabled="saving || loading"
                class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg v-if="saving" class="w-4 h-4 mr-2 -ml-1 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Save Changes
            </BaseButton>
        </div>
        <hr class="mb-6 text-gray-200" />

        <div v-if="loading" class="flex justify-center py-8">
            <div class="w-8 h-8 border-b-2 border-teal-600 rounded-full animate-spin"></div>
        </div>

        <form v-else @submit.prevent="handleSubmit" class="max-w-xl p-4 space-y-4">
            <div>
                <label for="name" class="block mb-1.5 text-xs font-medium text-gray-600">Name</label>
                <input type="text" id="name" v-model="form.name"
                    class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                    :class="{ 'border-red-400 ring-1 ring-red-400': errors.name }" />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block mb-1.5 text-xs font-medium text-gray-600">Email</label>
                <input type="email" id="email" v-model="form.email"
                    class="w-full px-3 py-2 text-xs bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-teal-500 focus:border-teal-500"
                    :class="{ 'border-red-400 ring-1 ring-red-400': errors.email }" />
                <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
            </div>

            <div v-if="profile" class="pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                    Office: <span class="font-medium text-gray-700">{{ profile.office_name }}</span>
                </p>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'
import BaseButton from '@/components/ui/buttons/BaseButton.vue'

const { loading, profile, fetchProfile, updateProfile } = useSettings()
const toast = useToast()

const form = reactive({
    name: '',
    email: '',
})

const errors = reactive<Record<string, string>>({})
const saving = ref(false)

onMounted(async () => {
    try {
        const data = await fetchProfile()
        form.name = data.name
        form.email = data.email
    } catch (e) {
        toast.error('Failed to load profile')
    }
})

async function handleSubmit() {
    // Clear errors
    Object.keys(errors).forEach(key => delete errors[key])

    // Basic validation
    if (!form.name.trim()) {
        errors.name = 'Name is required'
        return
    }
    if (!form.email.trim()) {
        errors.email = 'Email is required'
        return
    }

    saving.value = true
    try {
        await updateProfile({ name: form.name, email: form.email })
        toast.success('Profile updated successfully')
    } catch (e: any) {
        if (e.response?.data?.errors) {
            Object.assign(errors, e.response.data.errors)
        } else {
            toast.error(e.response?.data?.message || 'Failed to update profile')
        }
    } finally {
        saving.value = false
    }
}
</script>
