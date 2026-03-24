import { createRouter, createWebHistory } from 'vue-router'

import Home from '../pages/Home.vue'
import Register from "../pages/Register.vue"
import Login from "../pages/Login.vue"
import Profile from "../pages/Profile.vue"

const routes =[
  {path: "/", component: Home},
  {path: "/Register", component: Register},
  {path: "/Login", component: Login},
  {path: "/User/:id/Profile", component: Profile}
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router