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

	document.addEventListener("DOMContentLoaded", () => {
		document.querySelectorAll(".variations_form").forEach(setupVariationSwatches);
		setupProductTabs();
	});
})();
