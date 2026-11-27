/* Stub MapKit library for local ConsentGate proofs. */
window.mapkit = {
	init: function () {},
	Map: function (id) {
		var el = typeof id === "string" ? document.getElementById(id) : id;
		if (el) {
			el.setAttribute("data-cg-map-ready", "1");
			el.textContent = "map-ready";
		}
		return { showItems: function () {}, region: null };
	},
	Coordinate: function () {},
	CoordinateRegion: function () {},
	CoordinateSpan: function () {},
	MarkerAnnotation: function () {
		return { title: "", subtitle: "", selected: false };
	}
};
