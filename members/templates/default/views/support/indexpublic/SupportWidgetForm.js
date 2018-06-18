//Jquery Extensions
jQuery.extend({
    queryParameters: function () {
        var d = [],
            c;
        var a = window.location.href.slice(window.location.href.indexOf("?") + 1).split("&");
        for (var b = 0; b < a.length; b++) {
            c = a[b].split("=");
            c[1] = decodeURIComponent((c[1] || "").replace(/\+/g, " "));
            d.push(c[0]);
            d[c[0]] = c[1];
        }
        return d;
    }
});

//Setup Feedback App
(function (c) {    
    //setup sammy for feedback app use
    c.sammy.log = function () {};
    
    var d = {
            Util: {}
        };
        
    //global binging of fields to show error msg on blur
    c("input, select, textarea").bind("input blur change", function (f) {
        if (c(this).isValid()) {
            c(this).trigger("valid");
        }
    }).bind("invalid", function (f) {
        c(this).showError();
    }).bind("valid", function (f) {
        c(this).hideError();
    });

    //lets create the FeedbackApp global Sammy object
    d.app = c.sammy("#dropbox", function () {
        this.use("Flash");
        this.helpers({
            hideFlash: function () {
                c("#dropbox_content").find(".flash").remove();
            }
        });
        this.swap = function (f) {
            c("#dropbox_content").children(".view").not(f).hide();
            f.show();
        };
        this.bind("waiting", function () {
            this.swap(c("#waiting"));
        });
        this.bind("done", function () {
            c("#waiting").hide();
        });
        this.bind("questionAsked", function (f, g) {
            d.Util.HasQuery.updateAllQueries(g);
        });
        this.bind("noSuggestions", function () {
            var f = this;
            f.redirect("#/dropbox/tickets/new");
        });
        this.after(function () {
            var f = c(this.flash().toHTML());
            f.not(":empty").prependTo(c("#dropbox_content").find(".view .body:visible").first());
            this.flash().clear();
        });
        this.get("#/dropbox", function (f) {
            if (d.Suggestions.enabled()) {
                f.redirect("#/dropbox/suggestions/ask");
            } else {
                f.redirect("#/dropbox/tickets/new");
            }
        });
    });
    window.FeedbackApp = d;
    window.$j = c;
}(window.jQuery));


(function (e, j) {
    var d = e.parseJSON(e("#translations")[0].text);
    var f = function (k) {
            return e(k).is("form");
        };
    var g = function (k) {
            var l = /^.+\@.+\..+$/;
            return l.test(k);
        };
    var h = function (k) {
            if (e(k).is("select")) {
                return e.support.requiredOnSelect;
            } else {
                return e.support.html5Validation;
            }
        };
    var a = function (m) {            
            if (h(m)) {
                return m.prop("validity").valid;
            }
            var l = m.val();
            if (l == m.attr("placeholder")) {
                l = "";
            }
            var k = m.prop("required") || m.is('[required="required"]');
            if (k && (l === undefined || l === null || l === "")) {
                return false;
            }
            if ((m.attr("type") === "email" || m.attr("data-type") === "email") && !g(l)) {
                return false;
            }
            return true;
        };
    var c = function (k) {
            if (!a(k)) {
                k.trigger("invalid");
            }
            k.resetPlaceholder();
        };
    var b = function (k) {
            return !!j(k.find("input,select,textarea")).all(function (l) {
                return e(l).isValid();
            });
        };
    var i = function (k) {            
            e("input, textarea","select", k).each(function (m, l) {
                c(e(l));
            });
        };
    e.extend(e.support, {
        html5Validation: (typeof (jQuery("<input />").prop("validity")) !== "undefined"),
        requiredOnSelect: (typeof (jQuery("<select />").prop("required")) !== "undefined")
    });
    e.fn.extend({
        fire: function (l, k) {
            e(this).trigger(l, k);
        },
        validate: function () {
            return e(this).each(function (l, k) {
                k = e(k);
                if (f(k)) {
                    i(k);
                } else {
                    c(k);
                }
            });
        },
        isValid: function () {
            var k = e(this);
            var returnValue = false;
            if (f(k)) {
                returnValue = b(k);
            } else {
                returnValue = a(k);
            }
            return returnValue;
        },
        errorMessageOutput: function () {
            return e("#" + e(this).attr("id") + "_errors");
        },
        showError: function () {
            var k = e(this);
            k.addClass("invalid").errorMessageOutput().html(d["txt.feedback_tab.error.seems_to_be_invalid"]).show();
            return k;
        },
        hideError: function () {
            var k = e(this);
            k.removeClass("invalid").errorMessageOutput().hide().html("&nbsp;");
            return k;
        }
    });
}(window.jQuery, window._));

//function used to update all fields that require the
//lastest query searched.  i.e. ticket subject
(function (c, a) {
    var b;
    b = function (e) {
        this.query = c.proxy(c(e), "val");
        //only show the searched query on email subject and the query input box
        //do not show in the knowledge base search window.  Remove (if block) if
        //we want to include the searched string in the KB window as well
        if(e=="#subject" || e=="#suggestions_query") {
            b.instances.push(c(e));
        }
    };
    c.extend(b, {
        instances: [],
        updateAllQueries: function (d) {
            a(this.instances).each(function (e) {
                e.val(d);
            });
        }
    });
    FeedbackApp.Util.HasQuery = b;
}(window.jQuery, window._));



