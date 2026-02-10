define(['jquery-boot'], function ($) {
    return class ProgressBar {
        complete;
        percentIndex = 0;
        processUpdate;
        progressSelector;
        progressTextSelector;
        timeout;

        constructor(progressSelector, progressTextSelector) {
            this.progressSelector = progressSelector;
            this.progressTextSelector = progressTextSelector;

            this.tick = this.tick.bind(this);

            this.setPercent(0);
            $(this.progressSelector).css({'transition': 'width .3s linear'});
        }

        setPercent(percent) {
            $(this.progressTextSelector).text(percent + '%');
            $(this.progressSelector).css('width', percent + '%');
        }

        finish() {
            clearTimeout(this.processUpdate);
            $(this.progressSelector).css({'transition': 'width 1s ease'});
            this.setPercent(100);
            if (typeof this.complete === 'function') {
                setTimeout(this.complete, 1000);
            }
        }

        tick() {
            if (++this.percentIndex > 100) {
                this.finish();
                return;
            }

            this.setPercent(this.percentIndex);
            this.processUpdate = setTimeout(this.tick.bind(this), this.timeout);
        }

        animate(duration, complete) {
            clearTimeout(this.processUpdate);
            this.percentIndex = 0;
            this.timeout = parseInt(duration / 100, 10);
            this.complete = complete;
            this.tick();
        }
    }
});