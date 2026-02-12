<?php
//this is autoload routing for frontend

// Load files inside views/app/routes/ folder
load_routes(
    "web",
);

// Load files inside views/app/auto/ folder
load_auto(
    "functions",
);
