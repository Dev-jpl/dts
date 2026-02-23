import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { directives } from './directives'

const publicRoutes = ['/', '/login', '/register']

import { useAuthStore } from '@/stores/auth'

router.beforeEach((to, from, next) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.token && to.path !== '/login') {
        next('/login')
    } else if (auth.token && publicRoutes.includes(to.path)) {
        next('/dashboard')
    } else {
        next()
    }
})


// router.beforeEach((to, from, next) => {
//     const auth = useAuthStore()

//     if (to.meta.requiresAuth && !auth.user) {
//         next({ name: "login" })
//     } else {
//         next()
//     }
// })



const app = createApp(App)
app.use(createPinia())
app.use(router)

// Restore user session if token exists
const auth = useAuthStore()
// if (auth.token) {
//     auth.fetchUser().catch(() => {
//         auth.logout()
//         router.push('/login')
//     })
// }

Object.entries(directives).forEach(([name, directive]) => {
    app.directive(name, directive)
})

app.mount('#app')


// const app = createApp(App)
// app.use(createPinia())

// Register all directives in one loop


// app.use(router)
// app.mount('#app')
