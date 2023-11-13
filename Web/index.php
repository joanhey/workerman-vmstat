<!DOCTYPE html>
<title>Stats</title>
<meta charset="utf8">
<link rel="stylesheet" href="stats.css">

<main id="charts"></main>

<template id="section-template">
  <section>
    <h2 class="title"></h2>
    <canvas width="600" height="80"></canvas>
    <ul class="stats">
        <li class="stat">
            <span class="stat-name"></span>
            <span class="stat-value"></span>
        </li>
    </ul>
  </section>
</template>

<script src="js/smoothie.js"></script>
<script src="stats.js"></script>
