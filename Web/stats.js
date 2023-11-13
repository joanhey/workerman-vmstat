var allTimeSeries = {};
var allValueLabels = {};
const descriptions = {
  Processes: {
    r: "Number of processes waiting for run time",
    b: "Number of processes in uninterruptible sleep",
  },
  Memory: {
    swpd: "Amount of virtual memory used",
    free: "Amount of idle memory",
    buff: "Amount of memory used as buffers",
    cache: "Amount of memory used as cache",
  },
  Swap: {
    si: "Amount of memory swapped in from disk",
    so: "Amount of memory swapped to disk",
  },
  IO: {
    bi: "Blocks received from a block device (blocks/s)",
    bo: "Blocks sent to a block device (blocks/s)",
  },
  System: {
    in: "Number of interrupts per second, including the clock",
    cs: "Number of context switches per second",
  },
  CPU: {
    us: "Time spent running non-kernel code (user time, including nice time)",
    sy: "Time spent running kernel code (system time)",
    id: "Time spent idle",
    wa: "Time spent waiting for IO",
  },
};
const colHeadings = [
  "r",
  "b",
  "swpd",
  "free",
  "buff",
  "cache",
  "si",
  "so",
  "bi",
  "bo",
  "in",
  "cs",
  "us",
  "sy",
  "id",
  "wa",
];
const colors = [
    "#b3e2cd",
    "#fdcdac",
    "#cbd5e8",
    "#f4cae4"
]

function streamStats() {
  var source = new EventSource("http://localhost:3000");
  source.addEventListener(
    "stats",
    (event) => {
      var colValues = event.data.trim().split(/ +/);

      var stats = {};
      for (var i = 0; i < colHeadings.length; i++) {
        stats[colHeadings[i]] = parseInt(colValues[i]);
      }
      receiveStats(stats);
    },
    false
  );
}

function initCharts() {
  const charts = document.querySelector("#charts");
  const template = document.querySelector("#section-template").content;
  const fragment = document.createDocumentFragment();

  Object.entries(descriptions).forEach(([sectionName, values]) => {
    const clone = template.cloneNode(true);
    clone.querySelector(".title").textContent = sectionName;
    var smoothie = new SmoothieChart({
      grid: {
        sharpLines: true,
        verticalSections: 5,
        strokeStyle: "rgba(119,119,119,0.45)",
        millisPerLine: 1000,
      },
      minValue: 0,
      labels: {
        disabled: true,
      },
    });
    smoothie.streamTo(clone.querySelector("canvas"), 1000);

    var index = 0;
    Object.entries(values).forEach(([name, description]) => {
      var color = colors[index++];
      var timeSeries = new TimeSeries();
      smoothie.addTimeSeries(timeSeries, {
        strokeStyle: color,
        fillStyle: color + "77",
        lineWidth: 3,
      });
      allTimeSeries[name] = timeSeries;

      var statLine = clone.querySelector(".stat").cloneNode(true);
      statLine.setAttribute("title", description);
      statLine.style.color = color;
      statLine.querySelector(".stat-name").textContent = name;
      allValueLabels[name] = statLine.querySelector(".stat-value");
      clone.querySelector(".stats").appendChild(statLine);
    });

    fragment.appendChild(clone);
  });

  charts.appendChild(fragment);
}

function receiveStats(stats) {
  Object.entries(stats).forEach(([name, value]) => {
    var timeSeries = allTimeSeries[name];
    if (timeSeries) {
      timeSeries.append(Date.now(), value);
      allValueLabels[name].textContent = value;
    }
  });
}

initCharts();
streamStats();
