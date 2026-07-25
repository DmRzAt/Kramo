(() => {
	"use strict";

	if (typeof kramoWishlist === "undefined") {
		return;
	}

	const storageKey = "kramo_wishlist";
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
	if (kramoWishlist.isLoggedIn) {
		productIds = [...new Set([...productIds, ...kramoWishlist.serverIds.map(Number)])];
		saveLocal(productIds);
	}

	const syncButtons = () => {
		document.querySelectorAll(".kramo-wishlist-toggle").forEach((button) => {
			const isSaved = productIds.includes(Number(button.dataset.productId));
			const label = isSaved
				? kramoWishlist.removeLabel
				: kramoWishlist.addLabel;
			button.setAttribute("aria-pressed", isSaved ? "true" : "false");
			button.setAttribute("aria-label", label);
			const screenReader = button.querySelector(".screen-reader-text");
			if (screenReader) {
				screenReader.textContent = label;
			}
		});
	};

	const syncServer = async () => {
		if (!kramoWishlist.isLoggedIn) {
			return;
		}

		const body = new URLSearchParams({
			action: "kramo_update_wishlist",
			nonce: kramoWishlist.nonce,
		});
		productIds.forEach((id) => body.append("product_ids[]", String(id)));

		try {
			const response = await fetch(kramoWishlist.ajaxUrl, {
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

		const request = new URL(kramoWishlist.ajaxUrl);
		request.searchParams.set("action", "kramo_render_wishlist");
		request.searchParams.set("nonce", kramoWishlist.nonce);
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
			container.textContent = kramoWishlist.errorMessage;
			empty.hidden = true;
		}
	};

	document.addEventListener("click", (event) => {
		const button = event.target.closest(".kramo-wishlist-toggle");
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

	document.addEventListener("kramo:catalog-updated", syncButtons);
	document.addEventListener("DOMContentLoaded", () => {
		syncButtons();
		syncServer();
		renderWishlistPage();
	});
})();
