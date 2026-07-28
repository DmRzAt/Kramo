(() => {
	"use strict";

	const form = document.querySelector(".kramo-filters");
	const results = document.querySelector(".kramo-catalog-results");

	if (!form || !results || typeof kramoFilters === "undefined") {
		return;
	}

	const countEl = document.querySelector("[data-kramo-catalog-count]");
	const densityButtons = Array.from(
		document.querySelectorAll("[data-kramo-density]")
	);

	const announce = (count) => {
		if (!countEl) {
			return;
		}

		countEl.textContent = count > 0
			? kramoFilters.resultsText.replace("%d", String(count))
			: kramoFilters.noResultsText;
	};

	const applyDensity = (columns) => {
		const next = columns === 2 ? 2 : 4;

		results.dataset.columns = String(next);
		densityButtons.forEach((button) => {
			button.setAttribute(
				"aria-pressed",
				Number(button.dataset.kramoDensity) === next ? "true" : "false"
			);
		});

		try {
			window.localStorage.setItem(kramoFilters.densityKey, String(next));
		} catch (error) {
			// Private mode can refuse localStorage; density still works for the session.
		}
	};

	const savedDensity = (() => {
		try {
			return Number(window.localStorage.getItem(kramoFilters.densityKey)) || 4;
		} catch (error) {
			return 4;
		}
	})();

	applyDensity(savedDensity);

	densityButtons.forEach((button) => {
		button.addEventListener("click", () => {
			applyDensity(Number(button.dataset.kramoDensity) || 4);
		});
	});

	const skeletonCount = () => {
		const cards = results.querySelectorAll("ul.products li.product").length;

		return cards > 0 ? cards : 8;
	};

	const showSkeleton = () => {
		const grid = document.createElement("ul");
		grid.className = "kramo-skeleton-grid";
		grid.setAttribute("aria-hidden", "true");

		for (let index = 0; index < skeletonCount(); index += 1) {
			const card = document.createElement("li");
			card.className = "kramo-skeleton-card";
			card.innerHTML = [
				'<span class="kramo-skeleton kramo-skeleton-card__media"></span>',
				'<span class="kramo-skeleton kramo-skeleton-card__line"></span>',
				'<span class="kramo-skeleton kramo-skeleton-card__line kramo-skeleton-card__line--short"></span>',
			].join("");
			grid.append(card);
		}

		results.setAttribute("aria-busy", "true");
		results.dataset.loadingText = kramoFilters.loadingText;
		announce(0);
		countEl && (countEl.textContent = kramoFilters.loadingText);
		results.append(grid);
	};

	const clearSkeleton = () => {
		results.querySelectorAll(".kramo-skeleton-grid").forEach((grid) => grid.remove());
		results.setAttribute("aria-busy", "false");
		delete results.dataset.loadingText;
	};

	const scrollToResults = () => {
		const behavior = window.kramo && window.kramo.reducedMotion ? "auto" : "smooth";
		const top = results.getBoundingClientRect().top + window.scrollY - 96;

		window.scrollTo({ top: Math.max(0, top), behavior });
	};

	const setFormFromUrl = (url) => {
		const params = url.searchParams;
		Array.from(form.elements).forEach((field) => {
			if (field.name) {
				field.value = params.get(field.name) || "";
			}
		});
	};

	const requestProducts = async (url, pushState = true, scroll = false) => {
		showSkeleton();

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

			clearSkeleton();
			results.innerHTML = payload.data.html;
			announce(Number(payload.data.count) || 0);

			if (pushState) {
				window.history.pushState({}, "", url);
			}

			if (scroll) {
				scrollToResults();
			}

			document.dispatchEvent(new CustomEvent("kramo:catalog-updated", {
				detail: { container: results, count: Number(payload.data.count) || 0 },
			}));
		} catch (error) {
			clearSkeleton();
			results.innerHTML = `<p class="woocommerce-error">${kramoFilters.errorMessage}</p>`;
			if (countEl) {
				countEl.textContent = kramoFilters.errorMessage;
			}
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
		requestProducts(new URL(link.href), true, true);
	});

	window.addEventListener("popstate", () => {
		const url = new URL(window.location.href);
		setFormFromUrl(url);
		requestProducts(url, false);
	});
})();
