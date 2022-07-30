/*!
 * clipboard.js v1.5.2
 * https://zenorocha.github.io/clipboard.js
 *
 * Licensed MIT © Zeno Rocha
 */
!function(t){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=t();else if("function"==typeof define&&define.amd)define([],t);else{var e;e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,e.Clipboard=t()}}(function(){var t,e,n;return function t(e,n,r){function o(a,c){if(!n[a]){if(!e[a]){var s="function"==typeof require&&require;if(!c&&s)return s(a,!0);if(i)return i(a,!0);var u=new Error("Cannot find module '"+a+"'");throw u.code="MODULE_NOT_FOUND",u}var l=n[a]={exports:{}};e[a][0].call(l.exports,function(t){var n=e[a][1][t];return o(n?n:t)},l,l.exports,t,e,n,r)}return n[a].exports}for(var i="function"==typeof require&&require,a=0;a<r.length;a++)o(r[a]);return o}({1:[function(t,e,n){var r=t("matches-selector");e.exports=function(t,e,n){for(var o=n?t:t.parentNode;o&&o!==document;){if(r(o,e))return o;o=o.parentNode}}},{"matches-selector":2}],2:[function(t,e,n){function r(t,e){if(i)return i.call(t,e);for(var n=t.parentNode.querySelectorAll(e),r=0;r<n.length;++r)if(n[r]==t)return!0;return!1}var o=Element.prototype,i=o.matchesSelector||o.webkitMatchesSelector||o.mozMatchesSelector||o.msMatchesSelector||o.oMatchesSelector;e.exports=r},{}],3:[function(t,e,n){function r(t,e,n,r){var i=o.apply(this,arguments);return t.addEventListener(n,i),{destroy:function(){t.removeEventListener(n,i)}}}function o(t,e,n,r){return function(n){var o=i(n.target,e,!0);o&&(Object.defineProperty(n,"target",{value:o}),r.call(t,n))}}var i=t("closest");e.exports=r},{closest:1}],4:[function(t,e,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.function=function(t){var e=Object.prototype.toString.call(t);return"[object Function]"===e}},{}],5:[function(t,e,n){function r(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!c.string(e))throw new TypeError("Second argument must be a String");if(!c.function(n))throw new TypeError("Third argument must be a Function");if(c.node(t))return o(t,e,n);if(c.nodeList(t))return i(t,e,n);if(c.string(t))return a(t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function o(t,e,n){return t.addEventListener(e,n),{destroy:function(){t.removeEventListener(e,n)}}}function i(t,e,n){return Array.prototype.forEach.call(t,function(t){t.addEventListener(e,n)}),{destroy:function(){Array.prototype.forEach.call(t,function(t){t.removeEventListener(e,n)})}}}function a(t,e,n){return s(document.body,t,e,n)}var c=t("./is"),s=t("delegate");e.exports=r},{"./is":4,delegate:3}],6:[function(t,e,n){function r(t){var e;if("INPUT"===t.nodeName||"TEXTAREA"===t.nodeName)t.select(),e=t.value;else{var n=window.getSelection(),r=document.createRange();r.selectNodeContents(t),n.removeAllRanges(),n.addRange(r),e=n.toString()}return e}e.exports=r},{}],7:[function(t,e,n){function r(){}r.prototype={on:function(t,e,n){var r=this.e||(this.e={});return(r[t]||(r[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){function r(){o.off(t,r),e.apply(n,arguments)}var o=this;return r._=e,this.on(t,r,n)},emit:function(t){var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),r=0,o=n.length;for(r;o>r;r++)n[r].fn.apply(n[r].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),r=n[t],o=[];if(r&&e)for(var i=0,a=r.length;a>i;i++)r[i].fn!==e&&r[i].fn._!==e&&o.push(r[i]);return o.length?n[t]=o:delete n[t],this}},e.exports=r},{}],8:[function(t,e,n){"use strict";function r(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}n.__esModule=!0;var i=function(){function t(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,r.key,r)}}return function(e,n,r){return n&&t(e.prototype,n),r&&t(e,r),e}}(),a=t("select"),c=r(a),s=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=e.action,this.emitter=e.emitter,this.target=e.target,this.text=e.text,this.trigger=e.trigger,this.selectedText=""},t.prototype.initSelection=function t(){if(this.text&&this.target)throw new Error('Multiple attributes declared, use either "target" or "text"');if(this.text)this.selectFake();else{if(!this.target)throw new Error('Missing required attributes, use either "target" or "text"');this.selectTarget()}},t.prototype.selectFake=function t(){var e=this;this.removeFake(),this.fakeHandler=document.body.addEventListener("click",function(){return e.removeFake()}),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.position="absolute",this.fakeElem.style.left="-9999px",this.fakeElem.style.top=(window.pageYOffset||document.documentElement.scrollTop)+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,document.body.appendChild(this.fakeElem),this.selectedText=c.default(this.fakeElem),this.copyText()},t.prototype.removeFake=function t(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function t(){this.selectedText=c.default(this.target),this.copyText()},t.prototype.copyText=function t(){var e=void 0;try{e=document.execCommand(this.action)}catch(n){e=!1}this.handleResult(e)},t.prototype.handleResult=function t(e){e?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function t(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function t(){this.removeFake()},i(t,[{key:"action",set:function t(){var e=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=e,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function t(){return this._action}},{key:"target",set:function t(e){if(void 0!==e){if(!e||"object"!=typeof e||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');this._target=e}},get:function t(){return this._target}}]),t}();n.default=s,e.exports=n.default},{select:6}],9:[function(t,e,n){"use strict";function r(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function i(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}function a(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}n.__esModule=!0;var c=t("./clipboard-action"),s=r(c),u=t("tiny-emitter"),l=r(u),f=t("good-listener"),d=r(f),h=function(t){function e(n,r){o(this,e),t.call(this),this.resolveOptions(r),this.listenClick(n)}return i(e,t),e.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText},e.prototype.listenClick=function t(e){var n=this;this.listener=d.default(e,"click",function(t){return n.onClick(t)})},e.prototype.onClick=function t(e){this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new s.default({action:this.action(e.target),target:this.target(e.target),text:this.text(e.target),trigger:e.target,emitter:this})},e.prototype.defaultAction=function t(e){return a("action",e)},e.prototype.defaultTarget=function t(e){var n=a("target",e);return n?document.querySelector(n):void 0},e.prototype.defaultText=function t(e){return a("text",e)},e.prototype.destroy=function t(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(l.default);n.default=h,e.exports=n.default},{"./clipboard-action":8,"good-listener":5,"tiny-emitter":7}]},{},[9])(9)});

/**
 * @param $this
 * @returns {boolean}
 */
function fn_adls_validate_product_options($this) {
    var id = $this.attr('id');
    if (!id) {
        return;
    }
    var matches = id.match(/\d+/g);
    if (!matches || matches.length != 2) {
        console.error('ADLS: failed to retrieve product and option IDs of field', this);
        return;
    }
    var optionId = matches.pop();
    var productId = matches.pop();
    if (!optionId) {
        console.error('ADLS: failed to retrieve option ID of field', this);
        return;
    }
    if (!adlsOptionIds || adlsOptionIds.length == 0) {
        return;
    }
    var isAdlsOption = (adlsOptionIds.indexOf(optionId) > -1);
    if (!isAdlsOption) {
        return;
    }

    var sanitized = adlsHostnameFormat($this.val());
    if (sanitized) {
        $this.val(sanitized);
    }

    matches = id.split('_');
    if (!matches || matches.length < 2) {
        console.error('ADLS: failed to retrieve ID parts of field', this);
        return false;
    }

    var _objPrefixWithId = matches[1];
    var _id = matches[1];
    var _optionId = matches[2];

    fn_change_options(_objPrefixWithId, _id, _optionId);
}
function fn_adls_detect_product_options() {
    var $options = $('.ty-product-options :input');
    if (!$options || $options.length == 0) {
        return;

    }
    $options.each(function(i, option) {
        var $this = $(option);
        var id = $this.attr('id');
        if (!id) {
            return;
        }
        var matches = id.match(/\d+/g);
        if (!matches || matches.length != 2) {
            console.error('ADLS: failed to retrieve product and option IDs of field', this);
            return;
        }
        var optionId = matches.pop();
        var productId = matches.pop();
        if (!optionId) {
            console.error('ADLS: failed to retrieve option ID of field', this);
            return;
        }
        if (!adlsOptionIds || adlsOptionIds.length == 0) {
            return;
        }
        var isAdlsOption = (adlsOptionIds.indexOf(optionId) > -1);
        if (!isAdlsOption) {
            return;
        }

        matches = id.split('_');
        if (!matches || matches.length < 2) {
            console.error('ADLS: failed to retrieve ID parts of field', this);
            return false;
        }

        var _objPrefixWithId = matches[1];
        var _id = matches[1];
        var _optionId = matches[2];
        $this.attr('adls-obj-prefix-with-id', _objPrefixWithId);
        $this.attr('adls-id', _id);
        $this.attr('adls-option-id', _optionId);
        $this.addClass('adls-product-option-input');
        $this.parent('adls-product-option');
        $this.after('<i class="adls-product-option-button ty-icon-right-circle hidden"></i>');
    });
}

(function(_, $) {

    $(document).ready(function(){

        var clipboards = new Clipboard('.adls-clipboard');
        var fadeDelay = 100;
        clipboards.on('success', function(event) {
            var $button = $(event.trigger);
            var $successIcon = $button.find('.icon');
            var $clipboardIcon = $button.find('.fa');
            $clipboardIcon.fadeOut(fadeDelay);
            $successIcon.addClass('icon--order-success').fadeIn(fadeDelay);
            setTimeout(function() {
                $successIcon.removeClass('icon--order-success').fadeOut(fadeDelay);
                $clipboardIcon.fadeIn(fadeDelay);
            }, 1000);
        });

        // Hostname validator on order details page
        $('.adls-license-domains').on('change', '.adls-hostname', function () {
            var $this = $(this);
            $this.val(adlsHostnameFormat($this.val()));
        });

        fn_adls_detect_product_options();
        $(document).on('keyup paste input', '.ty-product-options .adls-product-option-input:input', function(event) {
            var $this = $(this);
            $this.addClass('adls-typing');
            $this.parent().find('.adls-product-option-button').show();
        });

        $(document).on('change', '.ty-product-options :input', function(event) {
            var $this = $(this);
            $this.removeClass('adls-typing');
            $this.parent().find('.adls-product-option-button').hide();
            return fn_adls_validate_product_options($(this))
        });

        $.ceEvent('on', 'ce.ajaxdone', function(elms, inlineScripts, params, data, responseText){
            fn_adls_detect_product_options();
            if (data) {
                if (data.adls_recalculate_cart) {
                    // automatically recalculate cart
                    $('#button_cart').trigger('click');
                }
            }
        });

    });
}(Tygh, Tygh.$));
