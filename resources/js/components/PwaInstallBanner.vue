<template>
  <div v-if="visible" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50">
    <div class="bg-white/95 dark:bg-gray-900/90 border rounded-lg shadow-lg px-4 py-3 flex items-center gap-3 max-w-xl">
      <div class="flex-1 text-sm text-gray-800 dark:text-gray-100">
        Install MaketuShop for a better experience — fast access and offline support.
      </div>
      <div class="flex items-center gap-2">
        <button @click="promptInstall" class="px-3 py-1 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Install</button>
        <button @click="visible = false" class="px-3 py-1 rounded border text-sm">Dismiss</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const deferredPrompt = ref(null)
const visible = ref(false)

function onBeforeInstallPrompt(e) {
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault()
  deferredPrompt.value = e
  // show a banner so user can choose to install
  visible.value = true
}

function promptInstall() {
  if (!deferredPrompt.value) return
  deferredPrompt.value.prompt()
  deferredPrompt.value.userChoice.then(() => {
    // hide banner after user responds
    visible.value = false
    deferredPrompt.value = null
  })
}

onMounted(() => {
  window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
})
</script>

<style scoped>
/* minimal styling left to Tailwind classes in template */
</style>
