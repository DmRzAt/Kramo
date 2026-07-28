(function () {
	"use strict";

	var config = window.kramoConsent;
	if (!config) {
		return;
	}

	var banner = document.querySelector("[data-kramo-consent]");
	if (!banner) {
		return;
	}

	function readConsent() {
		var match = document.cookie.match(
			new RegExp("(?:^|; )" + config.cookieName + "=([^;]*)")
		);
		if (match) {
			return decodeURIComponent(match[1]);
		}

		// Legacy woostarter cookie — keep reading until the visitor re-chooses.
		match = document.cookie.match(/(?:^|; )ws_consent=([^;]*)/);
		return match ? decodeURIComponent(match[1]) : "";
	}

	function storeConsent(value) {
		var expires = new Date();
		expires.setTime(expires.getTime() + config.cookieDays * 86400000);
		var base =
			"=" +
			encodeURIComponent(value) +
			"; expires=" +
			expires.toUTCString() +
			"; path=/; SameSite=Lax" +
			(location.protocol === "https:" ? "; Secure" : "");
		document.cookie = config.cookieName + base;
		// Expire the legacy cookie so it does not fight the new one.
		document.cookie =
			"ws_consent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax";
	}

	function loadScript(src) {
		var tag = document.createElement("script");
		tag.async = true;
		tag.src = src;
		document.head.appendChild(tag);

		return tag;
	}

	function startAnalytics() {
		var trackers = config.trackers || {};

		if (trackers.ga4) {
			window.dataLayer = window.dataLayer || [];
			window.gtag = function () {
				window.dataLayer.push(arguments);
			};
			loadScript(
				"https://www.googletagmanager.com/gtag/js?id=" +
					encodeURIComponent(trackers.ga4)
			);
			window.gtag("js", new Date());
			window.gtag("config", trackers.ga4, { anonymize_ip: true });
		}

		if (trackers.pixel) {
			/* eslint-disable */
			!(function (f, b, e, v, n, t, s) {
				if (f.fbq) return;
				n = f.fbq = function () {
					n.callMethod
						? n.callMethod.apply(n, arguments)
						: n.queue.push(arguments);
				};
				if (!f._fbq) f._fbq = n;
				n.push = n;
				n.loaded = !0;
				n.version = "2.0";
				n.queue = [];
				t = b.createElement(e);
				t.async = !0;
				t.src = v;
				s = b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t, s);
			})(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
			/* eslint-enable */
			window.fbq("init", trackers.pixel);
			window.fbq("track", "PageView");
		}
	}

	function hideBanner() {
		banner.hidden = true;
	}

	function decide(value) {
		storeConsent(value);
		hideBanner();

		if (value === "accepted") {
			startAnalytics();
		}
	}

	var current = readConsent();
	if (current === "accepted") {
		startAnalytics();
		return;
	}

	if (current === "rejected") {
		return;
	}

	banner.hidden = false;

	var accept = banner.querySelector("[data-consent-accept]");
	var reject = banner.querySelector("[data-consent-reject]");

	if (accept) {
		accept.addEventListener("click", function () {
			decide("accepted");
		});
	}

	if (reject) {
		reject.addEventListener("click", function () {
			decide("rejected");
		});
	}
})();
