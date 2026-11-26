import { createRouter, createWebHistory } from 'vue-router'

import Home from '../pages/Home.vue'
import Register from "../pages/Register.vue"
import Login from "../pages/Login.vue"
import Profile from "../pages/Profile.vue"
import Catalog from "../pages/Catalog.vue"
import Game from "../pages/Game.vue"
import List from "../pages/List.vue"
import Admin from "../pages/Admin.vue"
import About from "../pages/About.vue"
import ForgotPassword from "../pages/ForgotPassword.vue"

const routes =[
    {path: "/", component: Home},
    {path: "/Register", component: Register},
    {path: "/Login", component: Login},
    {path: "/ForgotPassword", component: ForgotPassword},
    {path: "/User/:id/Profile", component: Profile},
    {path: "/Catalog", component: Catalog, name: "catalog", meta: { hideHeader: true } },
    {path: "/Game/:id", component: Game},
    {path: "/List/:id", component: List},
    {path: "/Admin", component: Admin},
    {path: "/About", component: About}
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router