(function (e) {
    var d = {};
    FeedbackApp.KnowledgeBase = {
        HIDE_EVENT: "FeedbackApp.KnowledgeBase.hide",
        SHOW_EVENT: "FeedbackApp.KnowledgeBase.show"
    };
    if (!e("body").hasClass("knowledge_base")) {
        return;
    }
    FeedbackApp.Util.HasQuery.call(d, "#knowledge_base_search_input");
    FeedbackApp.app.bind(FeedbackApp.KnowledgeBase.HIDE_EVENT, function () {
        if (e("body").hasClass("knowledge_base")) {
            e("#knowledge_base_search").hide();
        }
    });
    FeedbackApp.app.bind(FeedbackApp.KnowledgeBase.SHOW_EVENT, function () {
        if (e("body").hasClass("knowledge_base")) {
            e("#knowledge_base_search").show();
        }
    });
    FeedbackApp.app.get(/#\/dropbox\/knowledge_base/, function (g) {
        g.hideFlash();
        g.trigger("questionAsked", d.query());
        g.trigger("waiting");
        FeedbackApp.Util.searchForums({
            form: e("#knowledge_base_search"),
            output: e(".body", e("#knowledge_base_search_results")),
            backURL: "#/dropbox/tickets/new",
            success: function () {
                g.trigger("done");
                g.swap(e("#knowledge_base_search_results"));
            },
            noResults: function () {
                g.trigger("done");
                g.flash("error", FeedbackApp.Util.searchForums.NO_RESULTS);
                g.redirect("#/dropbox/tickets/new");
            },
            error: function () {
                g.trigger("done");
                g.flash("error", FeedbackApp.Util.searchForums.ERROR);
                g.redirect("#/dropbox/tickets/new");
            }
        });
    });
}(window.jQuery));

(function (e) {
    var d = e("#search_results_template").html();
    var c = e.parseJSON(e("#translations")[0].text);

    function f(h) {
        return h.form ? h.form.attr("data-remote-moreurl") + "&" + h.form.serialize() : null;
    }
    function a(i, h) {
        i.backURL = h.backURL;
        i.total_entries = Number(i.total_entries || 0);
        if (i.total_entries <= i.entries.length) {
            i.total_entries = 0;
        }
        i.seeAllResults = Mustache.to_html(c["txt.feedback_tab.link.see_all_results"], i);
        i.goBack = c["txt.feedback_tab.link.go_back"];
        i.moreResultsURL = f(h);
        h.output.show().find(".content").html(Mustache.to_html(d,i));
    }
    function g(i, h) {
        if (i.entries.length) {
            a(i, h);
            h.success();
        } else {
            h.noResults();
        }
    }
    function b(h) {
        h.output.hide();
        e.ajax({
            url: h.form.attr("data-remote-url"),
            type: "get",
            before_send: function (i) {
                i.setRequestHeader("Accept", "application/json");
            },
            data: h.form.serialize(),
            dataType: "json",
            success: function (i) {
                g(i, h);
            },
            error: h.error
        });
    }
    b.NO_RESULTS = c["txt.feedback_tab.error.no_results_found"];
    b.ERROR = c["txt.feedback_tab.error.problem_submitting_search"];
    FeedbackApp.Util.searchForums = b;
}(jQuery));



(function (e, f, k) {
    var b = e("form", e("#ticket_submission")),
        h = {},
        c = e.parseJSON(e("#translations")[0].text),
        a = c["txt.feedback_tab.error.problem_submitting_request"];
    b.enable = function () {
        return e(this).find(":disabled").prop("disabled", false).end().find("#newticket_submit").val("Submit").end();
    };
    b.disable = function () {
        return e(this).find(":enabled").prop("disabled", true).end().find("#newticket_submit").val("Submitting...").end();
    };
    function d(l) {
        e.ajax(e.extend({
            data: b.serialize()
        }, {
            type: "POST",
            url: b.attr("data-remote-url"),
            dataType: "json",
            before_send: function (l) {
                l.setRequestHeader("Accept", "text/javascript");
            }
        }, l));
    }
    FeedbackApp.Util.HasQuery.call(h, "#subject");
    (function (l) {
        e("#client", b).val("Client: " + navigator.userAgent);
        e("#submitted_from", b).val(document.referrer);
        e("#subject", b).val(l.subject);
        e("#description", b).val(l.description);
        e("#name", b).val(l.name);
        e("#email", b).val(l.email);
        e("#ticketTypeId",b).val(l.ticketTypeId);
    }(e.queryParameters()));
    FeedbackApp.app.get("#/dropbox/tickets/new", function () {
        this.swap(e("#ticket_submission"));
    });
    FeedbackApp.app.before("#/dropbox/tickets", function () {
        this.hideFlash();
        return b.validate().isValid();
    });
    FeedbackApp.app.post("#/dropbox/tickets", function (l) {
        l.trigger("questionAsked", h.query());
        d({
            success: function (n, o, m) {
                l.redirect("#/dropbox/tickets/thanks");
            },
            error: function (m, o, n) {
                b.enable();
                l.flash("error", a);
                l.redirect("#/dropbox/tickets/new");
            }
        });
        b.disable();
    });
    FeedbackApp.app.get("#/dropbox/tickets/thanks", function () {
        this.trigger(FeedbackApp.KnowledgeBase.HIDE_EVENT);
        this.swap(e("#ticket_success"));
    });
}(window.jQuery));


