import 'react-toastify/dist/ReactToastify.css';
import { Id, ToastContent, ToastOptions, toast as toastifyToast } from 'react-toastify';

interface CustomToastOptions<T = unknown> extends ToastOptions<T> {
    recharge?: number;
}

type KeyType = Id;
type ValueType = ReturnType<typeof setTimeout>;
const timersId: Map<KeyType, ValueType> = new Map();

export function toast<TData = unknown>(content: ToastContent<TData>, options?: CustomToastOptions<TData>) {
    const isToastWithRecharge = options?.toastId && options.recharge;

    if (isToastWithRecharge && options.toastId && timersId.get(options.toastId)) {
        return;
    }

    toastifyToast(content, options);
    if (isToastWithRecharge) {
        timersId.set(
            options.toastId || '',
            setTimeout(() => {
                timersId.delete(options.toastId || '');
            }, options.recharge),
        );
    }
}
