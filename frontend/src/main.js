import { createApp } from 'vue'
import App from './App.vue'
import router from "./router/index.js"
import vuetify from './plugins/vuetify'
import axios from './plugins/axios'
import { auth } from './plugins/userinfo'

import './styles/main.css'

axios.get('/api/user')
  .then(response => { auth.user = response.data })
  .catch(() => {})
  .finally(() => {
    createApp(App)
      .use(router)
      .use(vuetify)
      .mount('#app')
  })
