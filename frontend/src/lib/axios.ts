import axios from 'axios';
import { clearAuthToken } from './auth';
import { ensureCsrfCookie } from './csrf';

const PUBLIC_AUTH_PATHS = new Set([
    '/',
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/verify-email',
    '/auth/callback',
]);

function isPublicAuthPath(pathname: string) {
    return PUBLIC_AUTH_PATHS.has(pathname) || pathname.startsWith('/contracts/sign/');
}

const axiosInstance = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_URL,
    withCredentials: true,
    withXSRFToken: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

axiosInstance.interceptors.request.use(
    async (config) => {
        const method = config.method?.toLowerCase();
        const isUnsafeMethod = ['post', 'put', 'patch', 'delete'].includes(method ?? '');

        if (typeof window !== 'undefined' && isUnsafeMethod) {
            await ensureCsrfCookie();
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Interceptor to handle responses
axiosInstance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            if (typeof window !== 'undefined') {
                clearAuthToken();
                if (!isPublicAuthPath(window.location.pathname)) {
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);

export default axiosInstance;
