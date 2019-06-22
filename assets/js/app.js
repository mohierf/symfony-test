// assets/js/app.js
// ...

require('../css/app.css');

const $ = require('jquery');
// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
require('bootstrap');
require("bootstrap/dist/css/bootstrap.css");

// or you can include specific pieces
// require('bootstrap/js/dist/tooltip');
// require('bootstrap/js/dist/popover');

$(document).ready(function() {
    console.log("Ready!");
    $('[data-toggle="popover"]').popover();
});