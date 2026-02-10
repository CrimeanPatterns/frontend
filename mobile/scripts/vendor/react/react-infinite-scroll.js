(function (React) {
    function topPosition(domElt) {
        if (!domElt) {
            return 0;
        }
        return domElt.offsetTop + topPosition(domElt.offsetParent);
    }
    if (React.addons && React.addons.InfiniteScroll) {
        return React.addons.InfiniteScroll;
    }
    React.addons = React.addons || {};
    var InfiniteScroll = React.addons.InfiniteScroll = React.createClass({
        displayName: 'InfiniteScroll',
        getDefaultProps: function () {
            return {
                pageStart: 0,
                hasMore: false,
                loadMore: function () {
                },
                threshold: 250,
                parent: null
            };
        },
        componentDidMount: function () {
            this.pageLoaded = this.props.pageStart;
            this.scrollParent = this.getScrollParent();
            this.attachScrollListener();
        },
        componentDidUpdate: function () {
            this.attachScrollListener();
        },
        render: function () {
            var props = this.props;
            return React.DOM.div(null, props.children, props.hasMore && (props.loader || InfiniteScroll._defaultLoader));
        },
        getScrollParent: function getScrollParent() {
            var el = this.getDOMNode();
            var overflowKey = 'overflowY';
            while (el = el.parentElement) {
                var overflow = window.getComputedStyle(el)[overflowKey];
                if (overflow === 'auto' || overflow === 'scroll') return el;
            }
            if(this.props.parent){
                el = document.querySelector(this.props.parent);
                if(el){
                    return el;
                }
            }
            return window;
        },
        scrollListener: function () {
            var el = this.scrollParent;
            var remaining = el.scrollHeight - (el.clientHeight + el.scrollTop);
            if (remaining < Number(this.props.threshold)) {
                this.detachScrollListener();
                // call loadMore after detachScrollListener to allow
                // for non-async loadMore functions
                this.props.loadMore(this.pageLoaded += 1);
            }
        },
        attachScrollListener: function () {
            if (!this.props.hasMore) {
                return;
            }
            this.scrollParent.addEventListener('scroll', this.scrollListener);
        },
        detachScrollListener: function () {
            this.scrollParent.removeEventListener('scroll', this.scrollListener);
        },
        componentWillUnmount: function () {
            this.detachScrollListener();
        }
    });
    InfiniteScroll.setDefaultLoader = function (loader) {
        InfiniteScroll._defaultLoader = loader;
    };
    return InfiniteScroll;
})(React);