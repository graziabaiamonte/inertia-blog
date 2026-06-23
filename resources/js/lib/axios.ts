import axiosLib from 'axios';

const axios = axiosLib.create({
    baseURL: '/',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export default axios;
