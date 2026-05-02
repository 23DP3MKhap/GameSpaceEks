import Axios from 'axios'

const axios = Axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        Accept: 'application/json',
    },
})

const token = localStorage.getItem('token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response.status === 401) {
            auth.user = null
            localStorage.removeItem('token')
            delete axios.defaults.headers.common['Authorization']
        }
        return Promise.reject(error)
    }
)

export default axios