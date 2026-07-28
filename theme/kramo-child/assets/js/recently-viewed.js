(() => {
	"use strict";

	if (typeof kramoRecent === "undefined") {
		return;
	}

	const storageKey = "kramo_recently_viewed";

	const readIds = () => {
		try {
			const ids = JSON.parse(window.localStorage.getItem(storageKey));

			return Array.isArray(ids)
				? ids.map(Number).filter((id) => Number.isInteger(id) && id > 0)
				: [];
		} catch (error) {
			return [];
		}
	};

	const remember = (productId) => {
		const ids = [productId, ...readIds().filter((id) => id !== productId)]
			.slice(0, kramoRecent.limit);

		window.localStorage.setItem(storageKey, JSON.stringify(ids));

		return ids;
	};

	const buildCard = (item) => {
		const link = document.createElement("a");
		link.className = "kramo-recent__card";
		link.href = item.url;

		const media = document.createElement("span");
		media.className = "kramo-recent__media";

		if (item.thumbnail) {
			const image = document.createElement("img");
			image.src = item.thumbnail;
			image.alt = "";
			image.loading = "lazy";
			image.decoding = "async";
			media.append(image);
		}

		const name = document.createElement("span");
		name.className = "kramo-recent__name";
		name.textContent = item.name;

		const price = document.createElement("span");
		price.className = "kramo-recent__price";
		price.textContent = item.price;

		link.append(media, name, price);

		return link;
	};

	const render = async (ids) => {
		const section = document.querySelector("[data-kramo-recent]");
		const track = document.querySelector("[data-kramo-recent-track]");

		if (!section || !track || !ids.length) {
			return;
		}

		const request = new URL(kramoRecent.ajaxUrl);
		request.searchParams.set("action", "kramo_recently_viewed");
		request.searchParams.set("nonce", kramoRecent.nonce);
		ids.forEach((id) => request.searchParams.append("product_ids[]", String(id)));

		try {
			const response = await fetch(request, { credentials: "same-origin" });
			const payload = await response.json();

			if (!response.ok || !payload.success || !payload.data.items.length) {
				return;
			}

			track.replaceChildren(...payload.data.items.map(buildCard));
			section.hidden = false;

			document.dispatchEvent(new CustomEvent("kramo:catalog-updated", {
				detail: { container: track },
			}));
		} catch (error) {
			section.hidden = true;
		}
	};

	const start = () => {
		const current = Number(kramoRecent.currentProduct) || 0;
		const ids = current ? remember(current) : readIds();

		render(ids.filter((id) => id !== current));
	};

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", start);
	} else {
		start();
	}
})();
