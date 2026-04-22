// Get the button
let mybutton = document.getElementById("myBtn");

// When the user scrolls down 20px from the top of the document, show the button
window.onscroll = function() {scrollFunction()};

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    mybutton.style.display = "block";
  } else {
    mybutton.style.display = "none";
  }
}

// When the user clicks on the button, scroll to the top of the document with a smooth effect
function topFunction() {
  // Set the duration of the scroll animation (in milliseconds)
  const duration = 500;

  // Get the current scroll position
  const start = window.pageYOffset;

  // Calculate the scroll distance
  const distance = 0 - start;

  // Get the start time
  let startTime = null;
  function animate(time) {
    if (!startTime) startTime = time;
    const progress = time - startTime;

    // Set the new scroll position
    window.scrollTo(0, easeInOutCubic(progress, start, distance, duration));

    // Continue the animation until the duration is reached
    if (progress < duration) {
      requestAnimationFrame(animate);
    }
  }
 // Start the animation
  requestAnimationFrame(animate);
}

// Easing function for smooth scroll
function easeInOutCubic(t, b, c, d) {
  t /= d / 2;
  if (t < 1) return c / 2 * t * t * t + b;
  t -= 2;
  return c / 2 * (t * t * t + 2) + b;
}
