// assets/js/app.js
// ...

require('../css/app.css');

// require jQuery normally
const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;
// this "modifies" the jquery module: adding behavior to it

// the bootstrap module doesn't export/return anything
require('bootstrap');
require("bootstrap/dist/css/bootstrap.css");
// Font awesome 5
require('@fortawesome/fontawesome-free/css/all.min.css');


// jsTree
// require('jstree/dist/themes/default-dark/style.min.css');
require('jstree/dist/jstree.min.js');

$(document).ready(function() {
    console.log("Ready!");
    $('[data-toggle="popover"]').popover();
});