(function (e) {
    var d = {};
    FeedbackApp.KnowledgeBase = {
        HIDE_EVENT: "FeedbackApp.KnowledgeBase.hide",
        SHOW_EVENT: "FeedbackApp.KnowledgeBase.show"
    };
    
    //if we are not showing knowledge base skip this section
    if (!e("body").hasClass("knowledge_base")) {
        return;
    }
    
    FeedbackApp.Util.HasQuery.call(d, "#knowledge_base_search_input");
    FeedbackApp.app.bind(FeedbackApp.KnowledgeBase.HIDE_EVENT, function () {
        if (e("body").hasClass("knowledge_base")) {
            e("#knowledge_base_search").hide();
        }
    });
    FeedbackApp.app.bind(FeedbackApp.KnowledgeBase.SHOW_EVENT, function () {
        if (e("body").hasClass("knowledge_base")) {
            e("#knowledge_base_search").show();
        }
    });
    FeedbackApp.app.get(/#\/dropbox\/knowledge_base/, function (g) {
        g.hideFlash();
        g.trigger("questionAsked", d.query());
        g.trigger("waiting");
        FeedbackApp.Util.searchForums({
            form: e("#knowledge_base_search"),
            output: e(".body", e("#knowledge_base_search_results")),
            backURL: "#/dropbox/tickets/new",
            success: function () {
                g.trigger("done");
                g.swap(e("#knowledge_base_search_results"));
            },
            noResults: function () {
                g.trigger("done");
                g.flash("error", FeedbackApp.Util.searchForums.NO_RESULTS);
                g.redirect("#/dropbox/tickets/new");
            },
            error: function () {
                g.trigger("done");
                g.flash("error", FeedbackApp.Util.searchForums.ERROR);
                g.redirect("#/dropbox/tickets/new");
            }
        });
    });
}(window.jQuery));

(function (d) {
    var j = {},
        b = d.parseJSON(d("#translations")[0].text);
    
    FeedbackApp.Suggestions = {
        enabled: function () {
            return d("body").hasClass("suggestions");
        }
    };
    if (!d("body").hasClass("suggestions")) {
        return;
    }
    FeedbackApp.Util.HasQuery.call(j, "#suggestions_query");
    FeedbackApp.app.bind("questionAsked", function (l, k) {
        d("#suggestions_results .you_asked .label").html("Your question");
        d("#suggestions_results .you_asked .question").html(k);
    });
    FeedbackApp.app.before(/#\/dropbox\/suggestions(?:\/ask)?/, function () {
        this.trigger(FeedbackApp.KnowledgeBase.HIDE_EVENT);
        return true;
    });
    FeedbackApp.app.before({
        except: {
            path: /#\/dropbox\/suggestions(?:\/ask)?/
        }
    }, function () {
        this.trigger(FeedbackApp.KnowledgeBase.SHOW_EVENT);
        return true;
    });
    FeedbackApp.app.get("#/dropbox/suggestions/ask", function () {
        d("form", d("#suggestions_form")).removeClass("submitted");
        this.swap(d("#suggestions_form"));
    });
    FeedbackApp.app.before("#/dropbox/suggestions", function () {
        this.hideFlash();
        return d("form", d("#suggestions_form")).validate().isValid();
    });
    FeedbackApp.app.get(/#\/dropbox\/suggestions(?:\?.*)?$/, function (k) {
        d("form", d("#suggestions_form")).addClass("submitted");
        k.trigger("questionAsked", j.query());
        k.trigger("waiting");
        FeedbackApp.Util.searchForums({
            form: d("form", d("#suggestions_form")),
            backURL: "#/dropbox/suggestions/ask",
            output: d(".body", d("#suggestions_results")),
            success: function () {
                k.trigger("done");
                k.swap(d("#suggestions_results"));
            },
            noResults: function () {
                k.flash("warn", b["txt.feedback_tab.notification.need_more_details"]);
                k.trigger("done");
                k.trigger("noSuggestions");
            },
            error: function () {
                k.flash("warn", b["txt.feedback_tab.notification.need_more_details"]);
                k.trigger("done");
                k.trigger("noSuggestions");
            }
        });
    });
    FeedbackApp.app.get("#/dropbox/suggestions/notHelpful", function () {
        this.trigger("noSuggestions");
    });
}(window.jQuery));

jQuery(function () {
    FeedbackApp.app.run("#/dropbox");
});