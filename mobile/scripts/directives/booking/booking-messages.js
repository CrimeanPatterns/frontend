(function (window, document, angular, React) {
    function classNames() {

        var classes = '';

        for (var i = 0; i < arguments.length; i++) {
            var arg = arguments[i];
            if (!arg) continue;

            var argType = typeof arg;

            if ('string' === argType || 'number' === argType) {
                classes += ' ' + arg;

            } else if (Array.isArray(arg)) {
                classes += ' ' + classNames.apply(null, arg);

            } else if ('object' === argType) {
                for (var key in arg) {
                    if (arg.hasOwnProperty(key) && arg[key]) {
                        classes += ' ' + key;
                    }
                }
            }
        }

        return classes.substr(1);
    }

    var messagesProcessing = false;

    angular.module('AwardWalletMobile').service('BookingMessages', ['$filter', 'EventEmitter', '$cordovaKeyboard', function ($filter, EventEmitter, $cordovaKeyboard) {

        var DefaultMessageMixin = {
            getDefaultProps: function () {
                return {
                    message: []
                };
            }
        };
        var BookingMessageInbox = React.createClass({
            displayName: 'BookingMessageInbox',
            mixins: [DefaultMessageMixin],
            handleLinkClick: function (e) {
                var element = e.target;
                if (element.parentElement && element.parentElement.nodeName == 'A') {
                    element = element.parentElement;
                }
                if (element.nodeName == 'A' && element.href) {
                    if (element.protocol && ['tel:', 'sms:', 'geo:', 'mailto:'].indexOf(element.protocol) > -1) {
                        window.open(element.href, '_system');
                    } else {
                        $cordovaKeyboard.hideAccessoryBar(false);
                        var ref = window.open(element.href, '_blank');

                        ref.addEventListener('close', function () {
                            $cordovaKeyboard.hideAccessoryBar(true);
                        });
                    }
                }
                e.preventDefault();
            },
            componentDidMount: function () {
                this.element = this.getDOMNode();
                if (platform && platform.cordova)
                    this.element.addEventListener('click', this.handleLinkClick, false);
                var innerElement = this.element.querySelector('.message-text');
                if (innerElement && innerElement.scrollWidth && innerElement.scrollWidth > innerElement.clientWidth) {
                    innerElement.setAttribute('sliding-menu-ignore', true);
                }
            },
            componentDidUnmount: function () {
                if (platform && platform.cordova)
                    this.element.removeEventListener('click', this.handleLinkClick);
            },
            render: function () {
                var message = this.props.message;
                var classes = classNames({
                    message: true,
                    inbox: true,
                    system: message.type != 'userText',
                    'new': message.readed == false
                });
                return React.createElement(
                    'div',
                    {className: classes, ref: "booking_message_" + message.id},
                    React.createElement(
                        'div',
                        {className: 'message-top'},
                        React.createElement(
                            'div',
                            {className: 'author'},
                            React.createElement(
                                'div',
                                {className: 'author-logo'},
                                React.createElement('img', {src: message.avatar})
                            ),
                            React.createElement(
                                'p',
                                null,
                                message.author
                            )
                        ),
                        React.createElement(BookingMessageDate, {
                            createDate: message['date'],
                            updateDate: message['lastUpdate']
                        })
                    ),
                    React.createElement(
                        'div',
                        {className: 'message-blk'},
                        React.createElement('div', {
                            className: 'message-text',
                            dangerouslySetInnerHTML: {__html: message.body}
                        })
                    )
                );
            }
        });
        var BookingMessageOutbox = React.createClass({
            displayName: 'BookingMessageOutbox',
            mixins: [DefaultMessageMixin],
            getInitialState: function () {
                return {showActions: false, processing: false};
            },
            handleClick: function (e) {
                var element = e.target,
                    actionPopup = this.element.getElementsByClassName('popup-message');
                if (element.parentElement && element.parentElement.nodeName == 'A') {
                    element = element.parentElement;
                }
                if (element.nodeName == 'A' && element.href && (actionPopup.length == 0 || !actionPopup[0].contains(element)) && !messagesProcessing) {
                    if (element.protocol && ['tel:', 'sms:', 'geo:', 'mailto:'].indexOf(element.protocol) > -1) {
                        window.open(element.href, '_system');
                    } else {
                        $cordovaKeyboard.hideAccessoryBar(false);

                        var ref = window.open(element.href, '_blank');

                        ref.addEventListener('close', function () {
                            $cordovaKeyboard.hideAccessoryBar(true);
                        });
                    }
                }
                e.preventDefault();
            },
            componentDidMount: function () {
                this.element = this.getDOMNode();
                if (platform && platform.cordova)
                    this.element.addEventListener('click', this.handleClick, false);
            },
            componentWillUnmount: function () {
                document.removeEventListener('click', this.handleClosePopupClickOutside, false);
                document.removeEventListener('click', this.handleCancelEditClickOutside, false);
            },
            componentDidUnmount: function () {
                if (platform && platform.cordova)
                    this.element.removeEventListener('click', this.handleClick, false);
            },
            componentWillUpdate: function (nextProps, nextState) {
                if (this.state.showActions === nextState.showActions) return;
                if (nextState.showActions) {
                    document.addEventListener('click', this.handleClosePopupClickOutside, false);
                } else {
                    document.removeEventListener('click', this.handleClosePopupClickOutside, false);
                }
            },
            handleCancelEditClickOutside: function (e) {
                var form = document.getElementsByClassName('send-message');
                if (form.length == 0 || !form[0].contains(e.target)) {
                    EventEmitter.dispatch("editMessage", {
                        cancel: true
                    });
                }
            },
            handleClosePopupClickOutside: function (e) {
                this.setState({showActions: false});
            },
            isVisibleActions: function () {
                return (this.props.message.canEdit || this.props.message.canDelete) && this.props.message.id > 0;
            },
            openActions: function (e) {
                if (!this.isVisibleActions() || this.state.processing) return;
                e.preventDefault();
                this.setState({showActions: true});
            },
            editMessage: function (e) {
                e.preventDefault();
                if (messagesProcessing) return;
                messagesProcessing = true;
                var _this = this;
                EventEmitter.dispatch("editMessage", {
                    messageId: _this.props.message.id,
                    message: _this.prepareMessage(_this.props.message.body),
                    onCancel: function () {
                        _this.cancelEdit();
                    },
                    onSubmit: function () {
                        _this.setState({processing: true});
                    },
                    onSuccess: function () {
                        _this.cancelEdit();
                    },
                    onError: function () {
                        _this.setState({processing: false});
                    }
                });
                document.addEventListener('click', this.handleCancelEditClickOutside, false);
            },
            cancelEdit: function () {
                this.setState({processing: false});
                messagesProcessing = false;
                document.removeEventListener('click', this.handleCancelEditClickOutside, false);
            },
            prepareMessage: function (text) {
                text = text.replace(/(\<br(\s*)?\/?\>)|(\<\s*a[^>]*\>)|(\<\s*\/\s*a\s*\>)/g, "");
                return angular.element('<textarea />').html(text).text();
            },
            deleteMessage: function (e) {
                e.preventDefault();
                if (messagesProcessing) return;
                messagesProcessing = true;
                var _this = this;
                this.setState({
                    processing: true
                });
                EventEmitter.dispatch("deleteMessage", {
                    messageId: _this.props.message.id,
                    onSuccess: function () {
                        messagesProcessing = false;
                    },
                    onError: function () {
                        _this.setState({processing: false});
                        messagesProcessing = false;
                    }
                });
            },
            render: function () {
                var message = this.props.message;
                var classes = classNames({message: true, send: true, system: message.type != 'userText'});
                var actionsClasses = classNames({'popup-message': true, show: this.state.showActions});
                var spinnerClasses = classNames({'spinner-block': true, 'hide': !this.state.processing});
                var messageClasses = classNames({'message-text': true, 'hide': this.state.processing});
                var actions = {
                    edit: Translator.trans(/** @Desc("Edit this message") */'edit-message', {}, 'booking'),
                    delete: Translator.trans(/** @Desc("Delete this message") */'delete-message', {}, 'booking')
                };
                var Spinner = React.addons.Spinner;
                return React.createElement(
                    'div',
                    {className: classes, ref: "booking_message_" + message.id},
                    React.createElement(
                        'div',
                        {className: actionsClasses},
                        React.createElement(
                            'ul',
                            null,
                            message.canEdit ? React.createElement(
                                'li',
                                null,
                                React.createElement(
                                    'a',
                                    {href: null, onClick: this.editMessage},
                                    React.createElement('i', {className: 'icon-edit'}),
                                    React.createElement(
                                        'span',
                                        null,
                                        actions.edit
                                    )
                                )
                            ) : null,
                            message.canDelete ? React.createElement(
                                'li',
                                null,
                                React.createElement(
                                    'a',
                                    {href: null, onClick: this.deleteMessage},
                                    React.createElement('i', {className: 'icon-delete'}),
                                    React.createElement(
                                        'span',
                                        null,
                                        actions.delete
                                    )
                                )
                            ) : null
                        )
                    ),
                    React.createElement(
                        'div',
                        {className: 'message-top'},
                        React.createElement(
                            'div',
                            {className: 'row'},
                            React.createElement(BookingMessageDate, {
                                createDate: message['date'],
                                updateDate: message['lastUpdate']
                            }),
                            React.createElement(
                                'div',
                                {className: 'author', onClick: this.openActions},
                                React.createElement(
                                    'div',
                                    null,
                                    message.author,
                                    ' ',
                                    this.isVisibleActions() ? React.createElement('i', {className: 'icon-arrow-down'}) : null
                                )
                            )
                        )
                    ),
                    React.createElement(
                        'div',
                        {className: 'message-blk'},
                        React.createElement(Spinner, {color: 'white', className: spinnerClasses}),
                        React.createElement('div', {
                            className: messageClasses,
                            dangerouslySetInnerHTML: {__html: message.body}
                        })
                    )
                );
            }
        });
        var BookingMessageDate = React.createClass({
            displayName: 'BookingMessageDate',
            getDefaultProps: function getDefaultProps() {
                return {
                    createDate: null,
                    updateDate: null
                };
            },
            render: function render() {
                var TimeAgo = React.addons.TimeAgo,
                    messageUpdated = !!this.props.updateDate,
                    date = messageUpdated ? this.props.updateDate : this.props.createDate;
                return React.createElement(
                    "div",
                    {className: "date"},
                    messageUpdated ? React.createElement("i", {className: "icon-refresh-g"}) : null,
                    React.createElement(
                        "span",
                        {className: "bold"},
                        React.createElement(TimeAgo, {date: date.ts * 1000})
                    ),
                    typeof date.fmt == 'string' ? "(" + date.fmt + ")" : "(" + $filter('date')($filter('fmt')(date.fmt), 'mediumDate') + ")"
                );
            }
        });

        return React.createClass({
            displayName: 'BookingMessages',
            unreadMessages: [],
            newMessages: [],
            getDefaultProps: function () {
                return {
                    needMore: false,
                    requestId: null
                };
            },
            getInitialState: function () {
                this.rafRequestId = null;
                this.scrollTop = 0;
                this.scrollHeight = undefined;
                return {
                    isInfiniteLoading: false
                };
            },
            getScrollParent: function getScrollParent() {
                var el = this.getDOMNode();
                var overflowKey = 'overflowY';
                while (el = el.parentElement) {
                    var overflow = window.getComputedStyle(el)[overflowKey];
                    if (overflow === 'auto' || overflow === 'scroll') return el;
                }
                return window;
            },
            pollScroll: function () {
                var scrollDomNode = this.scrollableDomEl,
                    domNode = this.element,
                    _this = this;
                if (scrollDomNode.scrollTop !== this.scrollTop) {
                    if (this.shouldTriggerLoad(scrollDomNode, domNode)) {
                        this.setState({isInfiniteLoading: true});
                        var p = this.props.onInfiniteLoad();
                        p.then(function () {
                            _this.setState({isInfiniteLoading: false});
                        });
                    }
                    //this.updateScrollTop();
                }
                this.rafRequestId = window.requestAnimationFrame(this.pollScroll);
            },
            scrollToUnread: function () {
                if (this.unreadMessages.length > 0 && this.refs.hasOwnProperty(this.unreadMessages[0])) {
                    this.scrollIntoView(this.refs[this.unreadMessages[0]].getDOMNode());
                } else {
                    this.scrollToBottom();
                }
            },
            scrollToBottom: function () {
                if (this.refs.hasOwnProperty('bottom')) {
                    this.scrollIntoView(this.refs['bottom'].getDOMNode());
                }
            },
            scrollIntoView: function (element) {
                setTimeout(function () {
                    element.scrollIntoView();
                }, 0);
            },
            isPassedThreshold: function (scrollTop, scrollHeight, clientHeight, height) {
                return scrollHeight - (clientHeight + scrollTop) >= (scrollHeight - (scrollHeight - height)) * .7;
            },
            shouldTriggerLoad: function (scrollDomNode, domNode) {
                var passedThreshold = this.isPassedThreshold(scrollDomNode.scrollTop, scrollDomNode.scrollHeight, scrollDomNode.clientHeight, domNode.clientHeight);
                return passedThreshold && !this.state.isInfiniteLoading;
            },
            componentDidMount: function () {
                var _this = this;
                this.scrollableDomEl = this.getScrollParent();
                this.element = this.getDOMNode();
                this.scrollToUnread();
                this.scrollTop = this.scrollableDomEl.scrollHeight - this.scrollableDomEl.clientHeight;
                setTimeout(function () {
                    _this.rafRequestId = window.requestAnimationFrame(_this.pollScroll);
                }, 300);
                EventEmitter.subscribe("deleteMessage", this.deleteMessage);
            },
            componentWillReceiveProps: function () {
                this.unreadMessages = [];
            },
            componentWillUnmount: function () {
                window.cancelAnimationFrame(this.rafRequestId);
                EventEmitter.unsubscribe("deleteMessage", this.deleteMessage);
            },
            componentDidUpdate: function () {
                var message = this.props.messages[this.props.messages.length - 1];
                if (message && message.new && this.newMessages.indexOf(message.id) == -1) {
                    this.newMessages.push(message.id);
                    this.scrollToBottom();
                } else {
                    this.updateScrollTop();
                }
            },
            updateScrollTop: function () {
                var scrollableDomEl = this.scrollableDomEl;

                var newScrollTop = scrollableDomEl.scrollTop + (scrollableDomEl.scrollHeight - (this.scrollHeight || 0));

                if (newScrollTop !== scrollableDomEl.scrollTop) {
                    scrollableDomEl.scrollTop = newScrollTop;
                }

                this.scrollTop = scrollableDomEl.scrollTop;
                this.scrollHeight = scrollableDomEl.scrollHeight;
            },
            deleteMessage: function (data) {
                this.props.onDeleteMessage({messageId: data.messageId}).then(data.onSuccess, data.onError);
            },
            render: function () {
                var messagesTrans = Translator.trans('booking.userscomm', {}, 'booking');
                return React.createElement(
                    'div',
                    {className: 'messages'},
                    React.createElement(
                        'div',
                        {className: 'block-title'},
                        React.createElement('i', {className: 'icon-arrow-down'}),
                        React.createElement(
                            'h3',
                            null,
                            messagesTrans
                        )
                    ),
                    this.renderMessages(this.props.messages),
                    React.createElement('div', {ref: 'bottom'})
                );
            },
            renderMessages: function (messages) {
                return messages.map(this.renderMessage);
            },
            renderMessage: function (message) {
                var messageId = 'booking_message_' + message.id;
                if (message.readed == false) {
                    this.unreadMessages.push(messageId);
                }
                if (message.box == 'in') {
                    return React.createElement(BookingMessageInbox, {message: message, key: messageId, ref: messageId});
                }
                return React.createElement(BookingMessageOutbox, {message: message, key: messageId, ref: messageId});
            }
        });

    }]);

    angular.module('AwardWalletMobile').directive('bookingMessages', ['BookingMessages', function (BookingMessages) {
        return {
            restrict: 'E',
            scope: {
                messages: '=',
                needMore: '=',
                requestId: '=',
                onInfiniteLoad: '&',
                onDeleteMessage: '&'
            },
            link: function (scope, element, attrs) {
                if (scope.messages) {
                    React.render(React.createElement(BookingMessages, {
                        messages: scope.messages,
                        needMore: scope.needMore,
                        requestId: scope.requestId,
                        onInfiniteLoad: scope.onInfiniteLoad,
                        onDeleteMessage: scope.onDeleteMessage
                    }), element[0]);
                    scope.$on('$destroy', function () {
                        React.unmountComponentAtNode(element[0]);
                    });
                }
                scope.$on('booking:chat:' + scope.requestId + ':update', function (event, messages) {
                    React.render(React.createElement(BookingMessages, {
                        messages: messages,
                        needMore: true,
                        requestId: scope.requestId,
                        onInfiniteLoad: scope.onInfiniteLoad,
                        onDeleteMessage: scope.onDeleteMessage
                    }), element[0]);
                });
            }
        };
    }]);

    angular.module('AwardWalletMobile').service('BookingMessagesForm', ['EventEmitter', '$cordovaKeyboard', function (EventEmitter, $cordovaKeyboard) {

        return React.createClass({
            displayName: 'BookingMessagesForm',
            getDefaultProps: function () {
                return {
                    message: null
                };
            },
            getInitialState: function () {
                return {
                    disabled: this.props.message ? false : true,
                    message: this.props.message,
                    editContext: {},
                    loading: false
                };
            },
            resizeTextarea: function (domNode) {
                if (domNode) {
                    domNode.style.cssText = 'height:1px;';
                    domNode.style.cssText = 'height:' + (domNode.scrollHeight < 30 ? 30 : domNode.scrollHeight) + 'px';
                }
            },
            handleChange: function (event) {
                this.resizeTextarea(event.target);
                this.setState({
                    message: event.target.value,
                    disabled: !(event.target.value.trim().length > 0)
                });
            },
            hideKeyboardAccessoryBar: function () {
                $cordovaKeyboard.hideAccessoryBar(true);
            },
            showKeyboardAccessoryBar: function () {
                $cordovaKeyboard.hideAccessoryBar(false);
            },
            componentDidMount: function () {
                this.textarea = this.refs.textarea.getDOMNode();
                this.resizeTextarea(this.textarea);
                EventEmitter.subscribe("editMessage", this.handleEditMessage);
                if (platform && platform.cordova) {
                    this.hideKeyboardAccessoryBar();
                    this.textarea.addEventListener('blur', this.showKeyboardAccessoryBar, false);
                    this.textarea.addEventListener('touchstart', this.hideKeyboardAccessoryBar, false);
                }
            },
            componentWillUnmount: function () {
                EventEmitter.unsubscribe("editMessage", this.handleEditMessage);
                messagesProcessing = false;
                if (platform && platform.cordova) {
                    this.showKeyboardAccessoryBar();
                    this.textarea.removeEventListener('blur', this.showKeyboardAccessoryBar, false);
                    this.textarea.removeEventListener('touchstart', this.hideKeyboardAccessoryBar, false);
                }
            },
            componentWillReceiveProps: function (props) {
                this.setState({
                    message: props.message,
                    disabled: !(props.message.length > 0)
                });
                this.resizeTextarea(this.textarea);
            },
            componentDidUpdate: function () {
                this.resizeTextarea(this.textarea);
            },
            handleEditMessage: function (data) {
                if (data.hasOwnProperty("cancel")) {
                    this.closeEditMode();
                    return;
                }

                var _this = this;
                this.setState({
                    disabled: false,
                    message: data.message,
                    editContext: data
                }, function () {
                    if (platform && platform.cordova) {
                        _this.hideKeyboardAccessoryBar();
                    }
                    setTimeout(function () {
                        _this.textarea.focus();
                    }, 0)
                });
            },
            submit: function (event) {
                var _this = this,
                    context;
                event.preventDefault();

                if (this.state.message && this.state.message.trim().length > 0 && !this.state.disabled) {
                    if (_this.isEditMode()) {
                        this.setState({
                            disabled: true
                        });
                        context = this.state.editContext;
                        this.state.editContext.onSubmit();
                        this.props.onUpdate(this.state.editContext.messageId, this.state.message).then(function () {
                            if (context !== _this.state.editContext) return;
                            _this.state.editContext.onSuccess();
                            _this.cleanForm();
                        }, function () {
                            if (context !== _this.state.editContext) return;
                            _this.state.editContext.onError();
                            _this.setState({
                                disabled: false
                            });
                        });
                    } else {
                        this.setState({
                            disabled: true,
                            loading: true
                        });
                        messagesProcessing = true;
                        this.props.onAdd(this.state.message).then(function () {
                            messagesProcessing = false;
                            _this.cleanForm();
                        }, function () {
                            messagesProcessing = false;
                            _this.setState({
                                disabled: false,
                                loading: false
                            });
                        });
                    }
                }
            },
            isEditMode: function () {
                return Object.getOwnPropertyNames(this.state.editContext).length > 0;
            },
            closeEditMode: function () {
                if (!this.isEditMode()) return;
                this.state.editContext.onCancel();
                this.cleanForm();
            },
            cleanForm: function () {
                if (platform && platform.cordova) {
                    this.showKeyboardAccessoryBar();
                }
                this.setState({
                    disabled: true,
                    message: '',
                    editContext: {},
                    loading: false
                });
            },
            render: function () {
                var translations = {
                    send: Translator.trans('send.btn', {}, 'booking'),
                    placeholder: Translator.trans(/** @Desc("Text Message") */'textarea.placeholder', {}, 'booking')
                };
                var Spinner = React.addons.Spinner;
                return React.createElement(
                    'form',
                    {className: 'send-message', onSubmit: this.submit},
                    React.createElement(
                        'button',
                        {type: 'submit', disabled: this.state.disabled},
                        this.state.loading ? React.createElement(Spinner, {color: 'gray'}) : translations.send
                    ),
                    this.isEditMode() ? React.createElement(
                        'a',
                        {onClick: this.closeEditMode, className: 'reset'},
                        React.createElement('i', {className: 'icon-clear-d'})
                    ) : null,
                    React.createElement(
                        'div',
                        {className: 'input'},
                        React.createElement('textarea', {
                            placeholder: translations.placeholder,
                            ref: 'textarea',
                            onChange: this.handleChange,
                            value: this.state.message,
                            disabled: this.state.loading
                        })
                    )
                );
            }
        });

    }]);

    angular.module('AwardWalletMobile').directive('bookingMessagesForm', ['BookingMessagesForm', function (BookingMessagesForm) {
        return {
            restrict: 'E',
            scope: {
                onAdd: '&',
                onUpdate: '&',
                message: '='
            },
            link: function (scope, element, attrs) {
                React.render(React.createElement(BookingMessagesForm, {
                    onAdd: function (message) {
                        return scope.onAdd({message: message});
                    },
                    onUpdate: function (messageId, message) {
                        return scope.onUpdate({messageId: messageId, message: message});
                    },
                    message: scope.message
                }), element[0]);
                scope.$on('$destroy', function () {
                    React.unmountComponentAtNode(element[0]);
                });
            }
        };
    }]);

})(window, document, angular, React);