import Axios from 'axios'

const axios = Axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
    },
})

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response.status === 401) {
            auth.user = null
        }
        return Promise.reject(error)
    }
)

export default axios