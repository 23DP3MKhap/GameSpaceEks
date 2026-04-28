import { createRouter, createWebHistory } from 'vue-router'

import Home from '../pages/Home.vue'
import Register from "../pages/Register.vue"
import Login from "../pages/Login.vue"
import Profile from "../pages/Profile.vue"
import Catalog from "../pages/Catalog.vue"
import Admin from "../pages/Admin.vue"
import About from "../pages/About.vue"

const routes =[
    {path: "/", component: Home},
    {path: "/Register", component: Register},
    {path: "/Login", component: Login},
    {path: "/User/:id/Profile", component: Profile},
    {path: "/Catalog", component: Catalog, name: "catalog", meta: { hideHeader: true } },
    {path: "/Admin", component: Admin},
    {path: "/About", component: About}
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router