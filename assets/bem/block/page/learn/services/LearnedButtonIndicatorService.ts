export class LearnedButtonIndicatorService {
    private static instance: LearnedButtonIndicatorService;
    private learnedPostIndicatorElement: HTMLElement | null = null;

    constructor() {
        this.learnedPostIndicatorElement = document.querySelector('[data-read-post-count]');
    }

    public static getInstance(): LearnedButtonIndicatorService {
        if (!LearnedButtonIndicatorService.instance) {
            LearnedButtonIndicatorService.instance = new LearnedButtonIndicatorService();
        }
        return LearnedButtonIndicatorService.instance;
    }

    public updateIndicator(isLearned: boolean): void {
        if (!this.learnedPostIndicatorElement) {
            this.learnedPostIndicatorElement = document.querySelector('[data-read-post-count]');
        }

        if (!this.learnedPostIndicatorElement) {
            return;
        }

        let learnedPostCount = Number(this.learnedPostIndicatorElement.getAttribute('data-read-post-count'));

        if (!isNaN(learnedPostCount)) {
            if (isLearned) {
                learnedPostCount = learnedPostCount - 1;
            } else {
                learnedPostCount = learnedPostCount + 1;
            }
        }

        this.learnedPostIndicatorElement.textContent = learnedPostCount <= 99 ? String(learnedPostCount) : '99+';
        this.learnedPostIndicatorElement.setAttribute('data-read-post-count', String(learnedPostCount));

        if (learnedPostCount <= 0) {
            this.learnedPostIndicatorElement.classList.remove('page-learn__header-quick-access-indicator--visible');
        } else {
            this.learnedPostIndicatorElement.classList.add('page-learn__header-quick-access-indicator--visible');
        }
    }

    public getCurrentCount(): number {
        if (!this.learnedPostIndicatorElement) {
            this.learnedPostIndicatorElement = document.querySelector('[data-read-post-count]');
        }

        if (!this.learnedPostIndicatorElement) {
            return 0;
        }

        const count = Number(this.learnedPostIndicatorElement.getAttribute('data-read-post-count'));
        return isNaN(count) ? 0 : count;
    }

    public setCount(count: number): void {
        if (!this.learnedPostIndicatorElement) {
            this.learnedPostIndicatorElement = document.querySelector('[data-read-post-count]');
        }

        if (!this.learnedPostIndicatorElement) {
            return;
        }

        this.learnedPostIndicatorElement.textContent = count <= 99 ? String(count) : '99+';
        this.learnedPostIndicatorElement.setAttribute('data-read-post-count', String(count));

        if (count <= 0) {
            this.learnedPostIndicatorElement.classList.remove('page-learn__header-quick-access-indicator--visible');
        } else {
            this.learnedPostIndicatorElement.classList.add('page-learn__header-quick-access-indicator--visible');
        }
    }
}

export const learnedButtonIndicatorService = LearnedButtonIndicatorService.getInstance();
