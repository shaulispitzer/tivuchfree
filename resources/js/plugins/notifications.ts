import { useToast } from 'vue-toastification';
import 'vue-toastification/dist/index.css';

import type { AppPageProps } from '@/types';

const toast = useToast();
const toastMethods = {
    error: toast.error,
    info: toast.info,
    success: toast.success,
    warning: toast.warning,
} as const;

type NotificationType = keyof typeof toastMethods;
type PageNotification = {
    body: string;
    type: NotificationType;
};

export const notifications = () => {
    router.on('finish', () => {
        const { notification } = usePage<
            AppPageProps<{ notification?: PageNotification }>
        >().props;

        if (!notification) {
            return;
        }

        (toastMethods[notification.type] ?? toast.info)(notification.body);
    });
};
