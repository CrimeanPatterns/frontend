import { reauthInterceptor } from './Reauth/ReauthInterceptor';
import axiosInstance from '@Bem/ts/service/axios';

reauthInterceptor(axiosInstance);

export const axios = axiosInstance;
