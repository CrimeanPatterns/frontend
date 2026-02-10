import { Router } from '@Services/Router';
import { axios } from '@Services/Axios';
import { toast } from '@Utilities/Toast';
import { useMutation } from '@tanstack/react-query';

export const usePrePaymentPay = () => {
    const { mutate: prePaymentPay, isPending } = useMutation({
        mutationFn: prePaymentPayRequest,
        onSuccess(data) {
            if (isSuccessfulResponse(data)) {
                window.location.href = data.redirect;
                return;
            }

            if (isResponseWithError(data)) {
                toast(data.error, { type: 'error', toastId: 'prePaymentPay' });
            }
        },
        onError(error) {
            toast(error.message, { type: 'error', toastId: 'prePaymentPay' });
        },
    });

    return { prePaymentPay, isPending };
};

type PrePaymentResponseSuccess = {
    success: true;
    redirect: string;
};
type PrePaymentResponseFailed = {
    error: string;
};

function isResponseWithError(response: object): response is PrePaymentResponseFailed {
    return 'error' in response;
}

function isSuccessfulResponse(response: object): response is PrePaymentResponseSuccess {
    return 'success' in response;
}

async function prePaymentPayRequest({
    purchaseType,
    refCode,
    hash,
    addSubscription,
}: {
    purchaseType: number;
    refCode: string;
    hash: string;
    addSubscription: boolean;
}) {
    return (
        await axios.post<PrePaymentResponseFailed | PrePaymentResponseSuccess>(
            Router.generate('aw_pre_payment_pay'),
            {
                purchaseType,
                addSubscription: addSubscription ? 1 : undefined,
            },
            {
                params: {
                    ref: refCode,
                    hash,
                },
            },
        )
    ).data;
}
