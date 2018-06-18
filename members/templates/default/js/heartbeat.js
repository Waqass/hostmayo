/**
 * Heartbeat function handler
 *
 * @author Rick Goodrow <rick@clientexec.com>
 * @version 1.1
 *
 */

heartbeat = {
    pulseCount:  0,
    pulseLength: 0,
    pulseTop:    0,
    beat:        null,
    beats:       [],
    url:         'index.php?fuse=admin&action=pulse',

    // Add heartbeat to the stack. Requirements are just a name (string), and a number in seconds on how often to pulse
    add: function(options) {
        var that = this;
        if (typeof options.name != 'string' || typeof options.pulse != 'number') {
            alert('Invalid parameters in heartbeat function');
        }

        for(i = 0; i < this.beats.length; i++) {
            if (this.beats[i].name == options.name) {
                //console.debug('duplicate heartbeat name');
                return false;
            }
        }
        var defaults = {
            args: {},
            callback: null,
            pulse: 15,
            delay: null
        };

        options = $.extend(true, {}, defaults, options);

        //options.prepulse = typeof options.pulse == 'undefined' ? false : options.prepulse;
        //options.args = typeof options.args == 'undefined' ? null : options.args;
        //options.callback = typeof options.callback == 'undefined' ? null : options.callback;
        for (var i in options.args) {
            if (i == 'fuse' || i == 'action') {
                alert('cannot use restricted query params "fuse" or "action"');
            }
        }

        if (options.delay !== null) {
            if (options.delay !== 0) {
                setTimeout(function(){
                    that.prepulse(options.name, options.args, options.callback);
                    that.beats.push({
                        name:     options.name,
                        pulse:    options.pulse,
                        args:     options.args,
                        callback: options.callback
                    });
                    that.setPulse();
                }, options.delay * 1000);
            } else {
                this.prepulse(options.name, options.args, options.callback);
                this.beats.push({
                    name:     options.name,
                    pulse:    options.pulse,
                    args:     options.args,
                    callback: options.callback
                });
                this.setPulse();
            }
        } else {
            this.beats.push({
                name:     options.name,
                pulse:    options.pulse,
                args:     options.args,
                callback: options.callback
            });
            this.setPulse();
        }
        return true;
    },

    // Remove heartbeat from the stack by name
    remove: function(beatName) {
        for(i = 0; i < this.beats.length; i++) {
            if (this.beats[i].name == beatName) {
                this.beats.splice(i, 1);
            }
        }
        this.setPulse();
    },

    // Check if a beat is already in place
    check: function(beatName) {
        for (i = 0; i < this.beats.length; i++) {
            if (this.beats[i].name == beatName) {
                return true;
            }
        }
        return false;
    },

    // Reconfigure the timings on pulse counts
    setPulse: function() {
        if (this.beats.length === 0) {
            clearInterval(this.beat);
            this.pulseCount = 0;
            this.pulseTop = 0;
            this.pulseLength = 0;
            return;
        }
        var pulseArray = [];
        for (var i = 0; i < this.beats.length; i ++) {
            pulseArray.push(this.beats[i].pulse);
        }
        // Get the Greatest Common Factor & Lowest Common Multiple for timing purposes
        this.pulseCount = 0;
        this.pulseTop = this.gcf_lcm_ar('lcm', pulseArray.slice());
        this.pulseLength = this.gcf_lcm_ar('gcf', pulseArray.slice());
        if (this.beat) { clearInterval(this.beat); }
        this.beat = setInterval(function(){ heartbeat.pulse(); }, this.pulseLength * 1000);
    },

    // Prepulse function - fire the newly added beat once upon adding to the stack
    prepulse: function(name, args, callback) {
        var that = this;
        var beatArgs = {};
        beatArgs[name] = args;
        $.ajax({
            type: 'POST',   // instead of GET 'cause args can be VERY long
            url: that.url,
            data: {
                args: JSON.stringify(beatArgs)
            },
            success: function(response) {
                ce.checkRedirectLogin(response);
                if (callback) { callback(response.returnArgs[name]); }
            }
        });
    },

    // Pulse function - what actually sends the ajax requests to the server, then parses the responses with their corresponding callback functions
    pulse: function() {
        var that = this;
        this.pulseCount += this.pulseLength;
        var beatArgs = {}, beatFunctions = {}, firePulse = false;
        for(i = 0; i < this.beats.length; i++) {
            if (this.pulseCount % this.beats[i].pulse === 0) {
                firePulse = true;
                if (this.beats[i].args !== null) {
                    beatArgs[this.beats[i].name] = this.beats[i].args;
                } else {
                    beatArgs[this.beats[i].name] = {};
                }
                if (this.beats[i].callback !== null) { beatFunctions[this.beats[i].name] = this.beats[i].callback; }
            }
        }
        if (firePulse) {
            // console.debug(beatArgs,that.url);
            $.ajax({
                type: 'POST',   // instead of GET 'cause args can be VERY long
                url: that.url,
                data: {
                    args: JSON.stringify(beatArgs)
                },
                dataType: 'json',
                success: function(response) {
                    ce.checkRedirectLogin(response);
                    for (var n in beatFunctions) {
                        if (n in response.returnArgs) {
                            beatFunctions[n](response.returnArgs[n]);
                        } else {
                            beatFunctions[n]();
                        }
                    }
                },
                error: function(jqXHR) {
                    // this happens when the session expires
                    if (jqXHR.responseText.indexOf("g=installer") > -1) {
                        window.location.href = '../index.php';
                    }
                }
            });
        }
        if (this.pulseCount == this.pulseTop) { this.pulseCount = 0; }
    },

    // Greatest Common Factor & Lowest Common Multiple functions
    gcf: function(a, b) { return (b === 0) ? (a):(this.gcf(b, a % b)); },
    lcm: function(a, b) { return ( a / this.gcf(a,b) ) * b; },
    gcf_lcm_ar: function (type, ar) {
        if (ar.length > 1) {
            if (type == 'lcm') {
                ar.push( this.lcm( ar.shift() , ar.shift() ) );
            } else {
                ar.push( this.gcf( ar.shift() , ar.shift() ) );
            }
            return this.gcf_lcm_ar(type, ar);
        } else {
            return ar[0];
        }
    }

};
