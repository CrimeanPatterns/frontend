import { Router } from '@Services/Router';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';
import { useMutation } from '@tanstack/react-query';

export function useCancelSubscription(onSuccess: () => void) {
    const { mutate: cancelSubscription, isPending } = useMutation({
        mutationFn: cancelSubscriptionRequest,
        onSuccess(data) {
            if (isSuccessfulResponse(data)) {
                onSuccess();
                return;
            }

            if (isResponseWithError(data)) {
                toast(data.error, { type: 'error', toastId: 'cancelSubscription' });
            }
        },
        onError(error) {
            toast(error.message, { type: 'error', toastId: 'cancelSubscription' });
        },
    });

    return { cancelSubscription, isPending };
}

type CancelSubscriptionResponseSuccess = {
    success: true;
};
type CancelSubscriptionResponseFailed = {
    error: string;
};

function isResponseWithError(response: object): response is CancelSubscriptionResponseFailed {
    return 'error' in response;
}

function isSuccessfulResponse(response: object): response is CancelSubscriptionResponseSuccess {
    return 'success' in response;
}

async function cancelSubscriptionRequest(): Promise<
    CancelSubscriptionResponseSuccess | CancelSubscriptionResponseFailed
> {
    return (
        await axios.delete<CancelSubscriptionResponseSuccess | CancelSubscriptionResponseFailed>(
            Router.generate('aw_user_subscription_cancel'),
        )
    ).data;
}
