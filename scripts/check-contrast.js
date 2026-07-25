"use strict";

const fs = require("node:fs");
const path = require("node:path");

const tokensPath = path.join(
	__dirname,
	"..",
	"theme",
	"kramo-child",
	"assets",
	"css",
	"tokens.css"
);
const presetName = process.argv[2] || "";
const allowedPresets = new Set(["craft", "service", "premium"]);

if (presetName && !allowedPresets.has(presetName)) {
	console.error(`Unknown preset "${presetName}". Use craft, service or premium.`);
	process.exit(1);
}

const presetPath = presetName
	? path.join(
		__dirname,
		"..",
		"theme",
		"kramo-child",
		"assets",
		"css",
		"presets",
		`${presetName}.css`
	)
	: "";
const presetCss = presetPath ? fs.readFileSync(presetPath, "utf8") : "";

function validatePresetCss(source) {
	const withoutComments = source.replace(/\/\*[\s\S]*?\*\//g, "").trim();
	const rootBlock = withoutComments.match(/^:root\s*\{([\s\S]*)\}$/);

	if (!rootBlock) {
		throw new Error("Preset CSS must contain one :root block only.");
	}

	const declarations = rootBlock[1]
		.split(";")
		.map((declaration) => declaration.trim())
		.filter(Boolean);

	for (const declaration of declarations) {
		if (!/^--[\w-]+\s*:\s*var\(--[\w-]+\)$/.test(declaration)) {
			throw new Error(
				`Preset CSS may only override variables with token references: ${declaration}`
			);
		}
	}
}

if (presetCss) {
	validatePresetCss(presetCss);
}

const css = [
	fs.readFileSync(tokensPath, "utf8"),
	presetCss,
].join("\n");
const tokens = new Map();
const tokenPattern = /(--[\w-]+)\s*:\s*([^;]+);/g;

for (const match of css.matchAll(tokenPattern)) {
	tokens.set(match[1], match[2].trim());
}

function resolveToken(name, trail = new Set()) {
	if (trail.has(name)) {
		throw new Error(`Circular token reference: ${[...trail, name].join(" -> ")}`);
	}

	if (!tokens.has(name)) {
		throw new Error(`Missing token: ${name}`);
	}

	const value = tokens.get(name);
	const reference = value.match(/^var\((--[\w-]+)\)$/);
	if (!reference) {
		return value;
	}

	trail.add(name);
	const resolved = resolveToken(reference[1], trail);
	trail.delete(name);

	return resolved;
}

function parseHexColor(value) {
	const match = value.match(/^#([\da-f]{3}|[\da-f]{6})$/i);
	if (!match) {
		throw new Error(`Unsupported color value: ${value}`);
	}

	const hex = match[1].length === 3
		? match[1].split("").map((character) => character + character).join("")
		: match[1];

	return [0, 2, 4].map((offset) => Number.parseInt(hex.slice(offset, offset + 2), 16));
}

function relativeLuminance(color) {
	const channels = parseHexColor(color).map((channel) => {
		const normalized = channel / 255;
		return normalized <= 0.04045
			? normalized / 12.92
			: ((normalized + 0.055) / 1.055) ** 2.4;
	});

	return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
}

function contrastRatio(foreground, background) {
	const foregroundLuminance = relativeLuminance(resolveToken(foreground));
	const backgroundLuminance = relativeLuminance(resolveToken(background));
	const lighter = Math.max(foregroundLuminance, backgroundLuminance);
	const darker = Math.min(foregroundLuminance, backgroundLuminance);

	return (lighter + 0.05) / (darker + 0.05);
}

const minimumRatio = 4.5;
const pairs = [
	["Body text on page background", "--color-text", "--color-bg"],
	["Primary color on surface", "--color-primary", "--color-surface"],
	["Button text on primary button", "--color-on-primary", "--color-primary"],
];

let failed = false;

for (const [label, foreground, background] of pairs) {
	const ratio = contrastRatio(foreground, background);
	const status = ratio >= minimumRatio ? "PASS" : "FAIL";
	const message = `${status} ${label}: ${ratio.toFixed(2)}:1`;

	if (ratio >= minimumRatio) {
		console.log(message);
	} else {
		console.error(message);
		failed = true;
	}
}

if (failed) {
	console.error(`Contrast must be at least ${minimumRatio}:1.`);
	process.exit(1);
}

console.log(
	`All required contrast pairs pass WCAG AA${presetName ? ` for ${presetName}` : ""}.`
);
