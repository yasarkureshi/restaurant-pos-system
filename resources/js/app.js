import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import router from './router/index.js'
import App from './App.vue'
import '../css/app.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Vue3Toastify, {
    autoClose: 3000,
    position: 'top-right',
    theme: 'light',
})

app.config.globalProperties.$toast = toast

app.mount('#app')
