document.addEventListener("DOMContentLoaded", function () {

  // Auto hide flash messages after 4 seconds
  const flashes = document.querySelectorAll(".card");
  flashes.forEach(function (card) {
    if (card.style.borderColor.includes("22,163,74") || card.style.borderColor.includes("220,38,38")) {
      setTimeout(function () {
        card.style.transition = "opacity 0.4s ease";
        card.style.opacity = "0";
      }, 4000);
    }
  });

});