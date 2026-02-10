export function useInitialData() {
    const userInfo = document.getElementById('content')?.dataset['userInfo'];
    const canCancel = document.getElementById('content')?.dataset['canCancel'];
    const manualCancellation = document.getElementById('content')?.dataset['manual'];
    const cancelButtonLabel = document.getElementById('content')?.dataset['cancelButtonLabel'];
    const isAT201 = document.getElementById('content')?.dataset['isAt201'] === 'true' ? true : false;
    const confirmationTitle = document.getElementById('content')?.dataset['confirmationTitle'];
    const confirmationBody = document.getElementById('content')?.dataset['confirmationBody'];
    const confirmationButtonNo = document.getElementById('content')?.dataset['confirmationButtonNo'];
    const confirmationButtonYes = document.getElementById('content')?.dataset['confirmationButtonYes'];

    if (!userInfo) {
        throw new Error("Initial data didn't load correctly");
    }

    return {
        userInfo,
        canCancel,
        manualCancellation,
        cancelButtonLabel,
        isAT201,
        confirmationTitle,
        confirmationBody,
        confirmationButtonNo,
        confirmationButtonYes
    };
}
