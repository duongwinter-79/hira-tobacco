/**
 * Does the scoring reject what the first run wrongly downloaded?
 *
 *     node tools/test-photo-scoring.mjs
 *
 * The cases are the real files that came back the first time, plus plausible good ones,
 * described exactly as their source APIs describe them.
 */

import { SLOTS, score } from "./fetch-photos.mjs";

const slot = (name) => SLOTS.find((s) => s.slot === name);

const CASES = [
	{
		name: "engraving of enslaved people (was picked for sorting)",
		slot: "stage-5",
		want: "reject",
		candidate: {
			title: "The manufacture of tobacco with leaves being sorted, dried, Wellcome L0017403",
			description: "Engraving showing tobacco manufacture",
			tags: "Wellcome Collection engravings",
			date: "1750",
			width: 2000,
			height: 1500,
		},
	},
	{
		name: "1915 colonial plantation (was picked for the nursery)",
		slot: "stage-1",
		want: "reject",
		candidate: {
			title: "KITLV - 5484 - Kleingrothe, C.J. - Medan - Planting seedlings on a tobacco plantation of the Deli Maatschappij, Deli - circa 1915",
			description: "Photograph of a tobacco plantation",
			tags: "KITLV collection",
			date: "circa 1915",
			width: 2400,
			height: 1600,
		},
	},
	{
		name: "Bristol building named The Tobacco Factory (was picked for processing)",
		slot: "stage-6",
		want: "reject",
		candidate: {
			title: "The Tobacco Factory on North Street - geograph.org.uk - 2470375",
			description: "A theatre and cafe in a converted building exterior on the street",
			tags: "Bristol buildings",
			date: "2011",
			width: 1500,
			height: 1000,
		},
	},
	{
		name: "Cuba postcard from the 1900s (was picked for the field)",
		slot: "stage-2",
		want: "reject",
		candidate: {
			title: "Cuba - Tobacco field",
			description: "Postcard published by the Rotograph Co",
			tags: "postcards Cuba",
			date: "1904",
			width: 1800,
			height: 1200,
		},
	},
	{
		name: "portrait orientation",
		slot: "stage-2",
		want: "reject",
		candidate: { title: "Tobacco field", description: "", tags: "", date: "2018", width: 1300, height: 1900 },
	},
	{
		name: "too small",
		slot: "stage-2",
		want: "reject",
		candidate: { title: "Tobacco field", description: "", tags: "", date: "2018", width: 900, height: 600 },
	},
	{
		name: "cigarette packet shot",
		slot: "stage-5",
		want: "reject",
		candidate: {
			title: "Cigarette packet with dried tobacco",
			description: "A packet of cigarettes in a shop",
			tags: "cigarette smoking",
			date: "2019",
			width: 2000,
			height: 1300,
		},
	},
	{
		name: "modern curing barn (was picked, and is fine)",
		slot: "stage-4",
		want: "accept",
		candidate: {
			title: "Tobacco-curing barn near Budd's Creek, Maryland",
			description: "A tobacco curing barn with leaves hanging inside to dry",
			tags: "barns tobacco Maryland",
			date: "2012",
			width: 2400,
			height: 1600,
		},
	},
	{
		name: "modern tobacco field photograph",
		slot: "stage-2",
		want: "accept",
		candidate: {
			title: "Tobacco field in bloom",
			description: "Rows of Nicotiana tabacum growing on a farm, green leaves in the afternoon",
			tags: "tobacco field farm crop",
			date: "2021",
			width: 2000,
			height: 1333,
		},
	},
	{
		name: "Cao Bang landscape",
		slot: "home",
		want: "accept",
		candidate: {
			title: "Rice fields in Cao Bang province, Vietnam",
			description: "Karst mountains above rice fields in a valley in Cao Bang",
			tags: "Vietnam landscape Cao Bang",
			date: "2015",
			width: 2400,
			height: 1600,
		},
	},
	{
		name: "container terminal",
		slot: "stage-7",
		want: "accept",
		candidate: {
			title: "Containers stacked at the port terminal",
			description: "Shipping containers being loaded by a crane at a container terminal",
			tags: "port container terminal crane",
			date: "2016",
			width: 2400,
			height: 1400,
		},
	},
];

let failures = 0;

for (const test of CASES) {
	const result = score(test.candidate, slot(test.slot), 0);
	const accepted = result.total >= 6;
	const ok = test.want === (accepted ? "accept" : "reject");

	if (!ok) failures++;

	console.log(
		`${ok ? "  ok  " : "FAIL  "}${test.want.padEnd(7)} ${String(result.total).padStart(6)}  ${test.name}  (${result.why})`
	);
}

console.log(`\n${CASES.length - failures}/${CASES.length} cases behave as intended.`);

if (failures) process.exitCode = 1;
