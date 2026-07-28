(() => {
	"use strict";

	const slugify = (value) => value
		.toLocaleLowerCase("pl")
		.normalize("NFD")
		.replace(/[\u0300-\u036f]/g, "")
		.replace(/[^a-z0-9]+/g, "-")
		.replace(/^-|-$/g, "");

	const setupVariationSwatches = (form) => {
		let variations = [];
		try {
			variations = JSON.parse(form.dataset.product_variations || "[]");
		} catch (error) {
			variations = [];
		}

		form.querySelectorAll(".variations select").forEach((select) => {
			if (select.dataset.kramoSwatches === "ready") {
				return;
			}

			select.dataset.kramoSwatches = "ready";
			select.classList.add("kramo-native-select");

			const wrapper = document.createElement("div");
			const isColor = /kolor|color/i.test(select.name);
			wrapper.className = `kramo-variation-options ${
				isColor
					? "kramo-variation-options--color"
					: "kramo-variation-options--size"
			}`;
			wrapper.setAttribute("role", "group");
			select.insertAdjacentElement("afterend", wrapper);

			const rebuild = () => {
				const current = select.value;
				wrapper.replaceChildren();

				Array.from(select.options).slice(1).forEach((option) => {
					const button = document.createElement("button");
					const isAvailable = !variations.length || variations.some((variation) => {
						if (!variation.is_in_stock) {
							return false;
						}

						return Array.from(form.querySelectorAll(".variations select")).every((field) => {
							const variationValue = variation.attributes[field.name] || "";
							const requestedValue = field === select ? option.value : field.value;

							return !requestedValue || !variationValue || variationValue === requestedValue;
						});
					});
					const isDisabled = option.disabled || !isAvailable;
					button.type = "button";
					button.className = "kramo-variation-option";
					button.dataset.value = option.value;
					button.setAttribute("aria-pressed", option.value === current ? "true" : "false");
					button.setAttribute("aria-disabled", isDisabled ? "true" : "false");
					button.title = option.textContent.trim();

					if (isColor) {
						button.classList.add(
							"kramo-variation-option--color",
							`kramo-swatch--${slugify(option.textContent)}`
						);
						const label = document.createElement("span");
						label.className = "screen-reader-text";
						label.textContent = option.textContent;
						button.append(label);
					} else {
						button.textContent = option.textContent;
					}

					if (isDisabled) {
						button.classList.add("is-disabled");
					}

					button.addEventListener("click", () => {
						if (button.getAttribute("aria-disabled") === "true") {
							return;
						}

						select.value = option.value;
						select.dispatchEvent(new Event("change", { bubbles: true }));
						rebuild();
					});

					wrapper.append(button);
				});
			};

			form.addEventListener("change", rebuild);
			new MutationObserver(rebuild).observe(select, {
				attributes: true,
				childList: true,
				subtree: true,
			});
			rebuild();
		});
	};

	const setupProductTabs = () => {
		const tabs = document.querySelector(".woocommerce-tabs");
		if (!tabs || tabs.dataset.kramoTabs === "ready") {
			return;
		}

		tabs.dataset.kramoTabs = "ready";
		const tabList = tabs.querySelector(".tabs");
		const panels = Array.from(tabs.querySelectorAll(".woocommerce-Tabs-panel"));
		const mobile = window.matchMedia("(max-width: 47.99rem)");

		panels.forEach((panel, index) => {
			panel.dataset.kramoDesktopDisplay = panel.style.display;
			const link = tabList?.querySelector(`a[href="#${panel.id}"]`);
			if (!link) {
				return;
			}

			const button = document.createElement("button");
			button.type = "button";
			button.className = "kramo-accordion-toggle";
			button.textContent = link.textContent.trim();
			button.setAttribute("aria-controls", panel.id);
			button.setAttribute("aria-expanded", index === 0 ? "true" : "false");
			panel.insertAdjacentElement("beforebegin", button);

			button.addEventListener("click", () => {
				const willOpen = button.getAttribute("aria-expanded") !== "true";
				button.setAttribute("aria-expanded", willOpen ? "true" : "false");
				panel.hidden = !willOpen;
				if (mobile.matches) {
					panel.style.display = willOpen ? "block" : "none";
				}
			});
		});

		const syncLayout = () => {
			const buttons = Array.from(tabs.querySelectorAll(".kramo-accordion-toggle"));

			if (mobile.matches) {
				panels.forEach((panel, index) => {
					const isOpen = buttons[index]?.getAttribute("aria-expanded") === "true";
					panel.hidden = !isOpen;
					panel.style.display = isOpen ? "block" : "none";
				});
			} else {
				panels.forEach((panel) => {
					panel.hidden = false;
					panel.style.display = panel.dataset.kramoDesktopDisplay;
				});
			}
		};

		mobile.addEventListener("change", syncLayout);
		syncLayout();
	};

	document.addEventListener("click", (event) => {
		const trigger = event.target.closest(".kramo-video-trigger");
		if (!trigger) {
			return;
		}

		const source = trigger.dataset.videoSrc;
		const type = trigger.dataset.videoType;
		let media;

		if (type === "mp4") {
			media = document.createElement("video");
			media.controls = true;
			media.autoplay = true;
			media.playsInline = true;
			media.src = source;
		} else {
			media = document.createElement("iframe");
			media.src = source;
			media.title = trigger.getAttribute("aria-label");
			media.allow = "autoplay; fullscreen; picture-in-picture";
			media.allowFullscreen = true;
		}

		media.className = "kramo-product-video";
		trigger.replaceWith(media);
	});

	const wcAjaxUrl = (endpoint) => {
		const params = window.wc_add_to_cart_params;

		return params && params.wc_ajax_url
			? params.wc_ajax_url.toString().replace("%%endpoint%%", endpoint)
			: "";
	};

	const bindQuickAdd = (form) => {
		const url = wcAjaxUrl("add_to_cart");

		if (!url) {
			return;
		}

		form.addEventListener("submit", async (event) => {
			event.preventDefault();

			const button = form.querySelector(".single_add_to_cart_button");

			if (button?.classList.contains("disabled")) {
				return;
			}

			const body = new FormData(form);
			const variationId = Number(body.get("variation_id")) || 0;
			const productId = variationId || Number(body.get("add-to-cart")) || 0;

			if (!productId) {
				return;
			}

			body.set("product_id", String(productId));
			body.delete("add-to-cart");
			body.delete("kramo_buy_now");

			button?.classList.add("loading");

			try {
				const response = await fetch(url, {
					method: "POST",
					body,
					credentials: "same-origin",
				});
				const payload = await response.json();

				if (payload && payload.error && payload.product_url) {
					window.location = payload.product_url;
					return;
				}

				form.closest("dialog")?.close();

				if (window.jQuery) {
					window.jQuery(document.body).trigger(
						"added_to_cart",
						[payload && payload.fragments, payload && payload.cart_hash, window.jQuery(button)]
					);
				} else {
					document.dispatchEvent(new CustomEvent("kramo:cart-changed", {
						detail: { message: kramoQuickView.addedMessage },
					}));
				}
			} catch (error) {
				document.dispatchEvent(new CustomEvent("kramo:cart-changed", {
					detail: { message: kramoQuickView.errorMessage, variant: "error" },
				}));
			} finally {
				button?.classList.remove("loading");
			}
		});
	};

	const setupQuickView = () => {
		const dialog = document.querySelector("[data-kramo-quick-view-dialog]");

		if (!dialog || typeof kramoQuickView === "undefined" || !dialog.showModal) {
			return;
		}

		const content = dialog.querySelector("[data-kramo-quick-view-content]");
		let controller = null;

		const skeleton = () => {
			content.innerHTML = [
				'<div class="kramo-quick-view__skeleton" aria-hidden="true">',
				'<span class="kramo-skeleton kramo-skeleton-card__media"></span>',
				'<span class="kramo-skeleton kramo-skeleton-card__line"></span>',
				'<span class="kramo-skeleton kramo-skeleton-card__line kramo-skeleton-card__line--short"></span>',
				"</div>",
			].join("");
		};

		const load = async (productId) => {
			if (controller) {
				controller.abort();
			}

			controller = new AbortController();
			skeleton();

			const request = new URL(kramoQuickView.ajaxUrl);
			request.searchParams.set("action", "kramo_quick_view");
			request.searchParams.set("nonce", kramoQuickView.nonce);
			request.searchParams.set("product_id", String(productId));

			try {
				const response = await fetch(request, {
					credentials: "same-origin",
					signal: controller.signal,
				});
				const payload = await response.json();

				if (!response.ok || !payload.success) {
					throw new Error("Quick view request failed.");
				}

				content.innerHTML = payload.data.html;
				dialog.setAttribute("aria-label", payload.data.title);

				content.querySelectorAll(".variations_form").forEach((form) => {
					if (window.jQuery && window.jQuery.fn.wc_variation_form) {
						window.jQuery(form).wc_variation_form();
					}
					setupVariationSwatches(form);
				});

				content.querySelectorAll("form.cart").forEach(bindQuickAdd);
				content.querySelector("input, select, button")?.focus();
			} catch (error) {
				if (error.name === "AbortError") {
					return;
				}

				content.innerHTML = `<p class="woocommerce-error">${kramoQuickView.errorMessage}</p>`;
			}
		};

		document.addEventListener("click", (event) => {
			const trigger = event.target.closest("[data-kramo-quick-view]");

			if (!trigger) {
				return;
			}

			event.preventDefault();
			dialog.showModal();
			load(Number(trigger.dataset.kramoQuickView));
		});

		dialog.addEventListener("click", (event) => {
			if (event.target === dialog || event.target.closest("[data-kramo-quick-view-close]")) {
				dialog.close();
			}
		});

		dialog.addEventListener("close", () => {
			if (controller) {
				controller.abort();
			}
			content.replaceChildren();
		});
	};

	const setupStickyCart = () => {
		const bar = document.querySelector("[data-kramo-sticky-cart]");
		const form = document.querySelector("form.cart");
		const anchor = form?.querySelector("button[type='submit'], .single_add_to_cart_button");

		if (!bar || !form || !anchor || !("IntersectionObserver" in window)) {
			return;
		}

		const action = bar.querySelector("[data-kramo-sticky-cart-action]");

		action?.addEventListener("click", () => {
			form.scrollIntoView({
				behavior: window.kramo && window.kramo.reducedMotion ? "auto" : "smooth",
				block: "center",
			});

			if (form.checkValidity()) {
				form.requestSubmit
					? form.requestSubmit(anchor)
					: anchor.click();
			}
		});

		new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				bar.hidden = entry.isIntersecting;
			});
		}, { rootMargin: "0px 0px -12% 0px" }).observe(anchor);
	};

	document.addEventListener("DOMContentLoaded", () => {
		document.querySelectorAll(".variations_form").forEach(setupVariationSwatches);
		setupProductTabs();
		setupQuickView();
		setupStickyCart();
	});
})();
