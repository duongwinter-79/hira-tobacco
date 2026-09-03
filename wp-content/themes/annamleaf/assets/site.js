/**
 * Annam Leaf front end.
 *
 * Two jobs: the mobile menu, and the 18+ trade gate when it is switched on in the
 * company profile. Nothing else on the site depends on JavaScript.
 */
(function () {
	"use strict";

	var burger = document.getElementById("burger");
	var nav = document.getElementById("primary-nav");

	if (burger && nav) {
		burger.addEventListener("click", function () {
			var open = nav.classList.toggle("open");
			burger.setAttribute("aria-expanded", String(open));
		});
	}

	var gate = document.getElementById("gate");
	var enabled = window.annamleafData && window.annamleafData.ageGate;

	if (!gate || !enabled) {
		return;
	}

	var KEY = "annamleaf-age-ok";
	var confirmed = false;

	try {
		confirmed = window.localStorage.getItem(KEY) === "1";
	} catch (e) {
		// Private browsing, or site data blocked: ask again this visit.
	}

	if (!confirmed) {
		gate.hidden = false;
		document.body.style.overflow = "hidden";
	}

	var yes = document.getElementById("gate-yes");

	if (yes) {
		yes.addEventListener("click", function () {
			gate.hidden = true;
			document.body.style.overflow = "";

			try {
				window.localStorage.setItem(KEY, "1");
			} catch (e) {
				// Not storable: the gate simply asks again next time.
			}
		});
	}
})();
