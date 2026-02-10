(function (React) {
    if (React.addons && React.addons.Spinner) {
        return React.addons.Spinner;
    }
    React.addons = React.addons || {};
    var Spinner = React.addons.Spinner = React.createClass({
        displayName: 'Spinner',
        getDefaultProps: function () {
            return {
                color: 'gray'
            };
        },
        componentDidMount: function () {
            var spinner = this.getDOMNode(), _this = this;
            //TODO: write with requestAnimationFrame, without svg animation
            setTimeout(function () {
                //fix svg onload
                spinner.innerHTML = _this.getSvgString(_this.props.color);
            }, 0);
        },
        getSvgString: function(color){
            return '<svg viewBox="0 0 64 64" class="' + color + '">'+
                '<g>'+
                    '<defs>'+
                        '<linearGradient id="sGD" gradientUnits="userSpaceOnUse" x1="55" y1="46" x2="2" y2="46">'+
                            '<stop offset="0.1" class="stop1"></stop>'+
                            '<stop offset="1" class="stop2"></stop>'+
                        '</linearGradient>'+
                    '</defs>'+
                    '<g stroke-width="4" stroke-linecap="round" fill="none" transform="rotate(324.592 32 32)">'+
                        '<path stroke="none" d="M4,32 c0,15,12,28,28,28c8,0,16-4,21-9"></path>'+
                        '<path stroke="none" d="M60,32 C60,16,47.464,4,32,4S4,16,4,32"></path>'+
                        '<animateTransform values="0,32,32;360,32,32" attributeName="transform" type="rotate" repeatCount="indefinite" dur="750ms"></animateTransform>'+
                    '</g>'+
                '</g>'+
            '</svg>'
        },
        render: function () {
            return React.createElement(
                'div',
                {className: this.props.className || 'spinner-block'}
            );
        }
    });
    Spinner.setDefaultLoader = function (loader) {
        Spinner._defaultLoader = loader;
    };
    return Spinner;
})(React);