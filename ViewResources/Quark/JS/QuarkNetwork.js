/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark.Network namespace
 */
Quark.Network = {};

/**
 * @param {string=} [host='127.0.0.1']
 * @param {number=} [port=25000]
 * @param {object=} [on={open,close,error}]
 *
 * @constructor
 */
Quark.Network.Client = function (host, port, on) {
    on = on || {};
        on.open = on.open || function () {};
        on.close = on.close || function () {};
        on.error = on.error || function () {};

    var that = this;
    var events = {};
    var response = function () {};
    var message = function (e) {
        try {
            var input = JSON.parse(e.data);

            if (input.response != undefined)
                response(input.response, input.data, input.session);

            if (input.event != undefined) {
                input.event = input.event.toLowerCase();

                if (events[input.event] instanceof Array) {
                    var i = 0;

                    while (i < events[input.event].length) {
                        events[input.event][i](input.event, input.data, input.session);

                        i++;
                    }
                }
            }
        }
        catch (e) {
            on.error(e);
        }
    };

    that.host = host || document.location.hostname;
    that.port = port || 25000;
    that.socket = null;

    /**
     * API methods
     */
    that.Connect = function () {
        that.socket = new WebSocket('ws://' + that.host + ':' + that.port);

        that.socket.onmessage = message;

        that.socket.onopen = on.open;
        that.socket.onclose = on.close;
        that.socket.onerror = on.error;
    };

    /**
     * @return {boolean}
     */
    that.Close = function () {
        if (that.socket == null) return false;

        that.socket.close();
        that.socket = null;

        return true;
    };

    /**
     * @param {object} data
     *
     * @return {boolean}
     */
    that.Send = function (data) {
        if (!(that.socket instanceof WebSocket)) return false;

        that.socket.send(data);
        return true;
    };

    /**
     * @param {string} url
     * @param {Function} listener
     *
     * @return {boolean}
     */
    that.Event = function (url, listener) {
        if (!(listener instanceof Function)) return false;

        url = url.toLowerCase();

        if (events[url] == undefined)
            events[url] = [];

        events[url].push(listener);

        return true;
    };

    /**
     * @param listener
     *
     * @return {boolean}
     */
    that.Response = function (listener) {
        if (!(listener instanceof Function)) return false;

        response = listener;

        return true;
    };

    /**
     * @param {string} url
     * @param {object=} data
     * @param {object=} session
     */
    that.Service = function (url, data, session) {
        try {
            var out = {
                url: url,
                data: data
            };

            if (session != undefined)
                out.session = session;

            that.Send(JSON.stringify(out));
        }
        catch (e) {
            on.error(e);
        }
    };
};

/**
 * Get a connection from cluster controller, specified by host and port, to the most suitable cluster node
 *
 * @param host
 * @param port
 */
Quark.Network.Client.From = function (host, port) {

};