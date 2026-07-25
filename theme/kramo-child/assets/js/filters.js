(() => {
	"use strict";

	const form = document.querySelector(".kramo-filters");
	const results = document.querySelector(".kramo-catalog-results");

	if (!form || !results || typeof kramoFilters === "undefined") {
		return;
	}

	const setFormFromUrl = (url) => {
		const params = url.searchParams;
		Array.from(form.elements).forEach((field) => {
			if (field.name) {
				field.value = params.get(field.name) || "";
			}
		});
	};

	const requestProducts = async (url, pushState = true) => {
		results.setAttribute("aria-busy", "true");
		results.dataset.loadingText = kramoFilters.loadingText;

		const request = new URL(kramoFilters.ajaxUrl);
		request.searchParams.set("action", "kramo_filter_products");
		request.searchParams.set("nonce", kramoFilters.nonce);
		url.searchParams.forEach((value, key) => {
			if (value) {
				request.searchParams.set(key, value);
			}
		});

		try {
			const response = await fetch(request, {
				headers: { "X-Requested-With": "XMLHttpRequest" },
				credentials: "same-origin",
			});
			const payload = await response.json();

			if (!response.ok || !payload.success) {
				throw new Error("Catalog request failed.");
			}

			results.innerHTML = payload.data.html;
			results.setAttribute("aria-busy", "false");

			if (pushState) {
				window.history.pushState({}, "", url);
			}

			document.dispatchEvent(new CustomEvent("kramo:catalog-updated"));
		} catch (error) {
			results.setAttribute("aria-busy", "false");
			results.innerHTML = `<p class="woocommerce-error">${kramoFilters.errorMessage}</p>`;
		}
	};

	const submit = () => {
		const data = new FormData(form);
		const url = new URL(form.action, window.location.origin);

		data.forEach((value, key) => {
			const normalized = String(value).trim();
			if (normalized) {
				url.searchParams.set(key, normalized);
			}
		});

		requestProducts(url);
	};

	form.addEventListener("submit", (event) => {
		event.preventDefault();
		submit();
	});

	form.addEventListener("change", (event) => {
		if (event.target.matches("select")) {
			submit();
		}
	});

	form.querySelector(".kramo-filters__reset")?.addEventListener("click", (event) => {
		event.preventDefault();
		const url = new URL(event.currentTarget.href);
		Array.from(form.elements).forEach((field) => {
			if (field.name) {
				field.value = "";
			}
		});
		requestProducts(url);
	});

	results.addEventListener("click", (event) => {
		const link = event.target.closest(".woocommerce-pagination a");
		if (!link) {
			return;
		}

		event.preventDefault();
		requestProducts(new URL(link.href));
	});

	window.addEventListener("popstate", () => {
		const url = new URL(window.location.href);
		setFormFromUrl(url);
		requestProducts(url, false);
	});
})();
