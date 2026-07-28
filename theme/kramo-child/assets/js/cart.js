(() => {
	"use strict";

	if (typeof kramoCart === "undefined") {
		return;
	}

	const dismissKey = "kramo_cart_recovery_dismissed";

	const readState = () => {
		const match = document.cookie.match(
			new RegExp(`(?:^|; )${kramoCart.cookieName}=([^;]*)`)
		);

		if (!match) {
			return null;
		}

		try {
			const payload = JSON.parse(decodeURIComponent(match[1]));
			const count = Number(payload.count);

			if (!Number.isInteger(count) || count <= 0) {
				return null;
			}

			return { count, total: String(payload.total || "") };
		} catch (error) {
			return null;
		}
	};

	const toast = (message, variant = "") => {
		const region = document.querySelector("[data-kramo-toasts]");

		if (!region || !message) {
			return;
		}

		const node = document.createElement("div");
		node.className = variant ? `kramo-toast kramo-toast--${variant}` : "kramo-toast";
		node.textContent = message;
		region.append(node);

		window.setTimeout(() => node.remove(), 4000);
	};

	const bump = (badge) => {
		badge.classList.remove("is-bumped");
		void badge.offsetWidth;
		badge.classList.add("is-bumped");
		badge.addEventListener(
			"animationend",
			() => badge.classList.remove("is-bumped"),
			{ once: true }
		);
	};

	const syncBadge = (animate = false) => {
		const state = readState();

		document.querySelectorAll("[data-kramo-cart-count]").forEach((badge) => {
			if (!state) {
				badge.hidden = true;
				badge.textContent = "";
				return;
			}

			const changed = badge.textContent !== String(state.count);
			badge.hidden = false;
			badge.textContent = String(state.count);

			if (animate && changed) {
				bump(badge);
			}
		});

		return state;
	};

	const syncRecovery = (state) => {
		const banner = document.querySelector("[data-kramo-cart-recovery]");

		if (!banner) {
			return;
		}

		const dismissed = window.sessionStorage.getItem(dismissKey) === "1";
		const total = banner.querySelector("[data-kramo-cart-total]");

		if (!state || dismissed) {
			banner.hidden = true;
			return;
		}

		if (total) {
			total.textContent = state.total;
		}

		banner.hidden = false;
	};

	document.addEventListener("click", (event) => {
		if (!event.target.closest("[data-kramo-cart-dismiss]")) {
			return;
		}

		window.sessionStorage.setItem(dismissKey, "1");
		const banner = document.querySelector("[data-kramo-cart-recovery]");
		if (banner) {
			banner.hidden = true;
		}
	});

	const refresh = (animate) => {
		const state = syncBadge(animate);
		syncRecovery(state);
	};

	if (window.jQuery) {
		window.jQuery(document.body).on("added_to_cart", () => {
			refresh(true);
			toast(kramoCart.addedMessage);
		});

		window.jQuery(document.body).on("removed_from_cart wc_fragments_refreshed", () => {
			refresh(false);
		});
	}

	document.addEventListener("kramo:cart-changed", (event) => {
		refresh(true);

		if (event.detail && event.detail.message) {
			toast(event.detail.message, event.detail.variant || "");
		}
	});

	refresh(false);

	window.kramo = window.kramo || {};
	window.kramo.toast = toast;
	window.kramo.refreshCart = refresh;
})();
