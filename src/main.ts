/**
 * main.ts
 *
 * Bootstraps Vuetify and other plugins then mounts the App`
 */

import { createPinia } from 'pinia'
import piniaPluginPersisteedstate from 'pinia-plugin-persistedstate'

// Composables
import { createApp } from 'vue'

// Plugins
import { registerPlugins } from '@/plugins'

// Components
import App from './App.vue'

// Styles
import 'unfonts.css'

const pinia = createPinia()
pinia.use(piniaPluginPersisteedstate)

const app = createApp(App).use(pinia)

registerPlugins(app)

app.mount('#app')
