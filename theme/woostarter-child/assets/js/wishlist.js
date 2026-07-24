(() => {
	"use strict";

	if (typeof wooStarterWishlist === "undefined") {
		return;
	}

	const storageKey = "woostarter_wishlist";
	const parseIds = (value) => {
		try {
			const ids = JSON.parse(value);
			return Array.isArray(ids)
				? [...new Set(ids.map(Number).filter((id) => Number.isInteger(id) && id > 0))]
				: [];
		} catch (error) {
			return [];
		}
	};
	const saveLocal = (ids) => {
		window.localStorage.setItem(storageKey, JSON.stringify(ids));
	};

	let productIds = parseIds(window.localStorage.getItem(storageKey));
	if (wooStarterWishlist.isLoggedIn) {
		productIds = [...new Set([...productIds, ...wooStarterWishlist.serverIds.map(Number)])];
		saveLocal(productIds);
	}

	const syncButtons = () => {
		document.querySelectorAll(".woostarter-wishlist-toggle").forEach((button) => {
			const isSaved = productIds.includes(Number(button.dataset.productId));
			const label = isSaved
				? wooStarterWishlist.removeLabel
				: wooStarterWishlist.addLabel;
			button.setAttribute("aria-pressed", isSaved ? "true" : "false");
			button.setAttribute("aria-label", label);
			const screenReader = button.querySelector(".screen-reader-text");
			if (screenReader) {
				screenReader.textContent = label;
			}
		});
	};

	const syncServer = async () => {
		if (!wooStarterWishlist.isLoggedIn) {
			return;
		}

		const body = new URLSearchParams({
			action: "woostarter_update_wishlist",
			nonce: wooStarterWishlist.nonce,
		});
		productIds.forEach((id) => body.append("product_ids[]", String(id)));

		try {
			const response = await fetch(wooStarterWishlist.ajaxUrl, {
				method: "POST",
				body,
				credentials: "same-origin",
			});
			const payload = await response.json();
			if (payload.success) {
				productIds = payload.data.productIds.map(Number);
				saveLocal(productIds);
				syncButtons();
			}
		} catch (error) {
			// Local state remains available and will be merged on the next page load.
		}
	};

	const renderWishlistPage = async () => {
		const container = document.querySelector("[data-wishlist-products]");
		const empty = document.querySelector("[data-wishlist-empty]");
		if (!container || !empty) {
			return;
		}

		if (!productIds.length) {
			container.replaceChildren();
			empty.hidden = false;
			return;
		}

		const request = new URL(wooStarterWishlist.ajaxUrl);
		request.searchParams.set("action", "woostarter_render_wishlist");
		request.searchParams.set("nonce", wooStarterWishlist.nonce);
		productIds.forEach((id) => request.searchParams.append("product_ids[]", String(id)));

		try {
			const response = await fetch(request, { credentials: "same-origin" });
			const payload = await response.json();
			if (!payload.success) {
				throw new Error("Wishlist request failed.");
			}

			container.innerHTML = payload.data.html;
			empty.hidden = Boolean(payload.data.html);
			syncButtons();
		} catch (error) {
			container.textContent = wooStarterWishlist.errorMessage;
			empty.hidden = true;
		}
	};

	document.addEventListener("click", (event) => {
		const button = event.target.closest(".woostarter-wishlist-toggle");
		if (!button) {
			return;
		}

		event.preventDefault();
		const productId = Number(button.dataset.productId);
		productIds = productIds.includes(productId)
			? productIds.filter((id) => id !== productId)
			: [...productIds, productId];
		saveLocal(productIds);
		syncButtons();
		syncServer();
		renderWishlistPage();
	});

	document.addEventListener("woostarter:catalog-updated", syncButtons);
	document.addEventListener("DOMContentLoaded", () => {
		syncButtons();
		syncServer();
		renderWishlistPage();
	});
})();
