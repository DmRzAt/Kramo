(() => {
	"use strict";

	const root = document.querySelector("[data-kramo-search]");

	if (!root || typeof kramoSearch === "undefined") {
		return;
	}

	const input = root.querySelector(".kramo-search__input");
	const panel = root.querySelector("[data-kramo-search-panel]");

	if (!input || !panel) {
		return;
	}

	let controller = null;
	let timer = 0;
	let activeIndex = -1;

	const options = () => Array.from(panel.querySelectorAll(".kramo-search__option"));

	const close = () => {
		panel.hidden = true;
		panel.replaceChildren();
		input.setAttribute("aria-expanded", "false");
		input.removeAttribute("aria-activedescendant");
		activeIndex = -1;
	};

	const setActive = (index) => {
		const items = options();

		if (!items.length) {
			return;
		}

		activeIndex = (index + items.length) % items.length;

		items.forEach((item, position) => {
			const isActive = position === activeIndex;
			item.classList.toggle("is-active", isActive);
			item.setAttribute("aria-selected", isActive ? "true" : "false");

			if (isActive) {
				input.setAttribute("aria-activedescendant", item.id);
				item.scrollIntoView({ block: "nearest" });
			}
		});
	};

	const showMessage = (message) => {
		const item = document.createElement("li");
		item.className = "kramo-search__message";
		item.textContent = message;
		panel.replaceChildren(item);
		panel.hidden = false;
		input.setAttribute("aria-expanded", "true");
		activeIndex = -1;
	};

	const render = (items) => {
		if (!items.length) {
			showMessage(kramoSearch.noResultsText);
			return;
		}

		const fragment = document.createDocumentFragment();

		items.forEach((item, index) => {
			const row = document.createElement("li");
			row.setAttribute("role", "presentation");

			const link = document.createElement("a");
			link.className = "kramo-search__option";
			link.id = `kramo-search-option-${index}`;
			link.href = item.url;
			link.setAttribute("role", "option");
			link.setAttribute("aria-selected", "false");

			if (item.thumbnail) {
				const image = document.createElement("img");
				image.src = item.thumbnail;
				image.alt = "";
				image.loading = "lazy";
				image.decoding = "async";
				link.append(image);
			}

			const body = document.createElement("span");
			body.className = "kramo-search__option-body";

			const name = document.createElement("span");
			name.className = "kramo-search__option-name";
			name.textContent = item.name;

			const price = document.createElement("span");
			price.className = "kramo-search__option-price";
			price.textContent = item.price;

			body.append(name, price);
			link.append(body);
			row.append(link);
			fragment.append(row);
		});

		panel.replaceChildren(fragment);
		panel.hidden = false;
		input.setAttribute("aria-expanded", "true");
		activeIndex = -1;
	};

	const search = async (term) => {
		if (controller) {
			controller.abort();
		}

		controller = new AbortController();

		const request = new URL(kramoSearch.ajaxUrl);
		request.searchParams.set("action", "kramo_search_suggest");
		request.searchParams.set("nonce", kramoSearch.nonce);
		request.searchParams.set("term", term);

		try {
			const response = await fetch(request, {
				credentials: "same-origin",
				signal: controller.signal,
			});
			const payload = await response.json();

			if (!response.ok || !payload.success) {
				throw new Error("Search request failed.");
			}

			render(payload.data.items);
		} catch (error) {
			if (error.name !== "AbortError") {
				showMessage(kramoSearch.errorMessage);
			}
		}
	};

	input.addEventListener("input", () => {
		const term = input.value.trim();

		window.clearTimeout(timer);

		if (term.length < 2) {
			close();
			return;
		}

		timer = window.setTimeout(() => search(term), 220);
	});

	input.addEventListener("keydown", (event) => {
		if (panel.hidden) {
			return;
		}

		if (event.key === "ArrowDown") {
			event.preventDefault();
			setActive(activeIndex + 1);
		} else if (event.key === "ArrowUp") {
			event.preventDefault();
			setActive(activeIndex - 1);
		} else if (event.key === "Enter" && activeIndex >= 0) {
			event.preventDefault();
			options()[activeIndex]?.click();
		} else if (event.key === "Escape") {
			close();
		}
	});

	document.addEventListener("click", (event) => {
		if (!root.contains(event.target)) {
			close();
		}
	});

	root.addEventListener("focusout", () => {
		window.setTimeout(() => {
			if (!root.contains(document.activeElement)) {
				close();
			}
		}, 0);
	});
})();
